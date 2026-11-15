<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Knowledge Center content (Module-08 / Module-11-B, Database-Design §3.2).
 *
 * Working copies live in `*_translations`; every medical approval snapshots the
 * content into `article_revisions`, and the public site only ever serves the
 * published revision. Editing a published article therefore never changes what
 * visitors see until the new version is approved again (BR-08-3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_categories', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('icon', 32)->default('book-open');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('ai_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('content_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['content_category_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_category_id')->constrained()->restrictOnDelete();
            $table->string('key', 96)->unique();
            $table->string('kind', 32)->default('article');
            $table->boolean('ai_enabled')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('article_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('title');
            $table->string('slug');
            $table->text('summary')->nullable();
            $table->mediumText('body_markdown');
            $table->string('review_status', 32)->default('draft');
            $table->string('publish_status', 32)->default('draft');
            $table->unsignedInteger('version')->default(0);
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('medically_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('medically_approved_at')->nullable();
            $table->date('review_due_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->unsignedBigInteger('published_revision_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['article_id', 'locale']);
            $table->unique(['locale', 'slug']);
            $table->index(['locale', 'publish_status']);
            $table->index(['review_status', 'locale']);
        });

        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_translation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->string('slug');
            $table->text('summary')->nullable();
            $table->mediumText('body_markdown');
            $table->mediumText('body_html');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at');
            $table->text('change_note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['article_translation_id', 'version']);
        });

        Schema::table('article_translations', function (Blueprint $table) {
            $table->foreign('published_revision_id')->references('id')->on('article_revisions')->nullOnDelete();
        });

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('publisher');
            $table->string('url', 2048);
            $table->string('publication_date', 32)->nullable();
            $table->date('accessed_at')->nullable();
            $table->string('evidence_type', 32)->default('other');
            $table->string('access_note')->nullable();
            $table->boolean('ai_use_restricted')->default(false);
            $table->timestamps();
        });

        Schema::create('article_source', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained()->restrictOnDelete();
            $table->string('support_level', 32)->default('direct');
            $table->text('claim_note')->nullable();

            $table->primary(['article_id', 'source_id']);
        });

        Schema::create('content_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_translation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_revision_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->unsignedInteger('chunk_index');
            $table->string('heading')->nullable();
            $table->text('text');
            $table->text('text_normalized');
            $table->unsignedInteger('token_estimate')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index(['locale', 'article_translation_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE content_chunks ADD FULLTEXT content_chunks_text_fulltext (heading, text_normalized) WITH PARSER ngram');
        }

        Schema::create('glossary_terms', function (Blueprint $table) {
            $table->id();
            $table->string('key', 96)->unique();
            $table->timestamps();
        });

        Schema::create('glossary_term_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('glossary_term_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 2);
            $table->string('term');
            $table->text('definition_markdown');
            $table->text('definition_html');
            $table->string('review_status', 32)->default('draft');
            $table->string('publish_status', 32)->default('draft');
            $table->foreignId('medically_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('medically_approved_at')->nullable();
            $table->timestamps();

            $table->unique(['glossary_term_id', 'locale']);
            $table->index(['locale', 'publish_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glossary_term_translations');
        Schema::dropIfExists('glossary_terms');
        Schema::dropIfExists('content_chunks');
        Schema::dropIfExists('article_source');
        Schema::dropIfExists('sources');

        Schema::table('article_translations', function (Blueprint $table) {
            $table->dropForeign(['published_revision_id']);
        });

        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('article_translations');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('content_category_translations');
        Schema::dropIfExists('content_categories');
    }
};
