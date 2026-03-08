<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->index('route_id');
            $table->index('warehouse_id');
            $table->index('vehicle_id');
            $table->index('project_id');
            $table->index('cost_center_id');
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropIndex(['route_id']);
            $table->dropIndex(['warehouse_id']);
            $table->dropIndex(['vehicle_id']);
            $table->dropIndex(['project_id']);
            $table->dropIndex(['cost_center_id']);
        });
    }
};
