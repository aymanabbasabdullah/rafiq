<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Clinical;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\ClinicalRule;
use App\Models\ClinicalRuleVersion;
use App\Models\GuidanceText;
use App\Models\RulesetRelease;
use App\Models\User;
use App\Services\Clinical\ReadableCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Clinical rules list and review screen (M11C-FR-01, M11C-FR-02). Read-only for conditions and priorities.
 */
class RuleController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', RulesetRelease::class);

        $rules = ClinicalRule::query()
            ->with('latestVersion')
            ->get()
            ->sortBy('code', SORT_NATURAL)
            ->values()
            ->map(fn (ClinicalRule $rule): array => [
                'code' => $rule->code,
                'title' => $rule->title,
                'version' => $rule->latestVersion?->version,
                'section' => $rule->latestVersion?->section,
                'priority' => $rule->latestVersion?->priority->value,
                'status' => $rule->latestVersion?->status->value,
                'support' => $rule->latestVersion?->support,
                'enabled' => $rule->latestVersion?->isEnabled() ?? false,
                'url' => $rule->latestVersion !== null ? route('admin.clinical.rules.show', $rule->latestVersion) : null,
            ]);

        return Inertia::render('Admin/Clinical/Rules', [
            'rules' => $rules,
            'counts' => [
                'pending' => ClinicalRuleVersion::query()->where('status', ReviewStatus::PendingMedicalReview)->count(),
                'approved' => ClinicalRuleVersion::query()->where('status', ReviewStatus::Approved)->count(),
            ],
            'releasesUrl' => route('admin.clinical.releases.index'),
        ]);
    }

    public function show(Request $request, ClinicalRuleVersion $version): Response
    {
        Gate::authorize('viewAny', RulesetRelease::class);

        /** @var User $user */
        $user = $request->user();
        $version->load(['rule', 'reviewer']);
        $definition = $version->definition;
        $release = $version->releases()->latest('id')->first();

        $questions = $release?->questions()->get()->mapWithKeys(fn ($question): array => [$question->key => $question->definition])->all() ?? [];
        $texts = $release !== null ? $this->texts($release) : [];
        $readable = new ReadableCondition($questions);

        $branches = array_map(fn (array $branch): array => [
            'readable' => ['ar' => $readable->describe($branch['when'], 'ar'), 'en' => $readable->describe($branch['when'], 'en')],
            'priority' => $branch['outcome']['priority'],
            'priorityByJourney' => $branch['outcome']['priority_by_journey'] ?? (object) [],
            'stop' => (bool) ($branch['outcome']['stop'] ?? false),
            'flags' => $branch['outcome']['flags'] ?? [],
            'guidance' => $this->withTexts($branch['guidance_keys'] ?? $definition['guidance_keys'] ?? [], $texts),
            'topics' => $this->withTexts($branch['doctor_topic_keys'] ?? $definition['doctor_topic_keys'] ?? [], $texts),
        ], $definition['branches'] ?? [['when' => $definition['when'], 'outcome' => $definition['outcome']]]);

        $affected = collect($release?->test_report['results'] ?? [])
            ->filter(fn (array $result): bool => in_array($version->rule->code, array_column($result['actual']['triggered'] ?? [], 'code'), true))
            ->map(fn (array $result): array => ['id' => $result['id'], 'description' => $result['description'], 'passed' => $result['passed']])
            ->values();

        return Inertia::render('Admin/Clinical/RuleVersion', [
            'rule' => [
                'code' => $version->rule->code,
                'title' => $version->rule->title,
                'version' => $version->version,
                'section' => $version->section,
                'priority' => $version->priority->value,
                'status' => $version->status->value,
                'support' => $version->support,
                'enabled' => $version->isEnabled(),
                'disabledReason' => $definition['disabled_reason'] ?? null,
                'journeys' => $definition['journeys'],
                'branches' => $branches,
                'evidence' => $version->evidence,
                'relatedArticles' => $definition['related_article_keys'] ?? [],
                'json' => json_encode($definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'reviewComment' => $version->review_comment,
                'reviewer' => $version->reviewer?->name,
                'reviewedAt' => $version->reviewed_at?->toDateTimeString(),
                'importedFromCommit' => $version->imported_from_commit,
            ],
            'release' => $release !== null ? ['version' => $release->version, 'status' => $release->status->value, 'testsRun' => $release->test_report !== null] : null,
            'affectedTests' => $affected,
            'can' => [
                'submit' => $version->status === ReviewStatus::Draft && $user->can('submit', $version),
                'review' => $version->status === ReviewStatus::PendingMedicalReview && Gate::allows('four-eyes', [$version->authored_by]),
            ],
            'actionUrl' => route('admin.clinical.rules.action', ['version' => $version, 'action' => '__action__']),
            'rulesUrl' => route('admin.clinical.rules.index'),
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function texts(RulesetRelease $release): array
    {
        return $release->guidanceTexts()->with('translations')->get()
            ->mapWithKeys(fn (GuidanceText $text): array => [
                $text->key => $text->translations->pluck('text_markdown', 'locale')->all(),
            ])->all();
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, array<string, string>>  $texts
     * @return list<array{key: string, ar: string|null, en: string|null}>
     */
    private function withTexts(array $keys, array $texts): array
    {
        return array_map(fn (string $key): array => ['key' => $key, 'ar' => $texts[$key]['ar'] ?? null, 'en' => $texts[$key]['en'] ?? null], $keys);
    }
}
