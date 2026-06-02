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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('s_p_office_id')
                ->nullable()
                ->after('role')
                ->constrained('s_p_offices')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 's_p_office_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['s_p_office_id']);
                $table->dropColumn('s_p_office_id');
            });
        }
    }
};
