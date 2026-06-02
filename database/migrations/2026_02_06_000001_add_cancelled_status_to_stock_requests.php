<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // For MySQL, we need to modify the enum column
        // Using raw SQL to add the 'cancelled' value to the existing enum
        // DB::statement("ALTER TABLE stock_requests MODIFY status ENUM('pending', 'approved', 'rejected', 'ready_to_receive', 'released', 'cancelled') DEFAULT 'pending'");
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropColumn('status');
            // Change enum to include new statuses: pending, sent, completed, approved, rejected
            $table->enum('status', ['pending', 'sent', 'completed', 'approved', 'rejected'])
                ->default('pending');
        });
    }

    public function down(): void
    {
        // Revert to original enum without 'cancelled'
        // DB::statement("ALTER TABLE stock_requests MODIFY status ENUM('pending', 'approved', 'rejected', 'ready_to_receive', 'released') DEFAULT 'pending'");
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
