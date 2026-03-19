<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costing_allocation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('costing_allocation_rules')->cascadeOnDelete();
            $table->date('allocation_date')->index();
            $table->string('target_dimension', 40);
            $table->unsignedBigInteger('target_id');
            $table->decimal('allocated_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['target_dimension', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_allocation_results');
    }
};

