<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->index(['client_id', 'account_id'], 'jl_client_account_idx');
            $table->index(['shipment_id', 'account_id'], 'jl_shipment_account_idx');
            $table->index(['route_id', 'account_id'], 'jl_route_account_idx');
            $table->index(['warehouse_id', 'account_id'], 'jl_warehouse_account_idx');
            $table->index(['project_id', 'account_id'], 'jl_project_account_idx');
        });

        Schema::table('ar_invoices', function (Blueprint $table) {
            $table->index(['client_id', 'status', 'invoice_date'], 'ar_client_status_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->dropIndex('jl_client_account_idx');
            $table->dropIndex('jl_shipment_account_idx');
            $table->dropIndex('jl_route_account_idx');
            $table->dropIndex('jl_warehouse_account_idx');
            $table->dropIndex('jl_project_account_idx');
        });

        Schema::table('ar_invoices', function (Blueprint $table) {
            $table->dropIndex('ar_client_status_date_idx');
        });
    }
};

