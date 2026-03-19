<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('name');
            $table->string('tax_id', 50)->nullable()->after('category');
            $table->string('bank_name', 150)->nullable()->after('notes');
            $table->string('bank_account_number', 100)->nullable()->after('bank_name');
            $table->string('bank_swift_code', 50)->nullable()->after('bank_account_number');
            $table->string('preferred_payment_method', 32)->nullable()->after('bank_swift_code');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'tax_id',
                'bank_name',
                'bank_account_number',
                'bank_swift_code',
                'preferred_payment_method',
            ]);
        });
    }
};

