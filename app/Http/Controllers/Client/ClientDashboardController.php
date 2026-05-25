<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\StockRequest;
use App\Models\ClientMember;
use App\Models\ClientSubaccount;
use App\Models\Outbound;
use App\Models\ClientDirectDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClientDashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->role === 'subaccount') {
            $subaccount = ClientSubaccount::where('user_id', Auth::id())->first();
            if ($subaccount) {
                // For subaccounts, show their dashboard with distributed items
                $distributedItems = $subaccount->allocations()->with(['stockRequestItem.stock', 'members'])->get();
                $totalItems = $distributedItems->sum('allocated_qty');
                $totalValue = $distributedItems->sum('allocated_qty');
                $totalDistributed = $subaccount->distributions->sum('distributed_qty');
                $usedItems = $totalDistributed;
                $availableItems = max(0, $totalItems - $usedItems);
                return view('client.dashboard', [
                    'isSubaccount' => true,
                    'subaccount' => $subaccount,
                    'distributedItems' => $distributedItems,
                    'totalItems' => $totalItems,
                    'totalValue' => $totalValue,
                    'usedItems' => $usedItems,
                    'availableItems' => $availableItems,
                ]);
            }
        }

        // For clients, show received inventory summary
        $receivedOutbounds = Outbound::with('stock')
            ->where('client_id', Auth::id())
            ->where('approval', 'approved')
            ->whereIn('status', ['on process', 'received'])
            ->get();

        $totalReceivedItems = $receivedOutbounds->sum('total');
        $totalReceivedValue = $receivedOutbounds->sum('total');

        // Get direct client members (not subaccount members)
        $clientMembers = ClientMember::where('client_id', Auth::id())
            ->with('distributions.stockRequestItem.stock')
            ->get();
        
        // Get subaccounts summary
        $subaccounts = ClientSubaccount::where('client_user_id', Auth::id())
            ->with(['allocations.stockRequestItem.stock', 'members.distributions.stockRequestItem.stock', 'distributions.stockRequestItem.stock'])
            ->get();
        $subaccountsSummary = $subaccounts->map(function($subaccount) {
            $allocations = $subaccount->allocations;
            $subaccountDistributions = $subaccount->distributions;
            $totalAllocated = $allocations->sum('allocated_qty');
            $totalUsed = $subaccountDistributions->sum('distributed_qty') + $allocations->sum('used_qty');
            $totalAvailable = max(0, $totalAllocated - $totalUsed);
            $totalValue = $allocations->sum('allocated_qty');
            $members = $subaccount->members->map(function($member) use ($subaccountDistributions, $subaccount) {
                $grouped = $member->distributions->groupBy('stock_request_item_id')->map(function($distributions, $stockRequestItemId) use ($subaccountDistributions, $subaccount) {
                    $first = $distributions->first();
                    $stockRequestItem = $first->stockRequestItem;
                    $currentQty = $distributions->sum('distributed_qty');
                    $usedQty = $distributions->sum('used_qty');
                    $originalDistributed = $currentQty + $usedQty;
                    return [
                        'stockRequestItem' => $stockRequestItem,
                        'distributed_qty' => $originalDistributed,
                        'remaining_qty' => $currentQty,
                        'totalValue' => $originalDistributed,
                    ];
                })->values();
                $totalDistributed = $grouped->sum('distributed_qty');
                $totalValue = $grouped->sum('totalValue');
                return [
                    'member' => $member,
                    'items' => $grouped,
                    'totalDistributed' => $totalDistributed,
                    'totalValue' => $totalValue,
                ];
            });
            return [
                'subaccount' => $subaccount,
                'totalAllocated' => $totalAllocated,
                'totalUsed' => $totalUsed,
                'totalAvailable' => $totalAvailable,
                'totalValue' => $totalValue,
                'allocations' => $allocations,
                'members' => $members,
            ];
        });

        // Member Performance Analytics
        $memberPerformance = $this->calculateMemberPerformance($clientMembers);

        return view('client.dashboard', [
            'isSubaccount' => false,
            'totalReceivedItems' => $totalReceivedItems,
            'totalReceivedValue' => $totalReceivedValue,
            'receivedOutbounds' => $receivedOutbounds,
            'clientMembers' => $clientMembers,
            'subaccountsSummary' => $subaccountsSummary,
            'memberPerformance' => $memberPerformance,
        ]);
    }

    public function summary(\Illuminate\Http\Request $request)
    {
        $type = trim((string)$request->query('type', 'all'));
        $memberId = trim((string)$request->query('member', ''));

        // Initialize empty collections
        $requests = collect();
        $directDeductions = collect();
        $memberDistributions = collect();
        $distributionDirectRequests = collect();
        $directRequests = collect();
        $urgentOutbounds = collect();

        // Filter based on transaction type
        if ($type === 'all' || $type === 'request') {
            $requestsQuery = StockRequest::with(['items.stock'])
                ->where('client_id', Auth::id());

            if ($memberId !== '') {
                $requestsQuery->where('member_id', $memberId);
            }

            $requests = $requestsQuery->latest()->get();
        }

        if ($type === 'all' || $type === 'deduction') {
            $directDeductionsQuery = ClientDirectDeduction::with(['stockRequestItem.stock', 'member'])
                ->where('client_id', Auth::id())
                ->where(function ($query) {
                    $query->whereNotNull('stock_request_item_id')
                        ->orWhere('reason', 'not like', 'Member distribution - %');
                });

            if ($memberId !== '') {
                $directDeductionsQuery->where('member_id', $memberId);
            }

            $directDeductions = $directDeductionsQuery->latest()->get();
        }

        if ($type === 'all' || $type === 'distribution') {
            $memberDistributionsQuery = \App\Models\ClientMemberDistribution::with(['stockRequestItem.stock', 'member'])
                ->whereHas('member', function ($query) {
                    $query->where('client_id', Auth::id());
                });

            if ($memberId !== '') {
                $memberDistributionsQuery->where('member_id', $memberId);
            }

            $memberDistributions = $memberDistributionsQuery->latest()->get();

            $distributionDirectRequestsQuery = ClientDirectDeduction::with(['member'])
                ->where('client_id', Auth::id())
                ->whereNull('stock_request_item_id')
                ->where('reason', 'like', 'Member distribution - %');

            if ($memberId !== '') {
                $distributionDirectRequestsQuery->where('member_id', $memberId);
            }

            $distributionDirectRequests = $distributionDirectRequestsQuery->latest()->get();
        }

        if ($type === 'all' || $type === 'direct') {
            $directRequestsQuery = Outbound::with(['stock', 'member', 'client'])
                ->where('is_direct_request', true)
                ->where('client_id', Auth::id());

            if ($memberId !== '') {
                $directRequestsQuery->where('member_id', $memberId);
            }

            $directRequests = $directRequestsQuery->latest()->get();
        }

        if ($type === 'all' || $type === 'urgent') {
            $urgentOutboundsQuery = Outbound::with(['stock', 'urgentRecipient'])
                ->where('is_urgent_outbound', true)
                ->where('client_id', Auth::id());

            if ($memberId !== '') {
                $urgentOutboundsQuery->whereHas('member', function($query) use ($memberId) {
                    $query->where('id', $memberId);
                });
            }

            $urgentOutbounds = $urgentOutboundsQuery->latest()->get();
        }

        // Get counts for all requests (unfiltered)
        $allRequests = StockRequest::with(['items.stock'])
            ->where('client_id', Auth::id())
            ->latest()
            ->get();
        $counts = $allRequests->groupBy('status')->map->count();

        // Get client members for dropdown
        $clientMembers = \App\Models\ClientMember::where('client_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('client.summary', [
            'counts' => $counts,
            'requests' => $requests,
            'directDeductions' => $directDeductions,
            'memberDistributions' => $memberDistributions,
            'distributionDirectRequests' => $distributionDirectRequests,
            'directRequests' => $directRequests,
            'urgentOutbounds' => $urgentOutbounds,
            'clientMembers' => $clientMembers,
            'type' => $type,
            'memberId' => $memberId,
        ]);
    }

    public function inventory()
    {
        $outbounds = Outbound::with(['stock'])
            ->where('client_id', Auth::id())
            ->where('approval', 'approved')
            ->whereIn('status', ['on process', 'received'])
            ->latest()
            ->get();

        // Get urgent outbounds for this client (both member and client urgent requests)
        $urgentOutbounds = Outbound::with(['stock', 'member', 'urgentRecipient'])
            ->where('is_urgent_outbound', true)
            ->where(function($query) {
                $query->where('client_id', Auth::id())
                      ->orWhereHas('member', function($q) {
                          $q->where('client_id', Auth::id());
                      });
            })
            ->where('approval', 'approved')
            ->whereIn('status', ['on process', 'received'])
            ->latest()
            ->get();

        $approvedInventory = StockRequestItem::with('stock')
            ->whereHas('request', function ($query) {
                $query->where('client_id', Auth::id())
                    ->whereIn('status', ['approved', 'ready_to_receive', 'released']);
            })
            ->where('approved_qty', '>', 0)
            ->withSum('allocations', 'allocated_qty')
            ->get();

        // Get direct requests for this client
        $directRequests = Outbound::with(['stock'])
            ->where('client_id', Auth::id())
            ->where('is_direct_request', true)
            ->where('approval', 'approved')
            ->whereIn('status', ['on process', 'received'])
            ->get();

        // Aggregate inventory items by stock (regular + direct requests)
        $stockInventoryMap = [];
        
        // First, add regular inventory items
        foreach ($approvedInventory as $item) {
            $stockId = $item->stock->id;
            $myInventory = max(0, ($item->approved_qty ?? 0) - ($item->distributed_qty ?? 0));
            
            $stockInventoryMap[$stockId] = (object)[
                'id' => $item->id,
                'stock' => $item->stock,
                'approved_qty' => $item->approved_qty,
                'distributed_qty' => $item->distributed_qty,
                'my_inventory' => $myInventory,
                'type' => $item->type ?? 'inventory'
            ];
        }
        
        // Then, add direct request quantities to existing items or create new entries
        foreach ($directRequests as $directRequest) {
            $stockId = $directRequest->stock->id;
            
            if (isset($stockInventoryMap[$stockId])) {
                // Add to existing item
                $stockInventoryMap[$stockId]->my_inventory += $directRequest->total;
                $stockInventoryMap[$stockId]->approved_qty += $directRequest->total;
            } else {
                // Create new entry for direct request only item
                $stockInventoryMap[$stockId] = (object)[
                    'id' => 'direct_' . $directRequest->id,
                    'stock' => $directRequest->stock,
                    'approved_qty' => $directRequest->total,
                    'distributed_qty' => 0,
                    'my_inventory' => $directRequest->total,
                    'type' => 'direct_request',
                    'outbound_id' => $directRequest->id
                ];
            }
        }
        
        // Convert back to collection
        $approvedInventory = collect(array_values($stockInventoryMap));

        // Add urgent outbounds to the approved inventory collection (urgent items remain separate)
        foreach ($urgentOutbounds as $urgent) {
            $approvedInventory->push((object)[
                'id' => 'urgent_' . $urgent->id,
                'stock' => $urgent->stock,
                'approved_qty' => $urgent->total,
                'distributed_qty' => 0,
                'my_inventory' => $urgent->total,
                'type' => 'urgent',
                'source' => 'urgent_outbound'
            ]);
        }

        return view('client.inventory', [
            'outbounds' => $outbounds,
            'urgentOutbounds' => $urgentOutbounds,
            'approvedInventory' => $approvedInventory,
        ]);
    }

    private function calculateMemberPerformance($clientMembers)
    {
        // Get all stock requests with member assignments
        $stockRequests = StockRequest::with(['items.stock'])
            ->where('client_id', Auth::id())
            ->whereNotNull('member_id')
            ->get();

        // Get all direct deductions
        $directDeductions = ClientDirectDeduction::with(['stockRequestItem.stock', 'member'])
            ->where('client_id', Auth::id())
            ->get();

        // Calculate request frequency
        $requestFrequency = $stockRequests->groupBy('member_id')->map(function ($requests) {
            return [
                'member_id' => $requests->first()->member_id,
                'request_count' => $requests->count(),
                'total_requested_qty' => $requests->sum(function ($request) {
                    return $request->items->sum('requested_qty');
                }),
                'total_approved_qty' => $requests->sum(function ($request) {
                    return $request->items->sum('approved_qty');
                }),
            ];
        })->sortByDesc('request_count');

        // Calculate usage statistics based on member distributions (used_qty)
        $memberDistributions = \App\Models\ClientMemberDistribution::with(['stockRequestItem.stock', 'member'])
            ->whereHas('member', function($query) {
                $query->where('client_id', Auth::id());
            })
            ->get();

        // Get direct request usage records (not original direct items)
        $directUsageRecords = ClientDirectDeduction::with(['member'])
            ->where('client_id', Auth::id())
            ->where('stock_request_item_id', null) // Only direct request items
            ->where(function($query) {
                $query->where('reason', 'like', '%Used from direct request%')
                      ->orWhere('reason', 'like', '%Member inventory deduction%');
            })
            ->get();

        // Combine usage data from both sources
        $combinedUsageData = [];
        
        // Process regular distributions
        foreach ($memberDistributions->groupBy('member_id') as $memberId => $distributions) {
            $combinedUsageData[$memberId] = [
                'member_id' => $memberId,
                'distribution_count' => $distributions->count(),
                'total_distributed_qty' => $distributions->sum('distributed_qty'),
                'total_used_qty' => $distributions->sum('used_qty'),
                'total_used_value' => $distributions->sum('used_qty') ?? 0,
                'last_activity' => $distributions->max('created_at'),
            ];
        }
        
        // Add direct usage records
        foreach ($directUsageRecords->groupBy('member_id') as $memberId => $directUsages) {
            if (isset($combinedUsageData[$memberId])) {
                // Add to existing member data
                $combinedUsageData[$memberId]['total_used_qty'] += $directUsages->sum('deducted_qty');
                $combinedUsageData[$memberId]['total_used_value'] += $directUsages->sum('deducted_qty');
                // Update last activity if direct usage is more recent
                $directLastActivity = $directUsages->max('created_at');
                if ($directLastActivity > $combinedUsageData[$memberId]['last_activity']) {
                    $combinedUsageData[$memberId]['last_activity'] = $directLastActivity;
                }
            } else {
                // Create new entry for member with only direct usage
                $combinedUsageData[$memberId] = [
                    'member_id' => $memberId,
                    'distribution_count' => 0,
                    'total_distributed_qty' => 0,
                    'total_used_qty' => $directUsages->sum('deducted_qty'),
                    'total_used_value' => $directUsages->sum('deducted_qty'),
                    'last_activity' => $directUsages->max('created_at'),
                ];
            }
        }

        $usageStats = collect($combinedUsageData)->sortByDesc('total_used_qty');

        // Combine member data with performance metrics
        $performanceData = $clientMembers->map(function ($member) use ($requestFrequency, $usageStats) {
            $requestStats = $requestFrequency->firstWhere('member_id', $member->id);
            $usageData = $usageStats->firstWhere('member_id', $member->id);

            return [
                'member' => $member,
                'request_count' => $requestStats['request_count'] ?? 0,
                'total_requested_qty' => $requestStats['total_requested_qty'] ?? 0,
                'total_approved_qty' => $requestStats['total_approved_qty'] ?? 0,
                'distribution_count' => $usageData['distribution_count'] ?? 0,
                'total_distributed_qty' => $usageData['total_distributed_qty'] ?? 0,
                'total_used_qty' => $usageData['total_used_qty'] ?? 0,
                'total_used_value' => $usageData['total_used_value'] ?? 0,
                'last_activity' => $usageData['last_activity'] ?? null,
                'performance_score' => $this->calculatePerformanceScore($requestStats, $usageData),
            ];
        });

        return [
            'most_frequent_requestors' => $performanceData->sortByDesc('request_count')->take(5)->values(),
            'heaviest_users' => $performanceData->sortByDesc('total_used_qty')->take(5)->values(),
            'top_value_users' => $performanceData->sortByDesc('total_used_value')->take(5)->values(),
            'most_active_members' => $performanceData->sortByDesc('performance_score')->take(5)->values(),
        ];
    }

    private function calculatePerformanceScore($requestStats, $usageData)
    {
        $requestScore = ($requestStats['request_count'] ?? 0) * 10;
        $usageScore = ($usageData['total_used_qty'] ?? 0) * 5;
        $valueScore = ($usageData['total_used_value'] ?? 0) * 0.01;
        
        return $requestScore + $usageScore + $valueScore;
    }
}
