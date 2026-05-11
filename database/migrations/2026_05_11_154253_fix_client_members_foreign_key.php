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
        Schema::table('client_members', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['client_id']);
            
            // Add new foreign key to users table
            $table->foreign('client_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_members', function (Blueprint $table) {
            // Drop foreign key to users table
            $table->dropForeign(['client_id']);
            
            // Add back foreign key to clients table
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->cascadeOnDelete();
        });
    }
};
