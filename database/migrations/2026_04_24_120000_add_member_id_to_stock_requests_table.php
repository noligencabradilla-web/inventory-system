<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->foreignId('member_id')
                ->nullable()
                ->constrained('client_members')
                ->nullOnDelete()
                ->after('client_id');

            $table->text('reason')
                ->nullable()
                ->after('member_id');
        });

            }

    public function down(): void
    {
        Schema::table('stock_requests', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropColumn(['member_id', 'reason']);
        });

            }
};
