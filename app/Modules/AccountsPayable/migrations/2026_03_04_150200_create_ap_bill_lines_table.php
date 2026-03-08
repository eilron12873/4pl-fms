<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('ap_bills')->cascadeOnDelete();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->string('source_type', 50)->nullable();
            $table->string('source_reference', 255)->nullable();
            $table->string('description');
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->timestamps();
            $table->index('bill_id');
            $table->index('journal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_bill_lines');
    }
};
