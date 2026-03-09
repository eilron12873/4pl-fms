<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archived_journals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id')->unique();
            $table->string('journal_number');
            $table->date('journal_date');
            $table->string('period')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('posted');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('archived_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_id');
            $table->unsignedBigInteger('account_id');
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
            $table->timestamps();

            $table->index('journal_id');
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archived_journal_lines');
        Schema::dropIfExists('archived_journals');
    }
};

