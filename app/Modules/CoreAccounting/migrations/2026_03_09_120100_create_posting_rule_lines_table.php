<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_rule_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posting_rule_id')->constrained('posting_rules')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->enum('entry_type', ['debit', 'credit']);
            $table->string('amount_source');
            $table->json('dimension_source')->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();

            $table->index(['posting_rule_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_rule_lines');
    }
};

