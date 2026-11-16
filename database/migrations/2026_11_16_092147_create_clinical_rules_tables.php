<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Clinical rules (Module-06 / Module-11-C, Database-Design §3.3).
 *
 * Rules are authored as JSON in Git and imported as Draft. A release groups exact rule versions; approved
 * versions are immutable (BR-06-5) and production only runs a published release whose versions are all
 * approved (BR-06-6). No answers or results are ever stored here (PRIV-002).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruleset_releases', function (Blueprint $table) {
            $table->id();
            $table->string('version', 32)->unique();
            $table->string('status', 32)->default('draft');
            $table->char('source_hash', 64);
            $table->string('imported_from_commit', 64)->nullable();
            $table->json('lexicon')->nullable();
            $table->json('test_report')->nullable();
            $table->timestamp('tests_passed_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('medically_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('medically_approved_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('clinical_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('clinical_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_rule_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('section', 16);
            $table->json('definition');
            $table->char('definition_hash', 64);
            $table->string('priority', 8);
            $table->string('support', 16);
            $table->string('status', 32)->default('draft');
            $table->json('evidence');
            $table->text('rationale')->nullable();
            $table->foreignId('authored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('imported_from_commit', 64)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamps();

            $table->unique(['clinical_rule_id', 'version']);
            $table->index('status');
        });

        Schema::create('ruleset_release_items', function (Blueprint $table) {
            $table->foreignId('ruleset_release_id')->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_rule_version_id')->constrained()->restrictOnDelete();

            $table->primary(['ruleset_release_id', 'clinical_rule_version_id'], 'ruleset_release_items_primary');
        });

        Schema::create('clinical_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruleset_release_id')->constrained()->cascadeOnDelete();
            $table->string('key', 64);
            $table->string('section', 16);
            $table->string('type', 16);
            $table->json('definition');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['ruleset_release_id', 'key']);
        });

        Schema::create('clinical_question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_question_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->text('prompt');
            $table->text('why_we_ask')->nullable();
            $table->json('option_labels')->nullable();
            $table->string('review_status', 32)->default('draft');
            $table->timestamps();

            $table->unique(['clinical_question_id', 'locale'], 'clinical_question_tr_locale_unique');
        });

        Schema::create('guidance_texts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ruleset_release_id')->constrained()->cascadeOnDelete();
            $table->string('key', 96);
            $table->string('category', 32);
            $table->timestamps();

            $table->unique(['ruleset_release_id', 'key']);
        });

        Schema::create('guidance_text_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guidance_text_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->text('text_markdown');
            $table->string('review_status', 32)->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['guidance_text_id', 'locale'], 'guidance_text_tr_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guidance_text_translations');
        Schema::dropIfExists('guidance_texts');
        Schema::dropIfExists('clinical_question_translations');
        Schema::dropIfExists('clinical_questions');
        Schema::dropIfExists('ruleset_release_items');
        Schema::dropIfExists('clinical_rule_versions');
        Schema::dropIfExists('clinical_rules');
        Schema::dropIfExists('ruleset_releases');
    }
};
