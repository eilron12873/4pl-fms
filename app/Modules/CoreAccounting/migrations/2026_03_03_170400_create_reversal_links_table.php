<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reversal_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('reversal_journal_id')->constrained('journals')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reversal_links');
    }
};

