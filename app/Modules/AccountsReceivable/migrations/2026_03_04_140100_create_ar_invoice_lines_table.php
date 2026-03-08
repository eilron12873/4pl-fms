<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('ar_invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->string('source_type', 50)->nullable();
            $table->string('source_reference', 255)->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('journal_id');
            $table->index(['source_type', 'source_reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_invoice_lines');
    }
};
