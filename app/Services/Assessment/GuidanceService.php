<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\GuidanceTextTranslation;
use App\Models\RulesetRelease;
use App\Services\Clinical\Data\EvaluationResult;
use App\Services\Content\KnowledgeUrls;
use RuntimeException;

/**
 * Builds the guidance report from an engine result (M07-FR-01/02): priority label and next step, the
 * guidance texts of the triggered rules, topics for the doctor and related approved articles.
 *
 * Production shows approved texts only; demo mode shows draft texts marked as such. A missing text is
 * an error (503), never a silently incomplete report. Nothing is stored (PRIV-002).
 */
final class GuidanceService
{
    public function __construct(private readonly KnowledgeUrls $urls) {}

    /**
     * @return array<string, mixed>
     */
    public function build(EvaluationResult $result, string $locale): array
    {
        $priority = $result->priority?->value;
        $texts = $this->texts($result, $locale);

        return [
            'priority' => $priority,
            'stopped' => $result->stopped,
            'label' => $priority !== null ? __("report.priority.{$priority}.label", [], $locale) : null,
            'headline' => $priority !== null ? __("report.priority.{$priority}.headline", [], $locale) : null,
            'nextStep' => $priority !== null ? __("report.priority.{$priority}.next_step", [], $locale) : null,
            'guidance' => array_map(fn (string $key): string => $texts[$key], $result->guidanceKeys),
            'doctorTopics' => array_map(fn (string $key): string => $texts[$key], $result->doctorTopicKeys),
            'notices' => array_values(array_filter(array_map(
                fn (string $flag): ?string => in_array($flag, ['show_emergency_criteria', 'time_sensitive', 'prompt_in_person_exam', 'unanswered_present', 'confirm_fever_then_same_day'], true)
                    ? __("report.flag.{$flag}", [], $locale)
                    : null,
                $result->flags,
            ))),
            'showEmergencyCriteria' => in_array('show_emergency_criteria', $result->flags, true),
            'articles' => $this->articles($result->relatedArticleKeys, $locale),
            'rulesetVersion' => $result->rulesetVersion,
            'demo' => $result->demo,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function texts(EvaluationResult $result, string $locale): array
    {
        $keys = array_values(array_unique([...$result->guidanceKeys, ...$result->doctorTopicKeys]));

        if ($keys === []) {
            return [];
        }

        $releaseId = RulesetRelease::query()->where('version', $result->rulesetVersion)->value('id');

        $rows = GuidanceTextTranslation::query()
            ->where('locale', $locale)
            ->whereHas('guidanceText', fn ($query) => $query->where('ruleset_release_id', $releaseId)->whereIn('key', $keys))
            ->when(! $result->demo, fn ($query) => $query->where('review_status', ReviewStatus::Approved))
            ->with('guidanceText')
            ->get();

        $texts = [];

        foreach ($rows as $row) {
            $texts[$row->guidanceText->key] = $row->text_markdown;
        }

        $missing = array_diff($keys, array_keys($texts));

        if ($missing !== []) {
            throw new RuntimeException('Missing approved guidance texts: '.implode(', ', $missing));
        }

        return $texts;
    }

    /**
     * Related articles that are live in this language (M07-FR-03).
     *
     * @param  list<string>  $keys
     * @return list<array{title: string, url: string}>
     */
    private function articles(array $keys, string $locale): array
    {
        if ($keys === []) {
            return [];
        }

        $articles = [];

        foreach (Article::query()->whereIn('key', $keys)->with(['translations.publishedRevision', 'category.translations'])->get() as $article) {
            $translation = $article->translationFor($locale);
            $url = $translation !== null ? $this->urls->article($translation) : null;

            if ($url !== null && $translation?->publishedRevision !== null) {
                $articles[] = ['title' => $translation->publishedRevision->title, 'url' => $url];
            }
        }

        return $articles;
    }
}
