import { Head, Link, usePage } from '@inertiajs/react';
import { Bot, ChartColumn, ClipboardCheck, Eye, Route as RouteIcon } from 'lucide-react';
import { Alert } from '@/components/ui/Alert';
import { Card, Panel } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

/** A count, or `null` when the server hid it because it was between 1 and 4 (ADM-051). */
type Count = number | null;

interface Section {
    metric: string;
    rows: { dimension: string; value: Count }[];
}

interface Report {
    period: number;
    from: string;
    to: string;
    totals: Record<string, Count>;
    daily: { date: string; value: Count }[];
    sections: Section[];
    topArticles: { id: number; title: string; value: Count }[];
    /** Percentages (or a 1–5 average); `null` means fewer than the threshold in the denominator. */
    kpis: {
        completionRate: number | null;
        fastGuidanceRate: number | null;
        followThroughRate: number | null;
        averageTrust: number | null;
        clarityRate: number | null;
    };
    equity: { segment: string; started: Count; completed: Count; rate: number | null }[];
}

interface AnalyticsProps {
    report: Report;
    periods: number[];
    threshold: number;
    filterUrl: string;
}

/**
 * Privacy-safe analytics (Module-11-E): aggregated daily counters, small counts shown as "< 5",
 * no raw export and nothing about priorities, red flags or answers (ADM-050/051/060).
 */
export default function AnalyticsIndex({ report, periods, threshold, filterUrl }: AnalyticsProps) {
    const t = useT();
    const { locale } = usePage().props;
    const numberFormat = new Intl.NumberFormat(locale.current === 'ar' ? 'ar-u-nu-latn' : 'en-GB');
    const hidden = t('admin.analytics.hidden', { threshold });
    const show = (value: Count) => (value === null ? hidden : numberFormat.format(value));
    const withUnit = (value: number, unit: string) => numberFormat.format(value) + unit;
    // `null` means hidden ("< 5"); only a missing key is a true zero.
    const total = (metric: string) => show(report.totals[metric] === undefined ? 0 : (report.totals[metric] ?? null));

    const dimensionLabel = (metric: string, dimension: string) => {
        const key = `admin.analytics.dim.${metric}.${dimension}`;
        const label = t(key);

        return label === key ? dimension : label;
    };

    const dateLabel = (iso: string, style: 'short' | 'long') =>
        new Intl.DateTimeFormat(locale.current === 'ar' ? 'ar-u-nu-latn' : 'en-GB', {
            day: 'numeric',
            month: style === 'short' ? 'numeric' : 'long',
            ...(style === 'long' ? { year: 'numeric' } : {}),
        }).format(new Date(`${iso}T00:00:00`));

    const hasData = report.sections.some((section) => section.rows.length > 0);
    // Hidden days are drawn at the threshold's height so the chart never implies an exact small number.
    const peak = Math.max(threshold, ...report.daily.map((day) => day.value ?? threshold));

    return (
        <AdminLayout>
            <Head title={t('admin.analytics.title')} />

            <div className="space-y-6">
                <div className="animate-fade-up">
                    <PageHeader
                        title={t('admin.analytics.title')}
                        description={t('admin.analytics.subtitle', {
                            from: dateLabel(report.from, 'long'),
                            to: dateLabel(report.to, 'long'),
                        })}
                        actions={
                            <nav
                                aria-label={t('admin.analytics.period_label')}
                                className="flex gap-1 rounded-xl bg-surface-sunken p-1"
                            >
                                {periods.map((period) => (
                                    <Link
                                        key={period}
                                        href={filterUrl}
                                        data={{ period }}
                                        preserveScroll
                                        aria-current={period === report.period ? 'page' : undefined}
                                        className={`min-h-9 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors ${
                                            period === report.period
                                                ? 'bg-surface-raised text-ink shadow-card'
                                                : 'text-ink-muted hover:text-ink'
                                        }`}
                                    >
                                        {t('admin.analytics.period_days', { days: period })}
                                    </Link>
                                ))}
                            </nav>
                        }
                    />
                </div>

                <Alert tone="info" title={t('admin.analytics.privacy_title')}>
                    {t('admin.analytics.privacy_body', { threshold })}
                </Alert>

                <div className="stagger grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label={t('admin.analytics.metric.page_view')} value={total('page_view')} icon={Eye} />
                    <StatCard
                        label={t('admin.analytics.metric.journey_selected')}
                        value={total('journey_selected')}
                        icon={RouteIcon}
                        tone="info"
                    />
                    <StatCard
                        label={t('admin.analytics.metric.assessment_completed')}
                        value={total('assessment_completed')}
                        icon={ClipboardCheck}
                        tone="success"
                    />
                    <StatCard
                        label={t('admin.analytics.metric.ai_request')}
                        value={total('ai_request')}
                        icon={Bot}
                        tone="neutral"
                    />
                </div>

                <Panel title={t('admin.analytics.kpi_title')} description={t('admin.analytics.kpi_hint')}>
                    <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        {(
                            [
                                ['completion_rate', report.kpis.completionRate, '%'],
                                ['fast_guidance', report.kpis.fastGuidanceRate, '%'],
                                ['follow_through', report.kpis.followThroughRate, '%'],
                                ['trust', report.kpis.averageTrust, ''],
                                ['clarity', report.kpis.clarityRate, '%'],
                            ] as const
                        ).map(([key, value, unit]) => (
                            <div key={key} className="rounded-xl bg-surface-sunken p-4">
                                <dt className="text-sm text-ink-muted">{t(`admin.analytics.kpi.${key}`)}</dt>
                                <dd className="mt-1 text-2xl font-semibold tabular-nums text-ink">
                                    {value === null ? (
                                        <span className="text-sm font-medium text-ink-subtle">
                                            {t('admin.analytics.kpi.not_enough')}
                                        </span>
                                    ) : (
                                        <bdi>{withUnit(value, unit)}</bdi>
                                    )}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </Panel>

                {report.equity.length > 0 ? (
                    <Panel title={t('admin.analytics.equity_title')} description={t('admin.analytics.equity_hint')}>
                        <div className="-m-5 overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-sunken text-xs text-ink-muted">
                                    <tr>
                                        <th scope="col" className="px-5 py-2.5 text-start font-semibold">
                                            {t('admin.analytics.col_segment')}
                                        </th>
                                        <th scope="col" className="px-5 py-2.5 text-end font-semibold">
                                            {t('admin.analytics.col_started')}
                                        </th>
                                        <th scope="col" className="px-5 py-2.5 text-end font-semibold">
                                            {t('admin.analytics.col_completed')}
                                        </th>
                                        <th scope="col" className="px-5 py-2.5 text-end font-semibold">
                                            {t('admin.analytics.col_rate')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {report.equity.map((row) => (
                                        <tr key={row.segment}>
                                            <td className="px-5 py-2.5 text-ink">
                                                {row.segment
                                                    .split(':')
                                                    .map((part) => t(`admin.analytics.segment.${part}`))
                                                    .join(' · ')}
                                            </td>
                                            <td className="px-5 py-2.5 text-end tabular-nums text-ink-muted">
                                                <bdi>{show(row.started)}</bdi>
                                            </td>
                                            <td className="px-5 py-2.5 text-end tabular-nums text-ink-muted">
                                                <bdi>{show(row.completed)}</bdi>
                                            </td>
                                            <td className="px-5 py-2.5 text-end font-medium tabular-nums text-ink">
                                                <bdi>
                                                    {row.rate === null
                                                        ? t('admin.analytics.kpi.not_enough')
                                                        : withUnit(row.rate, '%')}
                                                </bdi>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Panel>
                ) : null}

                {!hasData ? (
                    <EmptyState
                        icon={ChartColumn}
                        title={t('admin.analytics.empty')}
                        description={t('admin.analytics.empty_hint')}
                    />
                ) : (
                    <>
                        <Panel title={t('admin.analytics.daily_title')} description={t('admin.analytics.daily_hint')}>
                            <figure>
                                <div
                                    role="img"
                                    aria-label={t('admin.analytics.daily_title')}
                                    className="flex h-44 items-end gap-0.5 border-b border-line"
                                    dir="ltr"
                                >
                                    {report.daily.map((day) => (
                                        <div
                                            key={day.date}
                                            className="group relative flex h-full min-w-0 flex-1 items-end"
                                            title={`${dateLabel(day.date, 'long')}: ${show(day.value)}`}
                                        >
                                            <div
                                                className={`w-full rounded-t-sm transition-colors group-hover:bg-brand-deep ${
                                                    day.value === null ? 'bg-brand/35' : 'bg-brand'
                                                }`}
                                                style={{ height: `${((day.value ?? threshold) / peak) * 100}%` }}
                                            />
                                        </div>
                                    ))}
                                </div>
                                <figcaption className="mt-2 flex justify-between text-xs text-ink-subtle" dir="ltr">
                                    <bdi>{dateLabel(report.from, 'short')}</bdi>
                                    <bdi>{dateLabel(report.to, 'short')}</bdi>
                                </figcaption>
                            </figure>
                        </Panel>

                        <div className="grid gap-4 lg:grid-cols-2">
                            {report.sections.map((section) => (
                                <Card as="section" key={section.metric} className="overflow-hidden">
                                    <h2 className="border-b border-line px-5 py-3.5 text-sm font-semibold text-ink">
                                        {t(`admin.analytics.metric.${section.metric}`)}
                                    </h2>
                                    {section.rows.length === 0 ? (
                                        <p className="px-5 py-4 text-sm text-ink-subtle">{t('admin.analytics.no_rows')}</p>
                                    ) : (
                                        <table className="w-full text-sm">
                                            <thead className="sr-only">
                                                <tr>
                                                    <th scope="col">{t('admin.analytics.col_dimension')}</th>
                                                    <th scope="col">{t('admin.analytics.col_count')}</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-line">
                                                {section.rows.map((row) => (
                                                    <tr key={row.dimension}>
                                                        <td className="px-5 py-2.5 text-ink">
                                                            {dimensionLabel(section.metric, row.dimension)}
                                                        </td>
                                                        <td className="px-5 py-2.5 text-end font-medium tabular-nums text-ink-muted">
                                                            <bdi>{show(row.value)}</bdi>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    )}
                                </Card>
                            ))}
                        </div>

                        <Panel title={t('admin.analytics.top_articles')} description={t('admin.analytics.top_articles_hint')}>
                            {report.topArticles.length === 0 ? (
                                <p className="text-sm text-ink-subtle">{t('admin.analytics.no_rows')}</p>
                            ) : (
                                <ol className="-m-5 divide-y divide-line">
                                    {report.topArticles.map((article) => (
                                        <li
                                            key={article.id}
                                            className="flex items-center justify-between gap-4 px-5 py-2.5 text-sm"
                                        >
                                            <span className="min-w-0 truncate text-ink">{article.title}</span>
                                            <bdi className="shrink-0 font-medium tabular-nums text-ink-muted">
                                                {show(article.value)}
                                            </bdi>
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </Panel>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
