<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 64)->index();
            $table->string('idempotency_key')->index();
            $table->string('source_system', 255);
            $table->string('source_reference', 255)->nullable();
            $table->string('status', 32)->index(); // received, duplicate, posted, accepted, error
            $table->text('message')->nullable();
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
