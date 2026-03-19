<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_rule_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posting_rule_id')->constrained('posting_rules')->cascadeOnDelete();
            $table->foreignId('posting_rule_version_id')->nullable()->constrained('posting_rule_versions')->nullOnDelete();
            $table->string('action'); // created, updated, status_changed, activated, retired
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['posting_rule_id', 'action']);
            $table->index(['actor_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_rule_audit_logs');
    }
};

