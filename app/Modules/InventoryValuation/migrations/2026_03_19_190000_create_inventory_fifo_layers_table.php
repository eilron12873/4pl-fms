<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_fifo_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_items')->cascadeOnDelete();

            // FIFO layer state (receipts create layers; outbound movements consume remaining quantity)
            $table->decimal('quantity_original', 15, 4)->default(0);
            $table->decimal('quantity_remaining', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);

            $table->date('layer_date'); // typically movement_date of the inbound that created the layer
            $table->string('reference', 255)->nullable();

            $table->unsignedBigInteger('source_movement_id')->nullable();
            $table->string('source_movement_type', 30)->nullable(); // receipt, transfer_in, adjustment(add)

            $table->timestamps();

            $table->index(['warehouse_id', 'item_id']);
            $table->index(['warehouse_id', 'item_id', 'layer_date', 'id']);
            $table->index('source_movement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_fifo_layers');
    }
};

