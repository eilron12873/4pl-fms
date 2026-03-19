<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posting_rule_id')->constrained('posting_rules')->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('status')->default('draft'); // draft, review, approved, active, retired
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['posting_rule_id', 'version_number'], 'posting_rule_versions_rule_version_unique');
            $table->index(['posting_rule_id', 'status']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_rule_versions');
    }
};

