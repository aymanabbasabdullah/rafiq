import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, CircleCheck, Clock, Layers } from 'lucide-react';
import { PriorityBadge } from '@/components/admin/ClinicalStatus';
import { ReviewBadge } from '@/components/admin/ContentStatus';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { buttonClasses } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type RuleRow = {
    code: string;
    title: string;
    version: number | null;
    section: string | null;
    priority: string | null;
    status: string | null;
    support: string | null;
    enabled: boolean;
    url: string | null;
};

type RulesProps = {
    rules: RuleRow[];
    counts: { pending: number; approved: number };
    releasesUrl: string;
};

/**
 * Clinical rules list (M11C-FR-01). Conditions and priorities are read-only here (M11C-FR-06).
 */
export default function Rules({ rules, counts, releasesUrl }: RulesProps) {
    const t = useT();

    return (
        <AdminLayout>
            <Head title={t('admin.clinical.rules_title')} />
            <div className="space-y-6">
                <PageHeader
                    title={t('admin.clinical.rules_title')}
                    description={t('admin.clinical.rules_description')}
                    actions={
                        <Link href={releasesUrl} className={buttonClasses('secondary')}>
                            <Layers aria-hidden="true" className="size-4" />
                            {t('admin.clinical.releases_title')}
                        </Link>
                    }
                />

                <Alert tone="warning" title={t('admin.clinical.draft_banner_title')}>
                    {t('admin.clinical.draft_banner_body')}
                </Alert>

                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard label={t('admin.clinical.count_rules')} value={rules.length} icon={Layers} tone="brand" />
                    <StatCard label={t('admin.clinical.count_pending')} value={counts.pending} icon={Clock} tone="warning" />
                    <StatCard
                        label={t('admin.clinical.count_approved')}
                        value={counts.approved}
                        icon={CircleCheck}
                        tone="success"
                    />
                </div>

                {rules.length === 0 ? (
                    <EmptyState
                        icon={Layers}
                        title={t('admin.clinical.empty_title')}
                        description={t('admin.clinical.empty_body')}
                    />
                ) : (
                    <Card className="overflow-hidden">
                        <ul className="divide-y divide-line">
                            {rules.map((rule) => (
                                <li key={rule.code}>
                                    <Link
                                        href={rule.url ?? '#'}
                                        className="group flex flex-col gap-3 px-5 py-4 transition-colors hover:bg-surface md:flex-row md:items-center md:gap-6"
                                    >
                                        <div className="flex min-w-0 flex-1 items-start gap-4">
                                            <span
                                                className="w-20 shrink-0 pt-0.5 font-mono text-sm font-semibold text-brand"
                                                dir="ltr"
                                            >
                                                {rule.code}
                                            </span>
                                            <div className="min-w-0">
                                                <p className="font-semibold text-ink" dir="ltr" lang="en">
                                                    {rule.title}
                                                </p>
                                                <p className="mt-0.5 text-sm text-ink-subtle">
                                                    {rule.section !== null ? t(`admin.clinical.section.${rule.section}`) : null}
                                                    {rule.version !== null ? (
                                                        <span className="ms-2">
                                                            {t('admin.clinical.version_n', { version: rule.version })}
                                                        </span>
                                                    ) : null}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-2 md:justify-end">
                                            {rule.priority !== null ? <PriorityBadge priority={rule.priority} /> : null}
                                            {rule.status !== null ? <ReviewBadge status={rule.status} /> : null}
                                            {!rule.enabled ? <Badge tone="warning">{t('admin.clinical.disabled')}</Badge> : null}
                                            <ArrowUpRight
                                                aria-hidden="true"
                                                className="hidden size-4 text-ink-subtle transition-colors group-hover:text-brand md:block rtl:-scale-x-100"
                                            />
                                        </div>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </Card>
                )}
            </div>
        </AdminLayout>
    );
}
