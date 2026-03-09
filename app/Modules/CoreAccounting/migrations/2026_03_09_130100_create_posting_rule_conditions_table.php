<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posting_rule_id')->constrained('posting_rules')->cascadeOnDelete();
            $table->string('field_name');
            $table->string('operator', 16);
            $table->string('comparison_value');
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();

            $table->index(['posting_rule_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_rule_conditions');
    }
};

