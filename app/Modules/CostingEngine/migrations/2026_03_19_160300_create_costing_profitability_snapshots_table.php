<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('costing_profitability_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('dimension', 40);
            $table->unsignedBigInteger('dimension_id');
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->decimal('margin', 15, 2)->default(0);
            $table->decimal('margin_pct', 8, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->timestamp('computed_at')->index();
            $table->timestamps();

            $table->index(['dimension', 'dimension_id'], 'cps_dim_dimid_idx');
            $table->index(['dimension', 'from_date', 'to_date'], 'cps_dim_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costing_profitability_snapshots');
    }
};

