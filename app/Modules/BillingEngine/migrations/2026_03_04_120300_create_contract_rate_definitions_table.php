<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_rate_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('rate_type', 50); // per_pallet_day, per_cbm, per_kg, per_trip, per_route, per_container, fixed
            $table->decimal('unit_price', 15, 4)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->decimal('min_quantity', 15, 4)->nullable();
            $table->decimal('max_quantity', 15, 4)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['contract_id', 'rate_type']);
            $table->index(['contract_id', 'rate_type', 'min_quantity', 'max_quantity'], 'cr_def_contract_rate_tier_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_rate_definitions');
    }
};
