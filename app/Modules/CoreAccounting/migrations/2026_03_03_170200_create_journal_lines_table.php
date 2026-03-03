<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            // Profitability dimensions / context
            $table->unsignedBigInteger('client_id')->nullable()->index();
            $table->unsignedBigInteger('shipment_id')->nullable()->index();
            $table->unsignedBigInteger('route_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('service_line_id')->nullable()->index();
            $table->unsignedBigInteger('cost_center_id')->nullable();

            $table->timestamps();

            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};

