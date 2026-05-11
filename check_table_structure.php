<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Checking client_member_distributions table structure ===\n";
$columns = Schema::getColumnListing('client_member_distributions');
print_r($columns);

echo "\n=== Checking if stock_request_item_id column exists ===\n";
if (Schema::hasColumn('client_member_distributions', 'stock_request_item_id')) {
    echo "✅ stock_request_item_id column exists\n";
} else {
    echo "❌ stock_request_item_id column does NOT exist\n";
}

echo "\n=== Checking if stock_id column exists ===\n";
if (Schema::hasColumn('client_member_distributions', 'stock_id')) {
    echo "✅ stock_id column exists\n";
} else {
    echo "❌ stock_id column does NOT exist\n";
}

echo "\n=== Testing ClientMemberDistribution model query ===\n";
try {
    $distribution = \App\Models\ClientMemberDistribution::where('member_id', 3)
        ->where('stock_request_item_id', 3)
        ->first();
    echo "✅ Query executed successfully\n";
    if ($distribution) {
        echo "Found distribution with ID: " . $distribution->id . "\n";
    } else {
        echo "No distribution found\n";
    }
} catch (\Exception $e) {
    echo "❌ Query failed: " . $e->getMessage() . "\n";
}
