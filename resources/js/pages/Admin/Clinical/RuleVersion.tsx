import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CircleCheck, CircleX, FlaskConical, Send } from 'lucide-react';
import { useState } from 'react';
import { DecisionDialog, PriorityBadge } from '@/components/admin/ClinicalStatus';
import { ReviewBadge } from '@/components/admin/ContentStatus';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Panel } from '@/components/ui/Card';
import { PageHeader } from '@/components/ui/PageHeader';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type Text = { key: string; ar: string | null; en: string | null };

type Branch = {
    readable: { ar: string; en: string };
    priority: string;
    priorityByJourney: Record<string, string>;
    stop: boolean;
    flags: string[];
    guidance: Text[];
    topics: Text[];
};

type RuleVersionProps = {
    rule: {
        code: string;
        title: string;
        version: number;
        section: string;
        priority: string;
        status: string;
        support: string;
        enabled: boolean;
        disabledReason: string | null;
        journeys: string[];
        branches: Branch[];
        evidence: { source: string; support: string }[];
        relatedArticles: string[];
        json: string;
        reviewComment: string | null;
        reviewer: string | null;
        reviewedAt: string | null;
        importedFromCommit: string | null;
    };
    release: { version: string; status: string; testsRun: boolean } | null;
    affectedTests: { id: string; description: string; passed: boolean }[];
    can: { submit: boolean; review: boolean };
    actionUrl: string;
    rulesUrl: string;
};

/**
 * Medical review screen for one rule version (M11C-FR-02/03, Clinical-Review-Process §3).
 */
export default function RuleVersion({ rule, release, affectedTests, can, actionUrl, rulesUrl }: RuleVersionProps) {
    const t = useT();
    const { locale, errors } = usePage().props as unknown as { locale: { current: 'ar' | 'en' }; errors: Record<string, string> };
    const other = locale.current === 'ar' ? 'en' : 'ar';
    const [decision, setDecision] = useState<'approve' | 'reject' | null>(null);
    const [busy, setBusy] = useState(false);

    const act = (action: 'submit' | 'approve' | 'reject', comment?: string) => {
        setBusy(true);
        router.post(
            actionUrl.replace('__action__', action),
            { comment: comment ?? null },
            {
                preserveScroll: true,
                onFinish: () => {
                    setBusy(false);
                    setDecision(null);
                },
            },
        );
    };

    const texts = (items: Text[]) => (
        <ul className="space-y-3">
            {items.map((item) => (
                <li key={item.key} className="rounded-xl bg-surface p-3 text-sm">
                    <p className="font-mono text-xs text-ink-subtle" dir="ltr">
                        {item.key}
                    </p>
                    <p className="mt-1.5 text-ink" lang="ar" dir="rtl">
                        {item.ar}
                    </p>
                    <p className="mt-1 text-ink-muted" lang="en" dir="ltr">
                        {item.en}
                    </p>
                </li>
            ))}
        </ul>
    );

    return (
        <AdminLayout>
            <Head title={rule.code} />
            <div className="space-y-6">
                <Link
                    href={rulesUrl}
                    className="inline-flex min-h-10 items-center gap-2 text-sm font-medium text-brand hover:text-brand-strong"
                >
                    <ArrowLeft aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                    {t('admin.clinical.rules_title')}
                </Link>

                <PageHeader
                    eyebrow={
                        <span className="font-mono" dir="ltr">
                            {rule.code}
                        </span>
                    }
                    title={rule.title}
                    description={t('admin.clinical.version_n', { version: rule.version })}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <PriorityBadge priority={rule.priority} />
                            <ReviewBadge status={rule.status} />
                            <Badge tone="neutral">{t(`admin.clinical.support.${rule.support}`)}</Badge>
                        </div>
                    }
                />

                {!rule.enabled ? (
                    <Alert tone="warning" title={t('admin.clinical.disabled_title')}>
                        <span dir="ltr" lang="en">
                            {rule.disabledReason}
                        </span>
                    </Alert>
                ) : null}

                {errors.workflow !== undefined ? (
                    <Alert tone="danger" role="alert">
                        {errors.workflow}
                    </Alert>
                ) : null}

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="min-w-0 space-y-6 lg:col-span-2">
                        {rule.branches.map((branch, index) => (
                            <Panel
                                key={index}
                                title={
                                    rule.branches.length > 1
                                        ? t('admin.clinical.branch_n', { n: index + 1 })
                                        : t('admin.clinical.condition')
                                }
                                description={t('admin.clinical.condition_hint')}
                            >
                                <p className="text-base font-medium text-ink">{branch.readable[locale.current]}</p>
                                <p className="mt-2 text-sm text-ink-subtle" lang={other} dir={other === 'ar' ? 'rtl' : 'ltr'}>
                                    {branch.readable[other]}
                                </p>

                                <dl className="mt-5 grid gap-4 border-t border-line pt-5 text-sm sm:grid-cols-3">
                                    <div>
                                        <dt className="text-ink-subtle">{t('admin.clinical.outcome')}</dt>
                                        <dd className="mt-1 flex flex-wrap gap-1.5">
                                            <PriorityBadge priority={branch.priority} />
                                            {Object.entries(branch.priorityByJourney).map(([journey, priority]) => (
                                                <span
                                                    key={journey}
                                                    className="inline-flex items-center gap-1 text-xs text-ink-muted"
                                                >
                                                    {t(`journey.${journey}.title`)}
                                                    <PriorityBadge priority={priority} />
                                                </span>
                                            ))}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-ink-subtle">{t('admin.clinical.stops_flow')}</dt>
                                        <dd className="mt-1 font-medium text-ink">
                                            {branch.stop ? t('admin.clinical.value.yes') : t('admin.clinical.value.no')}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-ink-subtle">{t('admin.clinical.flags')}</dt>
                                        <dd className="mt-1 flex flex-wrap gap-1.5">
                                            {branch.flags.length === 0 ? (
                                                <span className="text-ink-muted">{t('admin.clinical.none')}</span>
                                            ) : (
                                                branch.flags.map((flag) => (
                                                    <code
                                                        key={flag}
                                                        className="rounded-md bg-surface-sunken px-1.5 py-0.5 text-xs"
                                                        dir="ltr"
                                                    >
                                                        {flag}
                                                    </code>
                                                ))
                                            )}
                                        </dd>
                                    </div>
                                </dl>

                                {branch.guidance.length > 0 ? (
                                    <div className="mt-5">
                                        <h3 className="mb-2 text-sm font-semibold text-ink">
                                            {t('admin.clinical.guidance_texts')}
                                        </h3>
                                        {texts(branch.guidance)}
                                    </div>
                                ) : null}
                                {branch.topics.length > 0 ? (
                                    <div className="mt-5">
                                        <h3 className="mb-2 text-sm font-semibold text-ink">
                                            {t('admin.clinical.doctor_topics')}
                                        </h3>
                                        {texts(branch.topics)}
                                    </div>
                                ) : null}
                            </Panel>
                        ))}

                        <Panel title={t('admin.clinical.json')} description={t('admin.clinical.json_hint')}>
                            <pre
                                className="max-h-96 overflow-auto rounded-xl bg-brand-deep p-4 text-xs leading-relaxed text-mint"
                                dir="ltr"
                            >
                                {rule.json}
                            </pre>
                        </Panel>
                    </div>

                    <div className="min-w-0 space-y-6">
                        <Panel title={t('admin.clinical.review')}>
                            <div className="space-y-4 text-sm">
                                {rule.reviewComment !== null ? (
                                    <p className="rounded-xl bg-surface p-3 text-ink">
                                        <span className="font-semibold">{t('admin.content.reviewer_comment')}</span>{' '}
                                        {rule.reviewComment}
                                        {rule.reviewer !== null ? (
                                            <span className="mt-1 block text-ink-subtle">{rule.reviewer}</span>
                                        ) : null}
                                    </p>
                                ) : null}
                                {can.submit ? (
                                    <Button className="w-full" disabled={busy} onClick={() => act('submit')}>
                                        <Send aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                                        {t('admin.clinical.action.submit')}
                                    </Button>
                                ) : null}
                                {can.review ? (
                                    <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                                        <Button disabled={busy} onClick={() => setDecision('approve')}>
                                            <CircleCheck aria-hidden="true" className="size-4" />
                                            {t('admin.clinical.action.approve')}
                                        </Button>
                                        <Button variant="danger" disabled={busy} onClick={() => setDecision('reject')}>
                                            <CircleX aria-hidden="true" className="size-4" />
                                            {t('admin.clinical.action.reject')}
                                        </Button>
                                    </div>
                                ) : null}
                                {!can.submit && !can.review ? (
                                    <p className="text-ink-muted">{t('admin.clinical.no_actions')}</p>
                                ) : null}
                            </div>
                        </Panel>

                        <Panel title={t('admin.clinical.details')}>
                            <dl className="space-y-3 text-sm">
                                <div>
                                    <dt className="text-ink-subtle">{t('admin.clinical.section_label')}</dt>
                                    <dd className="font-medium text-ink">{t(`admin.clinical.section.${rule.section}`)}</dd>
                                </div>
                                <div>
                                    <dt className="text-ink-subtle">{t('admin.clinical.journeys')}</dt>
                                    <dd className="font-medium text-ink">
                                        {rule.journeys.map((journey) => t(`journey.${journey}.title`)).join(' · ')}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-ink-subtle">{t('admin.clinical.evidence')}</dt>
                                    <dd className="mt-1 flex flex-wrap gap-1.5">
                                        {rule.evidence.length === 0 ? (
                                            <Badge tone="warning">{t('admin.clinical.no_source')}</Badge>
                                        ) : (
                                            rule.evidence.map((item) => (
                                                <Badge key={item.source} tone="info">
                                                    <bdi dir="ltr">{item.source}</bdi> {item.support}
                                                </Badge>
                                            ))
                                        )}
                                    </dd>
                                </div>
                                {release !== null ? (
                                    <div>
                                        <dt className="text-ink-subtle">{t('admin.clinical.in_release')}</dt>
                                        <dd className="font-medium text-ink" dir="ltr">
                                            {release.version}
                                        </dd>
                                    </div>
                                ) : null}
                            </dl>
                        </Panel>

                        <Panel title={t('admin.clinical.affected_tests')}>
                            {release === null || !release.testsRun ? (
                                <p className="flex items-start gap-2 text-sm text-ink-muted">
                                    <FlaskConical aria-hidden="true" className="mt-0.5 size-4 shrink-0" />
                                    {t('admin.clinical.tests_not_run')}
                                </p>
                            ) : affectedTests.length === 0 ? (
                                <p className="text-sm text-ink-muted">{t('admin.clinical.no_affected_tests')}</p>
                            ) : (
                                <ul className="space-y-2 text-sm">
                                    {affectedTests.map((test) => (
                                        <li key={test.id} className="flex items-start gap-2">
                                            <Badge tone={test.passed ? 'success' : 'danger'}>
                                                <bdi dir="ltr">{test.id}</bdi>
                                            </Badge>
                                            <span className="text-ink-muted" dir="ltr" lang="en">
                                                {test.description}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Panel>
                    </div>
                </div>
            </div>

            <DecisionDialog
                decision={decision}
                busy={busy}
                onCancel={() => setDecision(null)}
                onConfirm={(comment) => decision !== null && act(decision, comment)}
            />
        </AdminLayout>
    );
}
