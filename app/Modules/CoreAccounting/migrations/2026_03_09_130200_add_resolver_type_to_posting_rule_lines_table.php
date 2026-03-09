<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posting_rule_lines', function (Blueprint $table) {
            $table->string('resolver_type')->nullable()->after('account_id');
        });
    }

    public function down(): void
    {
        Schema::table('posting_rule_lines', function (Blueprint $table) {
            $table->dropColumn('resolver_type');
        });
    }
};

