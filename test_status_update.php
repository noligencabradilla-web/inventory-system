<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test updating a stock_request_item status to 'released'
$testItemId = 1; // Use an existing item

echo "Testing status update to 'released' for item ID: {$testItemId}\n";

try {
    $updated = DB::table('stock_request_items')
        ->where('id', $testItemId)
        ->update(['status' => 'released', 'updated_at' => now()]);
    
    if ($updated) {
        echo "✅ Successfully updated status to 'released'\n";
        
        // Verify the update
        $item = DB::table('stock_request_items')->where('id', $testItemId)->first();
        echo "Current status: {$item->status}\n";
    } else {
        echo "❌ Failed to update - item not found or no changes needed\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
