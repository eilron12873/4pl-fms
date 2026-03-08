<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('billing_clients')->cascadeOnDelete();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_payments');
    }
};
