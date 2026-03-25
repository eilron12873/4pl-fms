<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ar_invoice_adjustments', function (Blueprint $table) {
            $table->string('status', 32)->default('posted')->index()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('ar_invoice_adjustments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

