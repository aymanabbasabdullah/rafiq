<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\GlossaryTermTranslation;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bilingual glossary of approved, published terms, sorted alphabetically in the visitor's language (FR-073).
 */
class GlossaryController extends Controller
{
    public function __invoke(): Response
    {
        $locale = App::getLocale();
        $collator = class_exists(\Collator::class) ? new \Collator($locale) : null;

        $terms = GlossaryTermTranslation::query()
            ->where('locale', $locale)
            ->where('review_status', ReviewStatus::Approved->value)
            ->where('publish_status', PublishStatus::Published->value)
            ->get(['id', 'term', 'definition_html'])
            ->map(fn (GlossaryTermTranslation $term): array => [
                'id' => $term->id,
                'term' => $term->term,
                'definitionHtml' => $term->definition_html,
            ])
            ->all();

        usort($terms, fn (array $a, array $b): int => $collator?->compare($a['term'], $b['term']) ?? strcmp($a['term'], $b['term']));

        return Inertia::render('Public/Learn/Glossary', [
            'terms' => $terms,
            'learnUrl' => route('journeys.learn'),
        ]);
    }
}
