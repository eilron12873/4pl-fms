<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costing_saved_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('report_key', 60);
            $table->string('name', 100);
            $table->json('filters')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'report_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_saved_filters');
    }
};

