<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costing_allocation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('rule_type', 40);
            $table->string('target_dimension', 40);
            $table->string('source_dimension', 40)->nullable();
            $table->decimal('fixed_amount', 15, 2)->nullable();
            $table->decimal('percentage', 8, 4)->nullable();
            $table->json('meta')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['rule_type', 'target_dimension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_allocation_rules');
    }
};

