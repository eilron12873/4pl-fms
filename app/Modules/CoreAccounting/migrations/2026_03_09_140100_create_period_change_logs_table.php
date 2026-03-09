<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('periods')->cascadeOnDelete();
            $table->string('action'); // opened, closed
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['period_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_change_logs');
    }
};

