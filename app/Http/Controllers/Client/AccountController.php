<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use App\Models\ClientSubaccount;
use App\Models\ClientSubaccountAllocation;
use App\Models\StockRequestItem;
use App\Models\ClientSubaccountMember;
use App\Models\ClientSubaccountDistribution;
use App\Models\ClientMember;
use App\Models\ClientMemberDistribution;
use App\Models\ClientDirectDeduction;
use App\Models\Outbound;
use Dompdf\Dompdf;

class AccountController extends Controller
{
    protected function prepareAccountReportData($user, $dateFrom = null, $dateTo = null)
    {
        $subaccounts = collect();
        $subaccountReports = collect();
        $clientMembers = collect();
        $memberReports = collect();

        if ($user->role === 'client') {
            $subaccounts = ClientSubaccount::where('client_user_id', $user->id)
                ->with(['user', 'members.distributions.stockRequestItem.stock' => function ($query) use ($dateFrom, $dateTo) {
                    if ($dateFrom) {
                        $query->whereDate('created_at', '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $query->whereDate('created_at', '<=', $dateTo);
                    }
                }, 'allocations.stockRequestItem.stock'])
                ->withCount('members')
                ->get();

            $subaccountReports = $subaccounts->map(function ($subaccount) {
                return [
                    'subaccount_name' => $subaccount->name,
                    'members' => $subaccount->members->map(function ($member) {
                        return [
                            'name' => $member->name,
                            'email' => $member->email,
                            'available_items' => $member->distributions->sum('distributed_qty'),
                            'used_items' => Schema::hasColumn('client_subaccount_distributions', 'used_qty') ? $member->distributions->sum('used_qty') : 0,
                            'used_value' => $member->distributions->sum('used_qty') ?? 0,
                        ];
                    })->toArray(),
                ];
            });

            // Get all client members with distributions for reporting
            $clientMembers = ClientMember::where('client_id', $user->id)
                ->with(['distributions.stockRequestItem.stock', 'directDeductions.stockRequestItem.stock'])
                ->get();
            
            $allClientMembers = $clientMembers;

            // Get distributions within date range for accurate filtering
            if ($dateFrom || $dateTo) {
                $memberDistributionsQuery = \App\Models\ClientMemberDistribution::with(['member', 'stockRequestItem.stock'])
                    ->whereHas('member', function ($query) use ($user) {
                        $query->where('client_id', $user->id);
                    });

                if ($dateFrom) {
                    $memberDistributionsQuery->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $memberDistributionsQuery->whereDate('created_at', '<=', $dateTo);
                }

                $filteredDistributions = $memberDistributionsQuery->get();
                
                // Get direct deductions within date range
                $directDeductionsQuery = \App\Models\ClientDirectDeduction::with(['member'])
                    ->whereHas('member', function ($query) use ($user) {
                        $query->where('client_id', $user->id);
                    })
                    ->where('stock_request_item_id', null); // Only direct request items

                if ($dateFrom) {
                    $directDeductionsQuery->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $directDeductionsQuery->whereDate('created_at', '<=', $dateTo);
                }

                $filteredDirectDeductions = $directDeductionsQuery->get();
                
                // Group distributions and deductions by member for easy lookup
                $distributionsByMember = $filteredDistributions->groupBy('member_id');
                $directDeductionsByMember = $filteredDirectDeductions->groupBy('member_id');
            } else {
                // No date filters, get all distributions and direct deductions
                $allDistributions = \App\Models\ClientMemberDistribution::with(['member', 'stockRequestItem.stock'])
                    ->whereHas('member', function ($query) use ($user) {
                        $query->where('client_id', $user->id);
                    })->get();
                
                $allDirectDeductions = \App\Models\ClientDirectDeduction::with(['member'])
                    ->whereHas('member', function ($query) use ($user) {
                        $query->where('client_id', $user->id);
                    })
                    ->where('stock_request_item_id', null) // Only direct request items
                    ->get();
                
                $distributionsByMember = $allDistributions->groupBy('member_id');
                $directDeductionsByMember = $allDirectDeductions->groupBy('member_id');
            }

            $memberReports = $allClientMembers->map(function ($member) use ($distributionsByMember, $directDeductionsByMember) {
                $memberDistributions = $distributionsByMember->get($member->id, collect());
                $memberDirectDeductions = $directDeductionsByMember->get($member->id, collect());
                
                // Regular distributions
                $distributedQty = $memberDistributions->sum('distributed_qty');
                $usedQty = Schema::hasColumn('client_member_distributions', 'used_qty') ? $memberDistributions->sum('used_qty') : 0;
                
                // Direct deductions (count as distributed and available, not used)
                $directDistributedQty = $memberDirectDeductions->sum('deducted_qty');
                $directAvailableQty = $memberDirectDeductions->sum('deducted_qty');
                
                // Combine both types
                $totalDistributed = $distributedQty + $directDistributedQty;
                $totalUsed = $usedQty; // Don't add direct deductions to used
                $availableQty = ($distributedQty - $usedQty) + $directAvailableQty; // Direct items add to available
                
                return [
                    'name' => $member->name,
                    'email' => $member->email,
                    'distributed_items' => $totalDistributed,
                    'available_items' => max(0, $availableQty),
                    'used_items' => $totalUsed,
                    'used_value' => ($memberDistributions->sum('used_qty') ?? 0),
                ];
            });
        }

        // Get approved inventory items for this client
        $rawApprovedInventory = StockRequestItem::with('stock')
            ->whereHas('request', function ($query) use ($user) {
                $clientId = $user->role === 'subaccount' && $user->parent_client_id ? $user->parent_client_id : $user->id;
                $query->where('client_id', $clientId)
                    ->whereIn('status', ['approved', 'ready_to_receive', 'released']);
            })
            ->where('approved_qty', '>', 0)
            ->withSum('allocations', 'allocated_qty')
            ->get();

        // Get direct requests for this client
        $directRequests = Outbound::with(['stock'])
            ->where('client_id', $user->id)
            ->where('is_direct_request', true)
            ->where('approval', 'approved')
            ->whereIn('status', ['on process', 'received'])
            ->get();

        // Aggregate inventory items by stock (same logic as StockController with accumulation)
        $stockInventoryMap = [];
        
        foreach ($rawApprovedInventory as $item) {
            $stockId = $item->stock->id;
            $myInventory = max(0, ($item->approved_qty ?? 0) - ($item->distributed_qty ?? 0));
            
            // Check if this stock already exists in the map and accumulate
            if (isset($stockInventoryMap[$stockId])) {
                // Add to existing inventory
                $stockInventoryMap[$stockId]->approved_qty += $item->approved_qty;
                $stockInventoryMap[$stockId]->distributed_qty += ($item->distributed_qty ?? 0);
                $stockInventoryMap[$stockId]->my_inventory += $myInventory;
            } else {
                // Create new entry
                $stockInventoryMap[$stockId] = (object)[
                    'id' => $item->id,
                    'stock' => $item->stock,
                    'approved_qty' => $item->approved_qty,
                    'distributed_qty' => $item->distributed_qty,
                    'my_inventory' => $myInventory,
                    'type' => 'inventory'
                ];
            }
        }
        
        // Then, add direct request quantities to existing items or create new entries
        foreach ($directRequests as $directRequest) {
            $stockId = $directRequest->stock->id;
            
            // Calculate how much has been deducted from this direct request
            $deductedFromDirect = ClientDirectDeduction::where('stock_request_item_id', null)
                ->whereHas('member', function($query) use ($user) {
                    $query->where('client_id', $user->id);
                })
                ->where('created_at', '>=', $directRequest->created_at)
                ->sum('deducted_qty');
            
            $availableFromDirect = max(0, $directRequest->total - $deductedFromDirect);
            
            if (isset($stockInventoryMap[$stockId])) {
                // Add to existing item
                $stockInventoryMap[$stockId]->my_inventory += $availableFromDirect;
                $stockInventoryMap[$stockId]->approved_qty += $directRequest->total;
                // Add the deducted amount to distributed_qty for consistency
                $stockInventoryMap[$stockId]->distributed_qty += $deductedFromDirect;
            } else {
                // Create new entry for direct request only item
                $stockInventoryMap[$stockId] = (object)[
                    'id' => 'direct_' . $directRequest->id,
                    'stock' => $directRequest->stock,
                    'approved_qty' => $directRequest->total,
                    'distributed_qty' => $deductedFromDirect,
                    'my_inventory' => $availableFromDirect,
                    'type' => 'inventory'
                ];
            }
        }
        
        // Convert back to collection
        $approvedInventory = collect(array_values($stockInventoryMap));

        // Calculate main inventory totals based on approved inventory with date filtering
        if ($dateFrom || $dateTo) {
            // For the main Inventory card, we need to filter approved inventory by date
            $approvedInventoryQuery = StockRequestItem::with('stock')
                ->whereHas('request', function ($query) use ($user) {
                    $clientId = $user->role === 'subaccount' && $user->parent_client_id ? $user->parent_client_id : $user->id;
                    $query->where('client_id', $clientId)
                        ->whereIn('status', ['approved', 'ready_to_receive', 'released']);
                })
                ->where('approved_qty', '>', 0);

            // Filter by request approval date within the date range
            if ($dateFrom) {
                $approvedInventoryQuery->whereHas('request', function ($query) use ($dateFrom) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                });
            }
            if ($dateTo) {
                $approvedInventoryQuery->whereHas('request', function ($query) use ($dateTo) {
                    $query->whereDate('created_at', '<=', $dateTo);
                });
            }

            $filteredApprovedInventory = $approvedInventoryQuery->get();

            // Calculate totals from filtered approved inventory
            $totalReceivedInRange = $filteredApprovedInventory->sum('approved_qty');
            $totalDistributedInRange = $filteredApprovedInventory->sum(function ($item) {
                return $item->distributed_qty ?? 0;
            });
            $totalAvailableInRange = $filteredApprovedInventory->sum(function ($item) {
                return max(0, $item->approved_qty - ($item->distributed_qty ?? 0));
            });
            $totalDistributedValueInRange = $filteredApprovedInventory->sum('distributed_qty') ?? 0;
            $totalAvailableValueInRange = $filteredApprovedInventory->sum(function ($item) {
                return max(0, $item->approved_qty - ($item->distributed_qty ?? 0));
            }) ?? 0;

            $mainInventoryTotals = [
                'total_received' => $totalReceivedInRange,
                'total_distributed' => $totalDistributedInRange,
                'total_available' => $totalAvailableInRange,
                'total_distributed_value' => $totalDistributedValueInRange,
                'total_available_value' => $totalAvailableValueInRange,
            ];
        } else {
            // No date filters, show all-time totals
            $mainInventoryTotals = [
                'total_received' => $approvedInventory->sum('approved_qty'),
                'total_distributed' => $approvedInventory->sum(function ($item) {
                    return $item->distributed_qty ?? 0;
                }),
                'total_available' => $approvedInventory->sum(function ($item) {
                    return max(0, $item->approved_qty - ($item->distributed_qty ?? 0));
                }),
                'total_distributed_value' => $approvedInventory->sum('distributed_qty') ?? 0,
                'total_available_value' => $approvedInventory->sum(function ($item) {
                    return max(0, $item->approved_qty - ($item->distributed_qty ?? 0));
                }) ?? 0,
            ];
        }

        $totalAllocated = ClientSubaccountAllocation::whereHas('subaccount', function ($query) use ($user) {
                $query->where('client_user_id', $user->id);
            })
            ->sum('allocated_qty');

        $totalMemberUsed = ClientSubaccountDistribution::whereHas('member', function ($query) use ($user) {
                $query->whereHas('subaccount', function ($subQuery) use ($user) {
                    $subQuery->where('client_user_id', $user->id);
                });
            })
            ->sum('distributed_qty') +
            ClientSubaccountAllocation::whereHas('subaccount', function ($query) use ($user) {
                $query->where('client_user_id', $user->id);
            })
            ->sum('used_qty');

        $subaccountAllocatedValue = ClientSubaccountAllocation::whereHas('subaccount', function ($query) use ($user) {
                $query->where('client_user_id', $user->id);
            })->sum('allocated_qty');

        $subaccountUsedValue = ClientSubaccountDistribution::whereHas('member', function ($query) use ($user) {
                $query->whereHas('subaccount', function ($subQuery) use ($user) {
                    $subQuery->where('client_user_id', $user->id);
                });
            })->sum('distributed_qty') +
            ClientSubaccountAllocation::whereHas('subaccount', function ($query) use ($user) {
                $query->where('client_user_id', $user->id);
            })->sum('used_qty') ?? 0;

        $subaccountInventoryTotals = [
            'total_allocated' => $totalAllocated,
            'total_used_by_members' => $totalMemberUsed,
            'total_available_for_members' => max(0, $totalAllocated - $totalMemberUsed),
            'total_allocated_value' => $subaccountAllocatedValue,
            'total_used_value' => $subaccountUsedValue,
            'total_available_value' => max(0, $subaccountAllocatedValue - $subaccountUsedValue),
        ];

        // Calculate member inventory totals with date filtering
        $memberDistributionsQuery = ClientMemberDistribution::whereHas('member', function ($query) use ($user) {
            $query->whereHas('client', function ($subQuery) use ($user) {
                $subQuery->where('client_id', $user->id);
            });
        });

        // Apply date filters if they exist
        if ($dateFrom) {
            $memberDistributionsQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $memberDistributionsQuery->whereDate('created_at', '<=', $dateTo);
        }

        $memberDistributions = $memberDistributionsQuery->get();

        $totalMemberDistributed = $memberDistributions->sum('distributed_qty');
        $totalMemberUsed = $memberDistributions->sum('used_qty');
        $memberDistributedValue = $memberDistributions->sum('distributed_qty');
        $memberUsedValue = $memberDistributions->sum('used_qty') ?? 0;

        $memberInventoryTotals = [
            'total_distributed' => $totalMemberDistributed,
            'total_used' => $totalMemberUsed,
            'total_available' => max(0, $totalMemberDistributed - $totalMemberUsed),
            'total_distributed_value' => $memberDistributedValue,
            'total_used_value' => $memberUsedValue,
            'total_available_value' => max(0, $memberDistributedValue - $memberUsedValue),
        ];

        return [
            'user' => $user,
            'subaccounts' => $subaccounts,
            'approvedInventory' => $approvedInventory,
            'mainInventoryTotals' => $mainInventoryTotals,
            'subaccountInventoryTotals' => $subaccountInventoryTotals,
            'subaccountReports' => $subaccountReports,
            'clientMembers' => $clientMembers,
            'memberReports' => $memberReports,
            'memberInventoryTotals' => $memberInventoryTotals,
        ];
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $reportData = $this->prepareAccountReportData($user, $dateFrom, $dateTo);

        return view('client.account', $reportData);
    }

    public function generateReportPdf(Request $request)
    {
        $user = auth()->user();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $reportData = $this->prepareAccountReportData($user, $dateFrom, $dateTo);

        $pdf = new Dompdf();
        $pdf->set_option('isRemoteEnabled', true);
        $pdf->set_option('isHtml5ParserEnabled', true);
        $pdf->set_option('isFontSubsettingEnabled', true);
        $pdf->set_option('enablePhp', true);
        $pdf->set_option('enableJavascript', true);
        $pdf->setPaper('a4', 'portrait');
        
        // Get the HTML content
        $html = view('client.account-report-pdf', $reportData)->render();
        
        // Set base path to help resolve relative paths
        $pdf->set_option('chroot', base_path());
        
        $pdf->loadHtml($html);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="inventory-report.pdf"',
        ]);
    }

    public function updateEmail(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
        ]);

        $user->email = $validated['email'];
        $user->save();

        return redirect()->route('client.account')->with('success', 'Email updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
            'new_password_confirmation' => 'required',
        ]);

        $user = auth()->user();

        // Check current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->route('client.account')->with('error', 'Current password is incorrect.');
        }

        // Update password
        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return redirect()->route('client.account')->with('success', 'Password updated successfully.');
    }

    public function distributeToSubaccounts(Request $request)
    {
        $validated = $request->validate([
            'subaccount_id' => 'required|exists:client_subaccounts,id',
            'stock_request_item_id' => 'required|exists:stock_request_items,id',
            'allocated_qty' => 'required|integer|min:1',
        ]);

        $subaccount = ClientSubaccount::findOrFail($validated['subaccount_id']);
        if ($subaccount->client_user_id !== Auth::id()) {
            abort(403);
        }

        $item = StockRequestItem::with('request')->findOrFail($validated['stock_request_item_id']);
        if ($item->request->client_id !== Auth::id()) {
            abort(403);
        }

        // Check remaining inventory (approved - already distributed)
        $distributed = $item->distributed_qty ?? 0;
        $remaining = (int)$item->approved_qty - (int)$distributed;
        if ($validated['allocated_qty'] > $remaining) {
            return redirect()->route('client.account', ['tab' => 'distribution'])->with('error', 'Not enough inventory available for distribution.');
        }

        // Create allocation for the subaccount
        $allocation = ClientSubaccountAllocation::firstOrNew([
            'subaccount_id' => $subaccount->id,
            'stock_request_item_id' => $item->id,
        ]);
        $allocation->allocated_qty = ($allocation->allocated_qty ?? 0) + $validated['allocated_qty'];
        $allocation->save();

        // Deduct from My Inventory by updating distributed_qty
        $item->distributed_qty = ($item->distributed_qty ?? 0) + $validated['allocated_qty'];
        $item->save();

        return redirect()->route('client.account', ['tab' => 'distribution'])->with('success', 'Item distributed to subaccount successfully.');
    }

    public function storeMember(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $user = auth()->user();
        
        // Check if email already exists for this client
        $existingMember = ClientMember::where('client_id', $user->id)
            ->where('email', $validated['email'])
            ->first();
            
        if ($existingMember) {
            return redirect()->route('client.account', ['tab' => 'members'])->with('error', 'A member with this email already exists.');
        }

        $member = ClientMember::create([
            'client_id' => $user->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return redirect()->route('client.account', ['tab' => 'members'])->with('success', 'Member added successfully.');
    }

    public function distributeToMember(Request $request)
    {
        if ($request->expectsJson()) {
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'member_id' => 'required|exists:client_members,id',
                'stock_request_item_id' => 'required|string',
                'distributed_qty' => 'required|integer|min:1',
            ]);

            // Custom validation for stock_request_item_id to handle both regular and direct request items
            $validator->after(function ($validator) use ($request) {
                $stockRequestItemId = $request->input('stock_request_item_id');
                
                if (str_starts_with($stockRequestItemId, 'direct_')) {
                    // Handle direct request items
                    $outboundId = str_replace('direct_', '', $stockRequestItemId);
                    $outbound = \App\Models\Outbound::find($outboundId);
                    
                    if (!$outbound || $outbound->client_id !== Auth::id()) {
                        $validator->errors()->add('stock_request_item_id', 'The selected stock request item id is invalid.');
                    }
                } else {
                    // Handle regular stock request items
                    $stockRequestItem = \App\Models\StockRequestItem::find($stockRequestItemId);
                    
                    if (!$stockRequestItem) {
                        $validator->errors()->add('stock_request_item_id', 'The selected stock request item id is invalid.');
                    } else {
                        // Check if the item belongs to the authenticated client
                        if ($stockRequestItem->request->client_id !== Auth::id()) {
                            $validator->errors()->add('stock_request_item_id', 'The selected stock request item id is invalid.');
                        }
                    }
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            $validated = $validator->validated();
        } else {
            $validated = $request->validate([
                'member_id' => 'required|exists:client_members,id',
                'stock_request_item_id' => 'required|string',
                'distributed_qty' => 'required|integer|min:1',
            ]);
            
            // Apply the same custom validation for non-JSON requests
            $stockRequestItemId = $validated['stock_request_item_id'];
            
            if (str_starts_with($stockRequestItemId, 'direct_')) {
                $outboundId = str_replace('direct_', '', $stockRequestItemId);
                $outbound = \App\Models\Outbound::find($outboundId);
                
                if (!$outbound || $outbound->client_id !== Auth::id()) {
                    return redirect()->route('client.account', ['tab' => 'member-distribution'])->with('error', 'The selected stock request item id is invalid.');
                }
            } else {
                $stockRequestItem = \App\Models\StockRequestItem::find($stockRequestItemId);
                
                if (!$stockRequestItem || $stockRequestItem->request->client_id !== Auth::id()) {
                    return redirect()->route('client.account', ['tab' => 'member-distribution'])->with('error', 'The selected stock request item id is invalid.');
                }
            }
        }

        $member = ClientMember::findOrFail($validated['member_id']);
        if ($member->client_id !== Auth::id()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        // Handle both regular stock request items and direct request items
        $stockRequestItemId = $validated['stock_request_item_id'];
        $isDirectRequest = str_starts_with($stockRequestItemId, 'direct_');
        
        if ($isDirectRequest) {
            // Handle direct request items
            $outboundId = str_replace('direct_', '', $stockRequestItemId);
            $outbound = \App\Models\Outbound::with('stock')->findOrFail($outboundId);
            
            if ($outbound->client_id !== Auth::id()) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }
                abort(403);
            }
            
            $stockId = $outbound->stock_id;
            $item = null; // No stock request item for direct requests
        } else {
            // Handle regular stock request items
            $item = StockRequestItem::with('request')->findOrFail($stockRequestItemId);
            
            if ($item->request->client_id !== Auth::id()) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
                }
                abort(403);
            }
            
            $stockId = $item->stock_id;
        }
        
        // Get the client ID
        $clientId = Auth::id();
        
        // Get all approved inventory items for this client and stock
        $allStockItems = StockRequestItem::with('stock')
            ->whereHas('request', function ($query) use ($clientId) {
                $query->where('client_id', $clientId)
                      ->whereIn('status', ['approved', 'ready_to_receive', 'released']);
            })
            ->where('stock_id', $stockId)
            ->where('approved_qty', '>', 0)
            ->get();

        // Calculate accumulated inventory for this stock
        $totalApproved = 0;
        $totalDistributed = 0;
        
        foreach ($allStockItems as $stockItem) {
            $totalApproved += $stockItem->approved_qty;
            $totalDistributed += ($stockItem->distributed_qty ?? 0);
        }
        
        // Add direct requests for this stock
        $directRequests = Outbound::where('client_id', $clientId)
            ->where('stock_id', $stockId)
            ->where('is_direct_request', true)
            ->where('approval', 'approved')
            ->whereIn('status', ['on process', 'received'])
            ->get();
            
        foreach ($directRequests as $directRequest) {
            $deductedFromDirect = ClientDirectDeduction::where('stock_request_item_id', null)
                ->whereHas('member', function($query) use ($clientId) {
                    $query->where('client_id', $clientId);
                })
                ->where('created_at', '>=', $directRequest->created_at)
                ->sum('deducted_qty');
            
            $totalApproved += $directRequest->total;
            $totalDistributed += $deductedFromDirect;
        }
        
        $remaining = $totalApproved - $totalDistributed;
        
        if ($validated['distributed_qty'] > $remaining) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false, 
                    'message' => "Not enough inventory available for distribution. Available: {$remaining}, Requested: {$validated['distributed_qty']}"
                ]);
            }
            return redirect()->route('client.account', ['tab' => 'member-distribution'])->with('error', "Not enough inventory available for distribution. Available: {$remaining}, Requested: {$validated['distributed_qty']}");
        }

        // Create distribution for the member using the correct table structure
        if ($isDirectRequest) {
            // For direct requests, we need to handle differently since there's no stock_request_item_id
            // Create a direct deduction record instead
            ClientDirectDeduction::create([
                'client_id' => $clientId,
                'stock_request_item_id' => null,
                'member_id' => $member->id,
                'deducted_qty' => $validated['distributed_qty'],
                'reason' => 'Member distribution - ' . $outbound->stock->description ?? 'Direct Request Item',
                'received_by' => Auth::user()->name,
            ]);
        } else {
            // For regular stock request items
            $distribution = ClientMemberDistribution::firstOrNew([
                'member_id' => $member->id,
                'stock_request_item_id' => $item->id,
            ]);
            $distribution->distributed_qty = ($distribution->distributed_qty ?? 0) + $validated['distributed_qty'];
            $distribution->save();
        }

        // Deduct from accumulated inventory by updating distributed_qty across multiple items if needed
        $remainingToDeduct = $validated['distributed_qty'];
        
        // Get all available items for this stock, ordered by creation date (FIFO)
        $availableItems = StockRequestItem::with('stock')
            ->whereHas('request', function ($query) use ($clientId) {
                $query->where('client_id', $clientId)
                      ->whereIn('status', ['approved', 'ready_to_receive', 'released']);
            })
            ->where('stock_id', $stockId)
            ->where('approved_qty', '>', 0)
            ->whereRaw('(approved_qty - COALESCE(distributed_qty, 0)) > 0')
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($availableItems as $availableItem) {
            if ($remainingToDeduct <= 0) break;
            
            $availableFromThisItem = (int)$availableItem->approved_qty - (int)($availableItem->distributed_qty ?? 0);
            $deductFromThisItem = min($availableFromThisItem, $remainingToDeduct);
            
            $availableItem->distributed_qty = ($availableItem->distributed_qty ?? 0) + $deductFromThisItem;
            $availableItem->save();
            
            $remainingToDeduct -= $deductFromThisItem;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true, 
                'message' => 'Item distributed to member successfully.'
            ]);
        }

        return redirect()->route('client.account', ['tab' => 'member-distribution'])->with('success', 'Item distributed to member successfully.');
    }

    public function deductItems(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:client_members,id',
            'distribution_id' => 'required|string',
            'deducted_qty' => 'required|integer|min:1',
        ]);

        $member = ClientMember::findOrFail($validated['member_id']);
        if ($member->client_id !== Auth::id()) {
            abort(403);
        }

        $distributionId = $validated['distribution_id'];
        $isDirectRequest = str_starts_with($distributionId, 'direct_');

        if ($isDirectRequest) {
            // Handle direct request items
            $directDeductionId = str_replace('direct_', '', $distributionId);
            $directDeduction = ClientDirectDeduction::findOrFail($directDeductionId);
            
            if ($directDeduction->member_id !== $member->id || $directDeduction->stock_request_item_id !== null) {
                abort(403);
            }

            // Check if trying to deduct more than available
            if ($validated['deducted_qty'] > $directDeduction->deducted_qty) {
                return redirect()->route('client.account', ['tab' => 'members'])->with('error', 'Cannot deduct more items than available.');
            }

            // Create a usage record to track that this direct request item has been used
            ClientDirectDeduction::create([
                'client_id' => Auth::id(),
                'stock_request_item_id' => null, // This represents usage of a direct request item
                'member_id' => $member->id,
                'deducted_qty' => $validated['deducted_qty'],
                'reason' => 'Used from direct request - ' . $directDeduction->reason ?? 'Direct Request Item',
                'received_by' => $validated['received_by'] ?? Auth::user()->name,
            ]);

            // Update the original direct deduction to show it's been used
            $directDeduction->deducted_qty = $directDeduction->deducted_qty - $validated['deducted_qty'];
            if ($directDeduction->deducted_qty <= 0) {
                $directDeduction->delete();
            } else {
                $directDeduction->save();
            }

        } else {
            // Handle regular distribution items
            $distribution = ClientMemberDistribution::findOrFail($distributionId);
            if ($distribution->member_id !== $member->id) {
                abort(403);
            }

            // Check if trying to deduct more than available
            $availableQty = $distribution->distributed_qty - ($distribution->used_qty ?? 0);
            if ($validated['deducted_qty'] > $availableQty) {
                return redirect()->route('client.account', ['tab' => 'members'])->with('error', 'Cannot deduct more items than available.');
            }

            // Create a direct deduction record for Transaction History
            ClientDirectDeduction::create([
                'client_id' => Auth::id(),
                'stock_request_item_id' => $distribution->stockRequestItem->id,
                'member_id' => $member->id,
                'deducted_qty' => $validated['deducted_qty'],
                'reason' => 'Member inventory deduction - ' . $distribution->stockRequestItem->stock->description ?? 'Item',
                'received_by' => $validated['received_by'] ?? Auth::user()->name,
            ]);

            // Update used_qty
            $distribution->used_qty = ($distribution->used_qty ?? 0) + $validated['deducted_qty'];
            $distribution->save();
        }

        return redirect()->route('client.account', ['tab' => 'members'])->with('success', 'Items deducted successfully.');
    }

    public function updateMember(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $member = ClientMember::where('client_id', Auth::id())->findOrFail($id);
        
        // Check if email is being changed and if it conflicts with another member
        if ($validated['email'] !== $member->email) {
            $existingMember = ClientMember::where('client_id', Auth::id())
                ->where('email', $validated['email'])
                ->where('id', '!=', $id)
                ->first();
                
            if ($existingMember) {
                return redirect()->route('client.account', ['tab' => 'members'])->with('error', 'A member with this email already exists.');
            }
        }

        $member->update($validated);

        return redirect()->route('client.account', ['tab' => 'members'])->with('success', 'Member information updated successfully.');
    }

    public function destroyMember($id)
    {
        $member = ClientMember::where('client_id', Auth::id())->findOrFail($id);
        
        // Delete associated distributions and deductions
        $member->distributions()->delete();
        $member->directDeductions()->delete();
        
        // Delete the member
        $member->delete();

        return redirect()->route('client.account', ['tab' => 'members'])->with('success', 'Member deleted successfully.');
    }
}
