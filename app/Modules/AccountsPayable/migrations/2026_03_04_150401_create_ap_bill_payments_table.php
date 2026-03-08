<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('ap_bills')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('ap_payments')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();

            $table->unique(['bill_id', 'payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_bill_payments');
    }
};
