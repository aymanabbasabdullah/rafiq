<?php

declare(strict_types=1);

namespace App\Services\DoctorPrep;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Models\DoctorQuestionTemplate;
use App\Models\DoctorQuestionTemplateTranslation;

/**
 * Published doctor-visit questions and "what to bring" items in one language (M10-FR-01/02/05).
 *
 * Production shows medically approved wording only; demo mode also shows draft wording, which the page
 * marks as not yet approved. There is no endpoint that receives a visitor's own text (BR-10-1).
 */
final class DoctorPrepBank
{
    public const TOPICS = ['pain_swelling', 'lump_change', 'fertility', 'sexual_urinary', 'medications_lifestyle', 'tests_results'];

    /**
     * @return array{topics: list<array{key: string, label: string, questions: list<array{id: string, text: string, help: string|null}>}>, bring: list<array{id: string, text: string}>}
     */
    public function forLocale(string $locale, bool $includeDrafts): array
    {
        $rows = DoctorQuestionTemplateTranslation::query()
            ->where('locale', $locale)
            ->where('publish_status', PublishStatus::Published)
            ->when(! $includeDrafts, fn ($query) => $query->where('review_status', ReviewStatus::Approved))
            ->with('template')
            ->get()
            ->sortBy(fn (DoctorQuestionTemplateTranslation $row): int => $row->template->sort_order);

        $topics = [];

        foreach (self::TOPICS as $topic) {
            $questions = $rows
                ->filter(fn (DoctorQuestionTemplateTranslation $row): bool => $row->template->kind === DoctorQuestionTemplate::KIND_QUESTION && $row->template->topic_key === $topic)
                ->map(fn (DoctorQuestionTemplateTranslation $row): array => ['id' => $row->template->key, 'text' => $row->text, 'help' => $row->help])
                ->values()
                ->all();

            if ($questions !== []) {
                $topics[] = ['key' => $topic, 'label' => __("doctor_prep.topic.{$topic}", [], $locale), 'questions' => $questions];
            }
        }

        $bring = $rows
            ->filter(fn (DoctorQuestionTemplateTranslation $row): bool => $row->template->kind === DoctorQuestionTemplate::KIND_BRING)
            ->map(fn (DoctorQuestionTemplateTranslation $row): array => ['id' => $row->template->key, 'text' => $row->text])
            ->values()
            ->all();

        return ['topics' => $topics, 'bring' => $bring];
    }
}
