<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // DB::statement("ALTER TABLE stock_request_items MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'released', 'ready_to_receive') NOT NULL DEFAULT 'pending'");
        Schema::table('stock_request_items', function (Blueprint $table) {
            $table->dropColumn('status');
            // Change enum to include new statuses: pending, sent, completed, approved, rejected
            $table->enum('status', ['pending', 'approved', 'rejected', 'released', 'ready_to_receive'])
                ->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DB::statement("ALTER TABLE stock_request_items MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        Schema::table('stock_request_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
