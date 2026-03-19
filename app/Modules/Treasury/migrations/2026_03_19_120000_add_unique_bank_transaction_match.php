<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            // In MySQL, UNIQUE allows multiple NULLs, which is what we want:
            // a statement line is either unmatched (NULL) or matched to one bank transaction.
            $table->unique('bank_transaction_id', 'bstl_bank_transaction_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('bank_statement_lines', function (Blueprint $table) {
            $table->dropUnique('bstl_bank_transaction_id_unique');
        });
    }
};

