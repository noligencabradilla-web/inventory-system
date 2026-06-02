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
        Schema::create('inbound_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inbound_id')->constrained("inbounds")->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained("stocks")->cascadeOnDelete();
            $table->foreignId('office_id')->constrained("s_p_offices")->cascadeOnDelete();
            $table->integer("allocation");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inbound_allocations');
    }
};
