<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test with client ID 4 (Jan Rei Sibaen) who has multiple requests
$user = DB::table('users')->where('role', 'client')->where('id', 4)->first();
echo "Client ID: {$user->id} - {$user->name}\n";

// Get all approved inventory items for this client (grouped by stock_id)
$approvedInventory = DB::table('stock_request_items')
    ->join('stock_requests', 'stock_request_items.stock_request_id', '=', 'stock_requests.id')
    ->join('stocks', 'stock_request_items.stock_id', '=', 'stocks.id')
    ->where('stock_requests.client_id', $user->id)
    ->where('stock_request_items.approved_qty', '>', 0)
    ->whereIn('stock_request_items.status', ['approved', 'ready_to_receive', 'released'])
    ->select('stock_request_items.*', 'stocks.id_no', 'stocks.description')
    ->orderBy('stock_request_items.stock_id')
    ->get();

echo "All approved items (raw):\n";
foreach($approvedInventory as $item) {
    $myInventory = max(0, $item->approved_qty - ($item->distributed_qty ?? 0));
    echo "  Stock ID {$item->stock_id} ({$item->id_no}): Approved {$item->approved_qty} - Distributed " . ($item->distributed_qty ?? 0) . " = Available {$myInventory}\n";
}

// Test the accumulation logic
$stockInventoryMap = [];
foreach ($approvedInventory as $item) {
    $stockId = $item->stock_id;
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
            'stock_id' => $item->stock_id,
            'id_no' => $item->id_no,
            'description' => $item->description,
            'approved_qty' => $item->approved_qty,
            'distributed_qty' => $item->distributed_qty,
            'my_inventory' => $myInventory,
            'type' => 'inventory'
        ];
    }
}

echo "\nAccumulated inventory (after fix):\n";
foreach($stockInventoryMap as $stockId => $inventory) {
    echo "  Stock ID {$stockId} ({$inventory->id_no}): Total Approved {$inventory->approved_qty} - Total Distributed {$inventory->distributed_qty} = Total Available {$inventory->my_inventory}\n";
}
