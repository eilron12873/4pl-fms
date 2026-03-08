<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('statement_date');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('reference', 255)->nullable();
            $table->string('bank_sequence', 100)->nullable();
            $table->unsignedBigInteger('bank_transaction_id')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->index(['bank_account_id', 'statement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_lines');
    }
};
