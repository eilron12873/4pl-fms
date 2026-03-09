<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('account_resolvers')) {
            return;
        }

        Schema::create('account_resolvers', function (Blueprint $table) {
            $table->id();
            $table->string('resolver_type');
            $table->string('dimension_key');
            $table->string('dimension_value');
            $table->foreignId('account_id')->constrained('accounts');
            $table->unsignedInteger('priority')->default(100);
            $table->timestamps();

            $table->index(
                ['resolver_type', 'dimension_key', 'dimension_value'],
                'acct_resolver_dim_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_resolvers');
    }
};

