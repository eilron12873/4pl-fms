<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->string('asset_type', 50); // vehicle, equipment, it, building, other
            $table->date('purchase_date');
            $table->decimal('acquisition_cost', 15, 2);
            $table->unsignedSmallInteger('useful_life_years');
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->string('depreciation_method', 50)->default('straight_line');
            $table->string('gl_asset_code', 20)->default('1300');
            $table->string('gl_accum_depn_code', 20)->default('1320');
            $table->string('gl_depn_expense_code', 20)->default('5400');
            $table->string('status', 20)->default('active'); // active, disposed
            $table->string('location', 255)->nullable();
            $table->string('custodian', 255)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->date('last_depreciation_at')->nullable();
            $table->timestamp('disposed_at')->nullable();
            $table->timestamps();
            $table->index('status');
            $table->index('asset_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
