<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Doctor visit preparation bank (Module-10, Database-Design §3.2): approved suggested questions per topic
 * and the "what to bring" checklist. Visitors' own questions are never stored anywhere (BR-10-1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_question_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 96)->unique();
            $table->string('kind', 16)->default('question');
            $table->string('topic_key', 48)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['kind', 'topic_key']);
        });

        Schema::create('doctor_question_template_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_question_template_id');
            $table->string('locale', 2);
            $table->text('text');
            $table->text('help')->nullable();
            $table->string('review_status', 32)->default('draft');
            $table->string('publish_status', 32)->default('draft');
            $table->unsignedBigInteger('medically_approved_by')->nullable();
            $table->timestamp('medically_approved_at')->nullable();
            $table->timestamps();

            $table->unique(['doctor_question_template_id', 'locale'], 'doctor_question_tr_locale_unique');
            $table->index(['locale', 'publish_status'], 'doctor_question_tr_publish_index');
            // Short constraint names: MySQL limits identifiers to 64 characters.
            $table->foreign('doctor_question_template_id', 'doctor_question_tr_template_fk')->references('id')->on('doctor_question_templates')->cascadeOnDelete();
            $table->foreign('medically_approved_by', 'doctor_question_tr_approver_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_question_template_translations');
        Schema::dropIfExists('doctor_question_templates');
    }
};
