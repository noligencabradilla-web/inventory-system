<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockRequestItem;
use App\Models\ClientDirectDeduction;
use App\Models\Outbound;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::where('hidden', 0)->get();
        return view('client.stocks.index', compact('stocks'));
    }

    public function inventory()
    {
        $user = Auth::user();
        
        // Get approved inventory items for this client
        $approvedInventory = StockRequestItem::with(['stock'])
            ->whereHas('request', function($query) use ($user) {
                $query->where('client_id', $user->id)
                      ->whereIn('status', ['approved', 'ready_to_receive', 'released']);
            })
            ->where('approved_qty', '>', 0)
            ->get();

        // Get direct requests for this client
        $directRequests = Outbound::with(['stock'])
            ->where('client_id', $user->id)
            ->where('is_direct_request', true)
            ->where('approval', 'approved')
            ->whereIn('status', ['on process', 'received'])
            ->get();

        // Calculate inventory directly without creating temporary records
        // First, add regular inventory items
        $stockInventoryMap = [];
        
        foreach ($approvedInventory as $item) {
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

        return view('client.inventory', compact('approvedInventory'));
    }

    public function deduct(Request $request)
    {
        $data = $request->validate([
            'stock_request_item_id' => ['required', 'string'],
            'member_id' => ['required', 'integer', 'exists:client_members,id'],
            'deducted_qty' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        $itemId = $data['stock_request_item_id'];
        $stockRequestItem = null;
        $availableInventory = 0;

        // Check if this is a direct request item
        if (str_starts_with($itemId, 'direct_')) {
            $outboundId = str_replace('direct_', '', $itemId);
            $outbound = Outbound::findOrFail($outboundId);
            
            // Verify this outbound belongs to the authenticated client
            if ($outbound->client_id !== $user->id || !$outbound->is_direct_request) {
                return back()->with('error', 'Invalid direct request item.');
            }
            
            // Calculate how much has been deducted from this direct request
            $deductedFromDirect = ClientDirectDeduction::where('stock_request_item_id', null)
                ->whereHas('member', function($query) use ($user) {
                    $query->where('client_id', $user->id);
                })
                ->where('created_at', '>=', $outbound->created_at)
                ->sum('deducted_qty');
            
            $availableInventory = max(0, $outbound->total - $deductedFromDirect);
        } else {
            // Regular stock request item
            $stockRequestItem = StockRequestItem::findOrFail($itemId);
            
            // Verify this item belongs to the authenticated client
            if ($stockRequestItem->request->client_id !== $user->id) {
                return back()->with('error', 'You can only deduct items from your own inventory.');
            }
            
            // Calculate available inventory
            $distributed = $stockRequestItem->distributed_qty ?? 0;
            $availableInventory = max(0, $stockRequestItem->approved_qty - $distributed);
        }

        // Check if enough inventory is available
        if ($data['deducted_qty'] > $availableInventory) {
            return back()->with('error', 'Not enough inventory available. Available: ' . $availableInventory);
        }

        // Verify member belongs to this client
        $member = \App\Models\ClientMember::where('id', $data['member_id'])
            ->where('client_id', $user->id)
            ->firstOrFail();

        DB::transaction(function () use ($data, $stockRequestItem, $user, $itemId) {
            // Create deduction record
            ClientDirectDeduction::create([
                'client_id' => $user->id,
                'stock_request_item_id' => str_starts_with($itemId, 'direct_') ? null : $stockRequestItem->id,
                'member_id' => $data['member_id'],
                'deducted_qty' => $data['deducted_qty'],
                'reason' => $data['reason'] ?? null,
            ]);

            // Update distributed_qty only for regular stock request items
            if ($stockRequestItem) {
                $stockRequestItem->increment('distributed_qty', $data['deducted_qty']);
            }
        });

        return redirect()->route('client.inventory')
            ->with('success', 'Successfully deducted ' . $data['deducted_qty'] . ' item(s) and assigned to member.');
    }
}
