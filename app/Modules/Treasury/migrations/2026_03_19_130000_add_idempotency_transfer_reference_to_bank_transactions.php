<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->unique('bank_transactions_idempotency_key_unique');
            $table->string('transfer_group_reference', 255)->nullable()->index('bank_transactions_transfer_group_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropUnique('bank_transactions_idempotency_key_unique');
            $table->dropIndex('bank_transactions_transfer_group_reference_idx');
            $table->dropColumn(['idempotency_key', 'transfer_group_reference']);
        });
    }
};

