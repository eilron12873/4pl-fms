<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_bills', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('vendor_id')->constrained('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ap_bills', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
        });
    }
};
