<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_payments', function (Blueprint $table) {
            $table->string('payment_method', 32)->default('ach')->after('notes');
            $table->unsignedBigInteger('bank_account_id')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('ap_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'bank_account_id']);
        });
    }
};
