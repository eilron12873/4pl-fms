<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_close_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('checks');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['period_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_close_evidences');
    }
};

