<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_invoice_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('ar_invoices')->cascadeOnDelete();
            $table->string('type', 20); // credit_note, debit_note
            $table->string('adjustment_number', 50)->nullable()->unique();
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->date('adjustment_date');
            $table->timestamps();

            $table->index(['invoice_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_invoice_adjustments');
    }
};
