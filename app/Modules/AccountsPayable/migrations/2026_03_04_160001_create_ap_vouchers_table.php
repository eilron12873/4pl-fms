<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number', 64)->unique();
            $table->foreignId('payment_id')->constrained('ap_payments')->cascadeOnDelete();
            $table->date('voucher_date');
            $table->timestamps();
            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_vouchers');
    }
};
