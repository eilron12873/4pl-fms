<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('file_hash')->unique();
            $table->string('original_name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('rows_imported')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_import_logs');
    }
};

