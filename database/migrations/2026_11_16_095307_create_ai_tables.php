<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rafiq AI (Module-09 / Module-11-D, Database-Design §3.4).
 *
 * There is deliberately no conversations table, and `ai_usage_logs` holds metadata only: never a
 * message, an answer, retrieved chunks, an IP address, a session id or a user agent (AI-017, GR-14).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version')->unique();
            $table->string('status', 16)->default('draft');
            $table->json('settings');
            $table->string('core_prompt_version', 16);
            $table->string('model', 64)->nullable();
            $table->json('eval_report')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('created_at')->index();
            $table->string('mode', 32);
            $table->string('locale', 2);
            $table->string('outcome', 32);
            $table->unsignedInteger('ai_configuration_version')->nullable();
            $table->string('core_prompt_version', 16);
            $table->string('model', 64)->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_code', 32)->nullable();
            $table->unsignedBigInteger('estimated_cost_micros')->nullable();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();

            $table->index(['outcome', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_configurations');
    }
};
