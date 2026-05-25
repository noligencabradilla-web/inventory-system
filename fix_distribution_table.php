<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Check if column exists
    if (!Schema::hasColumn('client_member_distributions', 'stock_request_item_id')) {
        echo "Adding stock_request_item_id column to client_member_distributions table...\n";
        
        DB::statement('ALTER TABLE client_member_distributions ADD COLUMN stock_request_item_id INTEGER NOT NULL DEFAULT 0');
        
        echo "Added stock_request_item_id column\n";
    } else {
        echo "stock_request_item_id column already exists\n";
    }
    
    // Check if distributed_qty column exists
    if (!Schema::hasColumn('client_member_distributions', 'distributed_qty')) {
        echo "Adding distributed_qty column to client_member_distributions table...\n";
        
        DB::statement('ALTER TABLE client_member_distributions ADD COLUMN distributed_qty INTEGER NOT NULL DEFAULT 0');
        
        echo "Added distributed_qty column\n";
    } else {
        echo "distributed_qty column already exists\n";
    }
    
    // Check if used_qty column exists
    if (!Schema::hasColumn('client_member_distributions', 'used_qty')) {
        echo "Adding used_qty column to client_member_distributions table...\n";
        
        DB::statement('ALTER TABLE client_member_distributions ADD COLUMN used_qty INTEGER NOT NULL DEFAULT 0');
        
        echo "Added used_qty column\n";
    } else {
        echo "used_qty column already exists\n";
    }
    
    echo "Database schema updated successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
