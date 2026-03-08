<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posting_sources', function (Blueprint $table) {
            $table->dropForeign(['journal_id']);
        });
        Schema::table('posting_sources', function (Blueprint $table) {
            $table->unsignedBigInteger('journal_id')->nullable()->change();
            $table->foreign('journal_id')->references('id')->on('journals')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posting_sources', function (Blueprint $table) {
            $table->dropForeign(['journal_id']);
        });
        Schema::table('posting_sources', function (Blueprint $table) {
            $table->unsignedBigInteger('journal_id')->nullable(false)->change();
            $table->foreign('journal_id')->references('id')->on('journals')->cascadeOnDelete();
        });
    }
};
