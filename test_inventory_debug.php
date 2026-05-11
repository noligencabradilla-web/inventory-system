<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test client inventory calculation
$user = DB::table('users')->where('role', 'client')->first();
echo "Client ID: {$user->id} - {$user->name}\n";

// Get ALL stock request items for this client (debug)
$allItems = DB::table('stock_request_items')
    ->join('stock_requests', 'stock_request_items.stock_request_id', '=', 'stock_requests.id')
    ->where('stock_requests.client_id', $user->id)
    ->select('stock_request_items.*', 'stock_requests.status as request_status')
    ->get();

echo "All items count: {$allItems->count()}\n";

foreach($allItems as $item) {
    echo "Item ID: {$item->id} - Approved: {$item->approved_qty} - Status: {$item->status} - Request Status: {$item->request_status}\n";
}

// Get approved inventory items for this client (current logic)
$approvedInventory = DB::table('stock_request_items')
    ->join('stock_requests', 'stock_request_items.stock_request_id', '=', 'stock_requests.id')
    ->where('stock_requests.client_id', $user->id)
    ->where('stock_request_items.approved_qty', '>', 0)
    ->whereIn('stock_request_items.status', ['approved', 'ready_to_receive', 'released'])
    ->select('stock_request_items.*')
    ->get();

echo "Approved items count: {$approvedInventory->count()}\n";

foreach($approvedInventory as $item) {
    $myInventory = max(0, $item->approved_qty - ($item->distributed_qty ?? 0));
    echo "Item ID: {$item->id} - Approved: {$item->approved_qty} - Distributed: " . ($item->distributed_qty ?? 0) . " - My Inventory: {$myInventory}\n";
}
