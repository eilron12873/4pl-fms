<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->index(['log_name', 'created_at'], 'activities_log_name_created_at_index');
            $table->index(['causer_type', 'causer_id', 'created_at'], 'activities_causer_created_at_index');
            $table->index(['event', 'created_at'], 'activities_event_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_log_name_created_at_index');
            $table->dropIndex('activities_causer_created_at_index');
            $table->dropIndex('activities_event_created_at_index');
        });
    }
};
