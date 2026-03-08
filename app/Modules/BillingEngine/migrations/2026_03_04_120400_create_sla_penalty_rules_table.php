<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sla_penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('penalty_type', 50); // late_delivery, late_pod, etc.
            $table->string('amount_type', 20)->default('fixed'); // fixed, percent
            $table->decimal('amount', 15, 4)->default(0);
            $table->json('conditions')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();

            $table->index(['contract_id', 'penalty_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_penalty_rules');
    }
};
