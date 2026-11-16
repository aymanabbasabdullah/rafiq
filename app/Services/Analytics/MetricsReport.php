<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\ArticleTranslation;
use Illuminate\Support\Carbon;
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
        'ai_request',
    ];

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
            ->whereIn('metric', [...self::SECTIONS, 'article_ai_explain'])
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
        ];
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
