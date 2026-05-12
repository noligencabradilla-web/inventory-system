<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test with client ID 4 (Jan Rei Sibaen) who has multiple requests
$user = DB::table('users')->where('role', 'client')->where('id', 4)->first();
echo "Testing distribution fix for Client ID: {$user->id} - {$user->name}\n";

// Test accumulated inventory calculation (same logic as AccountController)
$stockId = 15; // Stock ID 15 has multiple requests

echo "\n=== Testing Accumulated Inventory for Stock ID {$stockId} ===\n";

// Get all approved inventory items for this client and stock
$allStockItems = DB::table('stock_request_items')
    ->join('stock_requests', 'stock_request_items.stock_request_id', '=', 'stock_requests.id')
    ->where('stock_requests.client_id', $user->id)
    ->whereIn('stock_requests.status', ['approved', 'ready_to_receive', 'released'])
    ->where('stock_request_items.stock_id', $stockId)
    ->where('stock_request_items.approved_qty', '>', 0)
    ->select('stock_request_items.*')
    ->get();

echo "Found {$allStockItems->count()} stock request items for stock {$stockId}\n";

// Calculate accumulated inventory for this stock
$totalApproved = 0;
$totalDistributed = 0;

foreach ($allStockItems as $stockItem) {
    $availableFromThisItem = (int)$stockItem->approved_qty - (int)($stockItem->distributed_qty ?? 0);
    echo "  Item ID {$stockItem->id}: Approved {$stockItem->approved_qty} - Distributed " . ($stockItem->distributed_qty ?? 0) . " = Available {$availableFromThisItem}\n";
    
    $totalApproved += $stockItem->approved_qty;
    $totalDistributed += ($stockItem->distributed_qty ?? 0);
}

// Add direct requests for this stock
$directRequests = DB::table('outbounds')
    ->where('client_id', $user->id)
    ->where('stock_id', $stockId)
    ->where('is_direct_request', true)
    ->where('approval', 'approved')
    ->whereIn('status', ['on process', 'received'])
    ->get();

echo "Found {$directRequests->count()} direct requests for stock {$stockId}\n";

foreach ($directRequests as $directRequest) {
    $deductedFromDirect = DB::table('client_direct_deductions')
        ->where('stock_request_item_id', null)
        ->where('client_id', $user->id)
        ->where('created_at', '>=', $directRequest->created_at)
        ->sum('deducted_qty');
    
    $availableFromDirect = (int)$directRequest->total - (int)$deductedFromDirect;
    echo "  Direct Request ID {$directRequest->id}: Total {$directRequest->total} - Deducted {$deductedFromDirect} = Available {$availableFromDirect}\n";
    
    $totalApproved += $directRequest->total;
    $totalDistributed += $deductedFromDirect;
}

$remaining = $totalApproved - $totalDistributed;

echo "\n=== Summary ===\n";
echo "Total Approved: {$totalApproved}\n";
echo "Total Distributed: {$totalDistributed}\n";
echo "Total Available: {$remaining}\n";

// Test FIFO distribution logic
echo "\n=== Testing FIFO Distribution Logic ===\n";
$distributionQty = 3;
echo "Attempting to distribute {$distributionQty} units...\n";

// Get all available items for this stock, ordered by creation date (FIFO)
$availableItems = DB::table('stock_request_items')
    ->join('stock_requests', 'stock_request_items.stock_request_id', '=', 'stock_requests.id')
    ->where('stock_requests.client_id', $user->id)
    ->whereIn('stock_requests.status', ['approved', 'ready_to_receive', 'released'])
    ->where('stock_request_items.stock_id', $stockId)
    ->where('stock_request_items.approved_qty', '>', 0)
    ->whereRaw('(stock_request_items.approved_qty - COALESCE(stock_request_items.distributed_qty, 0)) > 0')
    ->select('stock_request_items.*')
    ->orderBy('stock_request_items.created_at', 'asc')
    ->get();

$remainingToDeduct = $distributionQty;
$distributedFromItems = [];

foreach ($availableItems as $availableItem) {
    if ($remainingToDeduct <= 0) break;
    
    $availableFromThisItem = (int)$availableItem->approved_qty - (int)($availableItem->distributed_qty ?? 0);
    $deductFromThisItem = min($availableFromThisItem, $remainingToDeduct);
    
    $distributedFromItems[] = [
        'item_id' => $availableItem->id,
        'deducted' => $deductFromThisItem,
        'available_before' => $availableFromThisItem,
        'available_after' => $availableFromThisItem - $deductFromThisItem
    ];
    
    $remainingToDeduct -= $deductFromThisItem;
}

echo "Distribution plan:\n";
foreach ($distributedFromItems as $distribution) {
    echo "  Item ID {$distribution['item_id']}: Deduct {$distribution['deducted']} (was {$distribution['available_before']}, now {$distribution['available_after']})\n";
}

if ($remainingToDeduct > 0) {
    echo "❌ Cannot distribute {$distributionQty} units. Short by {$remainingToDeduct} units.\n";
} else {
    echo "✅ Successfully distributed {$distributionQty} units using FIFO logic.\n";
}
