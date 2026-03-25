<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();

            $table->string('approvable_type', 191);
            $table->unsignedBigInteger('approvable_id');

            $table->string('approval_type', 64);
            $table->string('status', 32)->index();

            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->timestamp('requested_at')->nullable()->index();

            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable()->index();

            $table->unsignedBigInteger('rejected_by')->nullable()->index();
            $table->timestamp('rejected_at')->nullable()->index();

            $table->text('comments')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id'], 'approvals_approvable_idx');
            $table->unique(['approvable_type', 'approvable_id', 'approval_type'], 'approvals_one_active_per_type_uq');
            $table->index(['status', 'approval_type', 'created_at'], 'approvals_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};

