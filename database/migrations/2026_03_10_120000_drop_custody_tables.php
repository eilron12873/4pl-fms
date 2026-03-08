<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove custody ledger from FMS. Custody data stays in WMS; FMS receives billing feed only.
     */
    public function up(): void
    {
        Schema::dropIfExists('custody_movements');
        Schema::dropIfExists('custody_balances');
    }

    public function down(): void
    {
        Schema::create('custody_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('billing_clients')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->timestamp('last_movement_at')->nullable();
            $table->timestamps();
            $table->unique(['client_id', 'warehouse_id', 'item_id']);
        });

        Schema::create('custody_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('billing_clients')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->string('movement_type', 32);
            $table->decimal('quantity', 15, 4);
            $table->string('reference', 255)->nullable();
            $table->date('movement_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'movement_date']);
        });
    }
};
