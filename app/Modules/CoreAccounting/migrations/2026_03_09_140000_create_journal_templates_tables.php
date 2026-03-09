<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('frequency')->nullable(); // e.g. daily, weekly, monthly
            $table->dateTime('next_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('journal_template_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_template_id')->constrained('journal_templates')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('shipment_id')->nullable();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('service_line_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_template_lines');
        Schema::dropIfExists('journal_templates');
    }
};

