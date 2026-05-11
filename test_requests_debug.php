<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== STOCK REQUESTS TABLE ===\n";
$requests = DB::table('stock_requests')->get();
echo "Total requests: {$requests->count()}\n";

foreach($requests as $req) {
    echo "Request ID: {$req->id} - Client ID: {$req->client_id} - Status: {$req->status}\n";
}

echo "\n=== STOCK REQUEST ITEMS TABLE ===\n";
$items = DB::table('stock_request_items')->get();
echo "Total items: {$items->count()}\n";

foreach($items as $item) {
    echo "Item ID: {$item->id} - Request ID: {$item->stock_request_id} - Approved: {$item->approved_qty} - Status: {$item->status}\n";
}

echo "\n=== CLIENTS ===\n";
$clients = DB::table('users')->where('role', 'client')->get();
foreach($clients as $client) {
    echo "Client ID: {$client->id} - Name: {$client->name}\n";
}
