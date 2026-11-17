import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    CircleCheck,
    CircleX,
    FlaskConical,
    Layers,
    ListChecks,
    PencilLine,
    Rocket,
    Save,
    Send,
    ShieldCheck,
    Square,
    SquareCheck,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { DecisionDialog, ReleaseBadge } from '@/components/admin/ClinicalStatus';
import { ConfirmDialog } from '@/components/public/ConfirmDialog';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button, buttonClasses } from '@/components/ui/Button';
import { Panel } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { inputClasses } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type Selected = {
    version: string;
    status: string;
    sourceHash: string;
    importedFromCommit: string | null;
    submittedAt: string | null;
    approver: string | null;
    approvedAt: string | null;
    publisher: string | null;
    publishedAt: string | null;
    reviewComment: string | null;
    report: {
        passed: number;
        total: number;
        ranAt: string | null;
        current: boolean;
        results: { id: string; description: string; passed: boolean; failures: string[] }[];
    } | null;
    checklist: { testsPassed: boolean; rulesApproved: number; rulesTotal: number; textsApproved: boolean };
    texts: { id: number; key: string; ar: string | null; en: string | null; approved: boolean; updateUrl: string }[];
    questions: { id: number; key: string; section: string; ar: string | null; en: string | null; updateUrl: string }[];
    can: { runTests: boolean; submit: boolean; review: boolean; publish: boolean; editTexts?: boolean };
    actionUrl: string;
    publishUrl: string;
};

type ReleasesProps = {
    releases: { version: string; status: string; publishedAt: string | null; url: string }[];
    selected: Selected | null;
    environment: { demoMode: boolean; demoRuleset: string; assessmentEnabled: boolean; production: boolean };
    rulesUrl: string;
};

/**
 * Ruleset releases (M11C-FR-04, M11C-FR-07): tests, checklist, texts, medical approval and publishing.
 */
export default function Releases({ releases, selected, environment, rulesUrl }: ReleasesProps) {
    const t = useT();
    const { errors } = usePage().props as unknown as { errors: Record<string, string> };
    const [busy, setBusy] = useState(false);
    const [decision, setDecision] = useState<'approve' | 'reject' | null>(null);
    const [publishing, setPublishing] = useState(false);

    const act = (action: string, comment?: string) => {
        if (selected === null) {
            return;
        }

        setBusy(true);
        router.post(
            selected.actionUrl.replace('__action__', action),
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

    const publish = () => {
        if (selected === null) {
            return;
        }

        setBusy(true);
        router.post(
            selected.publishUrl,
            {},
            {
                onFinish: () => {
                    setBusy(false);
                    setPublishing(false);
                },
            },
        );
    };

    const check = (done: boolean, label: string) => (
        <li className="flex items-center gap-2.5 text-sm">
            {done ? (
                <SquareCheck aria-hidden="true" className="size-5 text-success" />
            ) : (
                <Square aria-hidden="true" className="size-5 text-ink-subtle" />
            )}
            <span className={done ? 'text-ink' : 'text-ink-muted'}>{label}</span>
        </li>
    );

    return (
        <AdminLayout>
            <Head title={t('admin.clinical.releases_title')} />
            <div className="space-y-6">
                <PageHeader
                    title={t('admin.clinical.releases_title')}
                    description={t('admin.clinical.releases_description')}
                    actions={
                        <Link href={rulesUrl} className={buttonClasses('secondary')}>
                            <ListChecks aria-hidden="true" className="size-4" />
                            {t('admin.clinical.rules_title')}
                        </Link>
                    }
                />

                <div className="grid gap-3 md:grid-cols-2">
                    <Alert tone={environment.demoMode ? 'warning' : 'info'} title={t('admin.clinical.env_title')}>
                        {environment.demoMode
                            ? t('admin.clinical.env_demo', { version: environment.demoRuleset })
                            : t('admin.clinical.env_published_only')}
                    </Alert>
                    <Alert
                        tone={environment.assessmentEnabled ? 'success' : 'danger'}
                        title={t('admin.clinical.kill_switch_title')}
                    >
                        {environment.assessmentEnabled ? t('admin.clinical.kill_switch_on') : t('admin.clinical.kill_switch_off')}
                    </Alert>
                </div>

                {errors.workflow !== undefined ? (
                    <Alert tone="danger" role="alert">
                        {errors.workflow}
                    </Alert>
                ) : null}

                {selected === null ? (
                    <EmptyState
                        icon={Layers}
                        title={t('admin.clinical.no_releases_title')}
                        description={t('admin.clinical.no_releases_body')}
                    />
                ) : (
                    <>
                        <nav aria-label={t('admin.clinical.releases_title')} className="flex flex-wrap gap-2">
                            {releases.map((release) => (
                                <Link
                                    key={release.version}
                                    href={release.url}
                                    aria-current={release.version === selected.version ? 'page' : undefined}
                                    className={`inline-flex min-h-10 items-center gap-2 rounded-full border px-4 text-sm font-medium transition-colors ${
                                        release.version === selected.version
                                            ? 'border-brand bg-brand-soft text-brand-strong'
                                            : 'border-line bg-surface-raised text-ink-muted hover:border-line-strong'
                                    }`}
                                >
                                    <bdi dir="ltr">{release.version}</bdi>
                                    <ReleaseBadge status={release.status} />
                                </Link>
                            ))}
                        </nav>

                        <div className="grid gap-6 lg:grid-cols-3">
                            <div className="space-y-6 lg:col-span-2">
                                <Panel
                                    title={t('admin.clinical.test_report')}
                                    description={
                                        selected.report !== null
                                            ? t('admin.clinical.report_summary', {
                                                  passed: selected.report.passed,
                                                  total: selected.report.total,
                                              })
                                            : t('admin.clinical.tests_not_run')
                                    }
                                    action={
                                        selected.can.runTests ? (
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                disabled={busy}
                                                onClick={() => act('run-tests')}
                                            >
                                                <FlaskConical aria-hidden="true" className="size-4" />
                                                {t('admin.clinical.action.run_tests')}
                                            </Button>
                                        ) : undefined
                                    }
                                >
                                    {selected.report === null ? (
                                        <p className="text-sm text-ink-muted">{t('admin.clinical.run_tests_hint')}</p>
                                    ) : (
                                        <>
                                            {!selected.report.current ? (
                                                <Alert tone="warning" className="mb-4">
                                                    {t('admin.clinical.report_stale')}
                                                </Alert>
                                            ) : null}
                                            <ul className="divide-y divide-line">
                                                {selected.report.results.map((result) => (
                                                    <li key={result.id} className="flex items-start gap-3 py-2.5 text-sm">
                                                        <Badge tone={result.passed ? 'success' : 'danger'}>
                                                            {result.passed ? t('admin.clinical.pass') : t('admin.clinical.fail')}
                                                        </Badge>
                                                        <span
                                                            className="w-14 shrink-0 font-mono text-xs leading-6 text-ink-subtle"
                                                            dir="ltr"
                                                        >
                                                            {result.id}
                                                        </span>
                                                        <span className="min-w-0 flex-1" dir="ltr" lang="en">
                                                            <span className="text-ink">{result.description}</span>
                                                            {result.failures.map((failure) => (
                                                                <span key={failure} className="block text-xs text-danger">
                                                                    {failure}
                                                                </span>
                                                            ))}
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </>
                                    )}
                                </Panel>

                                <Panel title={t('admin.clinical.guidance_texts')} description={t('admin.clinical.texts_hint')}>
                                    <details>
                                        <summary className="cursor-pointer text-sm font-semibold text-brand">
                                            {t('admin.clinical.show_texts', { count: selected.texts.length })}
                                        </summary>
                                        <ul className="mt-4 space-y-3">
                                            {selected.texts.map((text) => (
                                                <EditableTextItem
                                                    key={text.key}
                                                    itemKey={text.key}
                                                    ar={text.ar ?? ''}
                                                    en={text.en ?? ''}
                                                    approved={text.approved}
                                                    canEdit={Boolean(selected.can.editTexts)}
                                                    updateUrl={text.updateUrl}
                                                />
                                            ))}
                                        </ul>
                                    </details>
                                    <details className="mt-4 border-t border-line pt-4">
                                        <summary className="cursor-pointer text-sm font-semibold text-brand">
                                            {t('admin.clinical.show_questions', { count: selected.questions.length })}
                                        </summary>
                                        <ul className="mt-4 space-y-3">
                                            {selected.questions.map((question) => (
                                                <EditableTextItem
                                                    key={question.key}
                                                    itemKey={question.key}
                                                    ar={question.ar ?? ''}
                                                    en={question.en ?? ''}
                                                    approved={false}
                                                    canEdit={Boolean(selected.can.editTexts)}
                                                    updateUrl={question.updateUrl}
                                                />
                                            ))}
                                        </ul>
                                    </details>
                                </Panel>
                            </div>

                            <div className="space-y-6">
                                <Panel
                                    title={t('admin.clinical.release_status_title')}
                                    action={<ReleaseBadge status={selected.status} />}
                                >
                                    <ul className="space-y-2.5">
                                        {check(selected.checklist.testsPassed, t('admin.clinical.check_tests'))}
                                        {check(
                                            selected.checklist.rulesTotal > 0 &&
                                                selected.checklist.rulesApproved === selected.checklist.rulesTotal,
                                            t('admin.clinical.check_rules', {
                                                approved: selected.checklist.rulesApproved,
                                                total: selected.checklist.rulesTotal,
                                            }),
                                        )}
                                        {check(selected.checklist.textsApproved, t('admin.clinical.check_texts'))}
                                    </ul>

                                    {selected.reviewComment !== null ? (
                                        <p className="mt-4 rounded-xl bg-surface p-3 text-sm text-ink">
                                            <span className="font-semibold">{t('admin.content.reviewer_comment')}</span>{' '}
                                            {selected.reviewComment}
                                        </p>
                                    ) : null}

                                    <div className="mt-5 space-y-2 border-t border-line pt-5">
                                        {selected.can.submit ? (
                                            <Button className="w-full" disabled={busy} onClick={() => act('submit')}>
                                                <Send aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                                                {t('admin.clinical.action.submit_release')}
                                            </Button>
                                        ) : null}
                                        {selected.can.review ? (
                                            <>
                                                {!selected.checklist.textsApproved ? (
                                                    <Button
                                                        variant="secondary"
                                                        className="w-full"
                                                        disabled={busy}
                                                        onClick={() => act('approve-texts')}
                                                    >
                                                        <ShieldCheck aria-hidden="true" className="size-4" />
                                                        {t('admin.clinical.action.approve_texts')}
                                                    </Button>
                                                ) : null}
                                                <div className="grid grid-cols-2 gap-2">
                                                    <Button disabled={busy} onClick={() => setDecision('approve')}>
                                                        <CircleCheck aria-hidden="true" className="size-4" />
                                                        {t('admin.clinical.action.approve')}
                                                    </Button>
                                                    <Button
                                                        variant="danger"
                                                        disabled={busy}
                                                        onClick={() => setDecision('reject')}
                                                    >
                                                        <CircleX aria-hidden="true" className="size-4" />
                                                        {t('admin.clinical.action.reject')}
                                                    </Button>
                                                </div>
                                            </>
                                        ) : null}
                                        {selected.can.publish ? (
                                            <Button className="w-full" disabled={busy} onClick={() => setPublishing(true)}>
                                                <Rocket aria-hidden="true" className="size-4" />
                                                {t('admin.clinical.action.publish')}
                                            </Button>
                                        ) : null}
                                        {!selected.can.submit && !selected.can.review && !selected.can.publish ? (
                                            <p className="text-sm text-ink-muted">{t('admin.clinical.no_actions')}</p>
                                        ) : null}
                                    </div>
                                </Panel>

                                <Panel title={t('admin.clinical.details')}>
                                    <dl className="space-y-3 text-sm">
                                        <div>
                                            <dt className="text-ink-subtle">{t('admin.clinical.source_hash')}</dt>
                                            <dd className="font-mono text-xs break-all text-ink" dir="ltr">
                                                {selected.sourceHash}
                                            </dd>
                                        </div>
                                        {selected.approver !== null ? (
                                            <div>
                                                <dt className="text-ink-subtle">{t('admin.clinical.approved_by')}</dt>
                                                <dd className="text-ink">
                                                    {selected.approver} <bdi dir="ltr">{selected.approvedAt}</bdi>
                                                </dd>
                                            </div>
                                        ) : null}
                                        {selected.publisher !== null ? (
                                            <div>
                                                <dt className="text-ink-subtle">{t('admin.clinical.published_by')}</dt>
                                                <dd className="text-ink">
                                                    {selected.publisher} <bdi dir="ltr">{selected.publishedAt}</bdi>
                                                </dd>
                                            </div>
                                        ) : null}
                                    </dl>
                                </Panel>
                            </div>
                        </div>
                    </>
                )}
            </div>

            <DecisionDialog
                decision={decision}
                busy={busy}
                onCancel={() => setDecision(null)}
                onConfirm={(comment) => decision !== null && act(decision, comment)}
            />
            <ConfirmDialog
                open={publishing}
                title={t('admin.clinical.publish_title')}
                confirmLabel={t('admin.clinical.action.publish')}
                cancelLabel={t('clear.cancel')}
                busy={busy}
                onCancel={() => setPublishing(false)}
                onConfirm={publish}
            >
                <p>{t('admin.clinical.publish_body')}</p>
            </ConfirmDialog>
        </AdminLayout>
    );
}

function EditableTextItem({
    itemKey,
    ar,
    en,
    approved,
    canEdit,
    updateUrl,
}: {
    itemKey: string;
    ar: string;
    en: string;
    approved: boolean;
    canEdit: boolean;
    updateUrl: string;
}) {
    const t = useT();
    const [editing, setEditing] = useState(false);
    const [arText, setArText] = useState(ar);
    const [enText, setEnText] = useState(en);
    const [saving, setSaving] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        router.put(
            updateUrl,
            { ar: arText, en: enText },
            {
                preserveScroll: true,
                onSuccess: () => setEditing(false),
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <li className="rounded-xl bg-surface p-3 text-sm">
            <div className="flex items-center justify-between gap-2">
                <span className="font-mono text-xs text-ink-subtle" dir="ltr">
                    {itemKey}
                </span>
                <div className="flex items-center gap-2">
                    {approved ? <Badge tone="success">{t('admin.content.review.approved')}</Badge> : null}
                    {canEdit ? (
                        <Button size="sm" variant="ghost" onClick={() => setEditing((v) => !v)}>
                            <PencilLine aria-hidden="true" className="size-3.5" />
                            {t('admin.clinical.edit_text')}
                        </Button>
                    ) : null}
                </div>
            </div>
            {editing ? (
                <form onSubmit={submit} className="mt-3 space-y-2">
                    <textarea
                        rows={2}
                        dir="rtl"
                        lang="ar"
                        value={arText}
                        onChange={(e) => setArText(e.target.value)}
                        className={inputClasses}
                    />
                    <textarea
                        rows={2}
                        dir="ltr"
                        lang="en"
                        value={enText}
                        onChange={(e) => setEnText(e.target.value)}
                        className={inputClasses}
                    />
                    <div className="flex justify-end gap-2">
                        <Button size="sm" variant="ghost" onClick={() => setEditing(false)}>
                            {t('clear.cancel')}
                        </Button>
                        <Button type="submit" size="sm" disabled={saving}>
                            <Save aria-hidden="true" className="size-3.5" />
                            {t('admin.clinical.save_text')}
                        </Button>
                    </div>
                </form>
            ) : (
                <>
                    <p className="mt-1.5 text-ink" lang="ar" dir="rtl">
                        {ar}
                    </p>
                    <p className="mt-1 text-ink-muted" lang="en" dir="ltr">
                        {en}
                    </p>
                </>
            )}
        </li>
    );
}
