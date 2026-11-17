<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Enums\RulesetStatus;
use App\Models\AiConfiguration;
use App\Models\ArticleRevision;
use App\Models\ArticleTranslation;
use App\Models\ClinicalQuestionTranslation;
use App\Models\DoctorQuestionTemplateTranslation;
use App\Models\GlossaryTermTranslation;
use App\Models\GuidanceTextTranslation;
use App\Models\Role;
use App\Models\RulesetRelease;
use App\Models\User;
use App\Services\Ai\AiEvaluator;
use App\Services\Ai\CorePrompt;
use App\Services\Clinical\ActiveRulesetResolver;
use App\Services\Clinical\RulesetImporter;
use App\Services\Clinical\RulesetTestRunner;
use App\Services\Content\ChunkBuilder;
use App\Services\Content\MarkdownRenderer;
use Illuminate\Database\Seeder;

/**
 * Medically approves and publishes all seeded content, glossary terms, doctor visit templates,
 * clinical ruleset RS-2026.1, and activates the AI configuration so the platform works in full approved mode.
 */
class ApproveAllContentSeeder extends Seeder
{
    public function run(
        MarkdownRenderer $renderer,
        ChunkBuilder $chunks,
        RulesetImporter $importer,
        ActiveRulesetResolver $resolver,
        RulesetTestRunner $runner,
        AiEvaluator $aiEvaluator,
    ): void {
        Role::ensureAllExist();

        $superAdmin = User::query()->where('email', 'admin@rafiq.test')->first()
            ?? User::query()->whereHas('activeRoles', fn ($q) => $q->where('key', AdminRole::SuperAdmin->value))->first();

        $reviewer = User::query()->where('email', 'reviewer@rafiq.test')->first()
            ?? User::query()->whereHas('activeRoles', fn ($q) => $q->where('key', AdminRole::MedicalReviewer->value))->first();

        $editor = User::query()->where('email', 'editor@rafiq.test')->first()
            ?? User::query()->whereHas('activeRoles', fn ($q) => $q->where('key', AdminRole::ContentEditor->value))->first();

        // 1. Approve & publish all ArticleTranslations and rebuild RAG chunks
        foreach (ArticleTranslation::query()->with(['article', 'publishedRevision'])->get() as $translation) {
            $translation->forceFill([
                'review_status' => ReviewStatus::Approved,
                'publish_status' => PublishStatus::Published,
                'medically_approved_by' => $reviewer?->id,
                'medically_approved_at' => $translation->medically_approved_at ?? now(),
                'review_due_at' => $translation->review_due_at ?? now()->addYear()->toDateString(),
                'published_at' => $translation->published_at ?? now(),
            ])->save();

            $revision = $translation->publishedRevision ?? ArticleRevision::query()->create([
                'article_translation_id' => $translation->id,
                'version' => max(1, (int) $translation->version),
                'title' => $translation->title,
                'slug' => $translation->slug,
                'summary' => $translation->summary,
                'body_markdown' => $translation->body_markdown,
                'body_html' => $renderer->toHtml($translation->body_markdown),
                'approved_by' => $reviewer?->id,
                'approved_at' => now(),
                'change_note' => 'Medically approved and published',
            ]);

            $revision->forceFill([
                'approved_by' => $reviewer?->id,
                'approved_at' => $revision->approved_at ?? now(),
                'change_note' => 'Medically approved and published',
            ])->save();

            $translation->forceFill(['published_revision_id' => $revision->id])->save();
            $chunks->rebuild($translation, $revision);
        }

        // 2. Approve & publish all GlossaryTermTranslations
        GlossaryTermTranslation::query()->update([
            'review_status' => ReviewStatus::Approved,
            'publish_status' => PublishStatus::Published,
            'medically_approved_by' => $reviewer?->id,
            'medically_approved_at' => now(),
        ]);

        // 3. Approve & publish all DoctorQuestionTemplateTranslations
        DoctorQuestionTemplateTranslation::query()->update([
            'review_status' => ReviewStatus::Approved,
            'publish_status' => PublishStatus::Published,
            'last_editor_id' => $editor?->id,
            'medically_approved_by' => $reviewer?->id,
            'medically_approved_at' => now(),
        ]);

        // 4. Approve & publish Clinical Ruleset RS-2026.1
        $version = (string) config('rafiq.demo_ruleset', 'RS-2026.1');
        $release = RulesetRelease::query()->firstWhere('version', $version)
            ?? $importer->import(resource_path("clinical/rulesets/{$version}"));

        $release->ruleVersions()->update([
            'status' => ReviewStatus::Approved,
            'authored_by' => $editor?->id,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'review_comment' => 'Medically approved',
        ]);

        GuidanceTextTranslation::query()
            ->whereIn('guidance_text_id', $release->guidanceTexts()->select('id'))
            ->update([
                'review_status' => ReviewStatus::Approved,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
            ]);

        ClinicalQuestionTranslation::query()
            ->whereIn('clinical_question_id', $release->questions()->select('id'))
            ->update([
                'review_status' => ReviewStatus::Approved,
            ]);

        $report = $runner->run($resolver->load($release), RulesetTestRunner::casesFor($release->version));
        $report['ran_at'] = now()->toIso8601String();

        RulesetRelease::query()
            ->where('status', RulesetStatus::Published)
            ->whereKeyNot($release->id)
            ->update(['status' => RulesetStatus::Superseded]);

        $release->forceFill([
            'status' => RulesetStatus::Published,
            'test_report' => $report,
            'tests_passed_at' => now(),
            'submitted_by' => $superAdmin?->id,
            'submitted_at' => $release->submitted_at ?? now(),
            'medically_approved_by' => $reviewer?->id,
            'medically_approved_at' => $release->medically_approved_at ?? now(),
            'published_by' => $superAdmin?->id,
            'published_at' => $release->published_at ?? now(),
            'review_comment' => 'Medically approved and published',
        ])->save();

        $resolver->forget();

        // 5. Ensure an active, evaluated AiConfiguration exists
        if (! AiConfiguration::query()->where('status', AiConfiguration::STATUS_ACTIVE)->exists()) {
            $evalReport = $aiEvaluator->run('safety');
            $evalReport['core_prompt_version'] = CorePrompt::VERSION;
            $evalReport['ran_at'] = now()->toIso8601String();

            AiConfiguration::query()->create([
                'version' => 1,
                'status' => AiConfiguration::STATUS_ACTIVE,
                'core_prompt_version' => CorePrompt::VERSION,
                'model' => config('services.gemini.model'),
                'settings' => [
                    'enabled' => true,
                    'default_simplification' => 2,
                    'daily_budget_usd' => (float) config('rafiq.ai.daily_budget_usd', 5.0),
                    'limits' => config('rafiq.ai.limits'),
                    'welcome' => [
                        'ar' => __('ai.welcome', [], 'ar'),
                        'en' => __('ai.welcome', [], 'en'),
                    ],
                    'suggestions' => [],
                ],
                'eval_report' => $evalReport,
                'created_by' => $superAdmin?->id,
                'activated_by' => $superAdmin?->id,
                'activated_at' => now(),
            ]);
        }
    }
}
