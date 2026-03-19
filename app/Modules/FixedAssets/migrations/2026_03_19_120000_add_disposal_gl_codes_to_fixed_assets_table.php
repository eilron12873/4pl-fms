<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->string('gl_disposal_proceeds_code', 20)->default('152600');
            $table->string('gl_disposal_gain_code', 20)->default('460000');
            $table->string('gl_disposal_loss_code', 20)->default('560000');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropColumn([
                'gl_disposal_proceeds_code',
                'gl_disposal_gain_code',
                'gl_disposal_loss_code',
            ]);
        });
    }
};

