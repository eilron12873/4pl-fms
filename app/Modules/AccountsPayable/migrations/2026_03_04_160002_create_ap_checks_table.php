<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_checks', function (Blueprint $table) {
            $table->id();
            $table->string('check_number', 64)->index();
            $table->foreignId('payment_id')->constrained('ap_payments')->cascadeOnDelete();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->date('check_date');
            $table->decimal('amount', 15, 2);
            $table->string('payee', 255);
            $table->string('status', 32)->default('printed')->index();
            $table->timestamps();
            $table->index(['bank_account_id', 'check_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_checks');
    }
};
