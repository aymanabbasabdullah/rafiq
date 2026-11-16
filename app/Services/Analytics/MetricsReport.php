<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\ArticleTranslation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the privacy-safe analytics view over `daily_metrics` (M11E-FR-01..03, ADM-050/051/060).
 *
 * Only allow-listed metrics are read, and any count from 1 to 4 is replaced by `null` before it leaves
 * the server, so the browser never receives a small number that could single out a visitor.
 */
class MetricsReport
{
    public const PERIODS = [7, 30, 90];

    public const SUPPRESSION_THRESHOLD = 5;

    /**
     * Metrics shown in the breakdown sections, in display order. Anything else in the table is ignored.
     *
     * @var list<string>
     */
    public const SECTIONS = [
        'page_view',
        'journey_selected',
        'locale_used',
        'age_gate',
        'assessment_started',
        'assessment_completed',
        'assessment_time',
        'next_step_taken',
        'report_trust',
        'report_clear',
        'ai_request',
    ];

    /**
     * Read only to build the equity table (completion per language and device class).
     *
     * @var list<string>
     */
    private const SEGMENT_METRICS = ['assessment_started_segment', 'assessment_completed_segment'];

    private const TOP_ARTICLES = 10;

    /**
     * @return array{
     *     period: int,
     *     from: string,
     *     to: string,
     *     totals: array<string, int|null>,
     *     daily: list<array{date: string, value: int|null}>,
     *     sections: list<array{metric: string, rows: list<array{dimension: string, value: int|null}>}>,
     *     topArticles: list<array{id: int, title: string, value: int|null}>
     * }
     */
    public function forPeriod(int $days, string $locale): array
    {
        $to = Carbon::today();
        $from = $to->copy()->subDays($days - 1);

        $rows = DB::table('daily_metrics')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('metric', [...self::SECTIONS, ...self::SEGMENT_METRICS, 'article_ai_explain'])
            ->groupBy('metric', 'dimension')
            ->selectRaw('metric, dimension, SUM(value) as total')
            ->get();

        $totals = [];
        $sections = [];

        foreach (self::SECTIONS as $metric) {
            $metricRows = $rows->where('metric', $metric)->sortByDesc('total');
            $totals[$metric] = $this->suppress((int) $metricRows->sum('total'));
            $sections[] = [
                'metric' => $metric,
                'rows' => $metricRows
                    ->map(fn (object $row): array => ['dimension' => (string) $row->dimension, 'value' => $this->suppress((int) $row->total)])
                    ->values()
                    ->all(),
            ];
        }

        return [
            'period' => $days,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totals' => $totals,
            'daily' => $this->dailyPageViews($from, $to),
            'sections' => $sections,
            'topArticles' => $this->topArticles($rows->where('metric', 'article_ai_explain')->all(), $locale),
            'kpis' => $this->kpis($rows),
            'equity' => $this->equity($rows),
        ];
    }

    /**
     * Competition impact indicators from the counters. A rate is `null` ("not enough data") whenever its
     * denominator is below the suppression threshold, so a rate can never reveal a small count.
     *
     * @param  Collection<int, object>  $rows
     * @return array{completionRate: float|null, fastGuidanceRate: float|null, followThroughRate: float|null, averageTrust: float|null, clarityRate: float|null}
     */
    private function kpis(Collection $rows): array
    {
        $sum = fn (string $metric, ?array $dimensions = null): int => (int) $rows
            ->where('metric', $metric)
            ->filter(fn (object $row): bool => $dimensions === null || in_array((string) $row->dimension, $dimensions, true))
            ->sum('total');

        $completed = $sum('assessment_completed');
        $timed = $sum('assessment_time');
        $trustVotes = $sum('report_trust');
        $trustTotal = (int) $rows->where('metric', 'report_trust')->sum(fn (object $row): int => (int) $row->dimension * (int) $row->total);

        return [
            'completionRate' => $this->rate($completed, $sum('assessment_started')),
            'fastGuidanceRate' => $this->rate($sum('assessment_time', ['lt_3m', '3_6m']), $timed),
            // Share of completed assessments followed by at least one recorded next step (upper bound: one visitor can take several).
            'followThroughRate' => $this->rate(min($sum('next_step_taken'), $completed), $completed),
            'averageTrust' => $trustVotes >= self::SUPPRESSION_THRESHOLD ? round($trustTotal / $trustVotes, 1) : null,
            'clarityRate' => $this->rate($sum('report_clear', ['yes']), $sum('report_clear')),
        ];
    }

    private function rate(int $part, int $whole): ?float
    {
        return $whole >= self::SUPPRESSION_THRESHOLD ? round(min(1, $part / $whole) * 100, 1) : null;
    }

    /**
     * Completion rate per `{locale}:{device}` segment (fairness across the groups Rafiq can see without
     * collecting demographics).
     *
     * @param  Collection<int, object>  $rows
     * @return list<array{segment: string, started: int|null, completed: int|null, rate: float|null}>
     */
    private function equity(Collection $rows): array
    {
        $started = $rows->where('metric', 'assessment_started_segment')->mapWithKeys(fn (object $row): array => [(string) $row->dimension => (int) $row->total]);
        $completed = $rows->where('metric', 'assessment_completed_segment')->mapWithKeys(fn (object $row): array => [(string) $row->dimension => (int) $row->total]);

        return $started->keys()->merge($completed->keys())->unique()->sort()->values()
            ->map(fn (string $segment): array => [
                'segment' => $segment,
                'started' => $this->suppress((int) $started->get($segment, 0)),
                'completed' => $this->suppress((int) $completed->get($segment, 0)),
                'rate' => $this->rate((int) $completed->get($segment, 0), (int) $started->get($segment, 0)),
            ])
            ->all();
    }

    /**
     * Counts below the threshold (but above zero) are hidden as "< 5" (ADM-051).
     */
    public function suppress(int $value): ?int
    {
        return $value > 0 && $value < self::SUPPRESSION_THRESHOLD ? null : $value;
    }

    /**
     * One entry per day in the period, zero-filled, so the chart's time axis has no gaps.
     *
     * @return list<array{date: string, value: int|null}>
     */
    private function dailyPageViews(Carbon $from, Carbon $to): array
    {
        $byDate = DB::table('daily_metrics')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->where('metric', 'page_view')
            ->groupBy('date')
            ->selectRaw('date, SUM(value) as total')
            ->pluck('total', 'date')
            ->mapWithKeys(fn (mixed $total, mixed $date): array => [Carbon::parse((string) $date)->toDateString() => (int) $total]);

        $days = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $days[] = ['date' => $day->toDateString(), 'value' => $this->suppress((int) $byDate->get($day->toDateString(), 0))];
        }

        return $days;
    }

    /**
     * Articles most often explained by the AI companion (`article:{id}` dimensions), titled in the admin's language.
     *
     * @param  array<int, object>  $rows
     * @return list<array{id: int, title: string, value: int|null}>
     */
    private function topArticles(array $rows, string $locale): array
    {
        $counts = collect($rows)
            ->filter(fn (object $row): bool => preg_match('/^article:(\d+)$/', (string) $row->dimension) === 1)
            ->mapWithKeys(fn (object $row): array => [(int) substr((string) $row->dimension, 8) => (int) $row->total])
            ->sortDesc()
            ->take(self::TOP_ARTICLES);

        if ($counts->isEmpty()) {
            return [];
        }

        $titles = ArticleTranslation::query()
            ->whereIn('article_id', $counts->keys())
            ->get(['article_id', 'locale', 'title'])
            ->groupBy('article_id')
            ->map(fn ($translations) => ($translations->firstWhere('locale', $locale) ?? $translations->first())?->title);

        return $counts
            ->map(fn (int $total, int $id): array => ['id' => $id, 'title' => (string) ($titles->get($id) ?? "#{$id}"), 'value' => $this->suppress($total)])
            ->values()
            ->all();
    }
}
