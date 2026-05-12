<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Member Distribution Fix ===\n";

// Test with client ID 4 (Jan Rei Sibaen)
$user = DB::table('users')->where('role', 'client')->where('id', 4)->first();
echo "Client ID: {$user->id} - {$user->name}\n";

// Get a member for testing
$member = DB::table('client_members')->where('client_id', $user->id)->first();
if (!$member) {
    echo "❌ No members found for testing. Creating a test member...\n";
    $memberId = DB::table('client_members')->insertGetId([
        'client_id' => $user->id,
        'name' => 'Test Member',
        'email' => 'test@example.com',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $member = (object)['id' => $memberId, 'name' => 'Test Member', 'email' => 'test@example.com'];
    echo "✅ Created test member with ID: {$memberId}\n";
} else {
    echo "✅ Found member: {$member->name} (ID: {$member->id})\n";
}

// Get an available stock request item for testing
$availableItem = DB::table('stock_request_items')
    ->join('stock_requests', 'stock_request_items.stock_request_id', '=', 'stock_requests.id')
    ->where('stock_requests.client_id', $user->id)
    ->where('stock_request_items.approved_qty', '>', 0)
    ->whereRaw('(stock_request_items.approved_qty - COALESCE(stock_request_items.distributed_qty, 0)) > 0')
    ->select('stock_request_items.*')
    ->first();

if (!$availableItem) {
    echo "❌ No available items for distribution\n";
    exit;
}

echo "✅ Found available item ID: {$availableItem->id}\n";
echo "   Approved: {$availableItem->approved_qty}, Distributed: " . ($availableItem->distributed_qty ?: 0) . "\n";

// Test creating a ClientMemberDistribution record
echo "\n=== Testing ClientMemberDistribution Creation ===\n";

try {
    $distribution = new \App\Models\ClientMemberDistribution();
    $distribution->member_id = $member->id;
    $distribution->stock_request_item_id = $availableItem->id;
    $distribution->distributed_qty = 1;
    $distribution->save();
    
    echo "✅ Successfully created distribution record with ID: {$distribution->id}\n";
    
    // Test the relationships
    echo "\n=== Testing Relationships ===\n";
    
    // Test stockRequestItem relationship
    $stockRequestItem = $distribution->stockRequestItem;
    if ($stockRequestItem) {
        echo "✅ StockRequestItem relationship works: Item ID {$stockRequestItem->id}\n";
    } else {
        echo "❌ StockRequestItem relationship failed\n";
    }
    
    // Test stock relationship (through StockRequestItem)
    $stock = $distribution->stock;
    if ($stock) {
        echo "✅ Stock relationship works: " . ($stock->description ?: $stock->name) . "\n";
    } else {
        echo "❌ Stock relationship failed\n";
    }
    
    // Test member relationship
    $memberRelation = $distribution->member;
    if ($memberRelation) {
        echo "✅ Member relationship works: {$memberRelation->name}\n";
    } else {
        echo "❌ Member relationship failed\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Failed to create distribution: " . $e->getMessage() . "\n";
}

echo "\n=== Testing Distribution Query ===\n";

// Test the query that was failing
try {
    $existingDistribution = \App\Models\ClientMemberDistribution::where('member_id', $member->id)
        ->where('stock_request_item_id', $availableItem->id)
        ->first();
    
    if ($existingDistribution) {
        echo "✅ Query works! Found distribution with ID: {$existingDistribution->id}\n";
    } else {
        echo "✅ Query works! No existing distribution found (as expected)\n";
    }
} catch (\Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "\n";
}
