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
        Schema::create('stock_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_allocation_id')->constrained("inbound_allocations")->cascadeOnDelete();
            $table->integer("allocation");
            $table->integer("outstanding"); // remaining qty for allocation
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_allocations');
    }
};
