<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_member_distributions', function (Blueprint $table) {
            $table->foreignId('member_id')
                ->constrained('client_members')
                ->cascadeOnDelete()
                ->after('id');
                
            $table->foreignId('stock_id')
                ->constrained('stocks')
                ->cascadeOnDelete()
                ->after('member_id');
                
            $table->unsignedInteger('quantity')->default(0)->after('stock_id');
            $table->text('notes')->nullable()->after('quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_member_distributions', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropForeign(['stock_id']);
            $table->dropColumn(['member_id', 'stock_id', 'quantity', 'notes']);
        });
    }
};
