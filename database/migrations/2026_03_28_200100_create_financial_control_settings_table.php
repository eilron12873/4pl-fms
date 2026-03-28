<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_control_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('max_backdating_days')->nullable()->comment('Null = unlimited');
            $table->boolean('allow_manual_journals')->default(true);
            $table->json('thresholds')->nullable();
            $table->timestamps();
        });

        DB::table('financial_control_settings')->insert([
            'max_backdating_days' => null,
            'allow_manual_journals' => true,
            'thresholds' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_control_settings');
    }
};
