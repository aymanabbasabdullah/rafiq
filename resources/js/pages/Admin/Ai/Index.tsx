import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Activity, Bot, CircleDollarSign, FilePlus2, FlaskConical, Power, Rocket, Save, Timer } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { ConfirmDialog } from '@/components/public/ConfirmDialog';
import { Alert } from '@/components/ui/Alert';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Panel } from '@/components/ui/Card';
import { Field, SelectInput, TextInput, inputClasses } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type Settings = {
    enabled: boolean;
    welcome: { ar: string | null; en: string | null };
    suggestions: Record<string, { ar: string[]; en: string[] }>;
    default_simplification: number;
    daily_budget_usd: number;
    limits: Partial<Record<'per_session_per_minute' | 'per_ip_per_hour' | 'grounding_chunks', number>>;
};

type Configuration = {
    id: number;
    version: number;
    status: string;
    settings: Settings;
    evalReport: {
        total: number;
        passed: number;
        failed: number;
        ran_at: string;
        failures: { id: string; expected: string; actual: string }[];
    } | null;
    evalCurrent: boolean;
};

type AiProps = {
    status: {
        masterSwitch: boolean;
        enabled: boolean;
        model: string | null;
        corePromptVersion: string;
        activeVersion: number | null;
    };
    draft: Configuration | null;
    active: Configuration | null;
    history: { version: number; status: string; activatedAt: string | null; activatedBy: string | null }[];
    ceilings: { dailyBudgetUsd: number; limits: Record<string, number> };
    usage: {
        byOutcome: { outcome: string; total: number }[];
        todayRequests: number;
        todayModelCalls: number;
        todayCostUsd: number;
        budgetUsd: number;
        pricingSet: boolean;
        callCap: number;
        averageLatencyMs: number;
    };
    corePrompt: { ar: string; en: string };
    contexts: string[];
    urls: { createDraft: string; update: string | null; evaluate: string | null; activate: string | null; disable: string };
};

const LIMIT_KEYS = ['per_session_per_minute', 'per_ip_per_hour', 'grounding_chunks'] as const;

/**
 * AI configuration (Module-11-D): safe fields only, evaluation before activation, immediate switch-off,
 * read-only core prompt and a usage panel built from metadata (no conversation text exists anywhere).
 */
export default function AiIndex({ status, draft, active, history, ceilings, usage, corePrompt, contexts, urls }: AiProps) {
    const t = useT();
    const { errors: pageErrors } = usePage().props as unknown as { errors: Record<string, string> };
    const [confirm, setConfirm] = useState<'activate' | 'disable' | null>(null);
    const [busy, setBusy] = useState(false);

    const form = useForm<Settings>(draft?.settings ?? active?.settings ?? ({} as Settings));
    const { data, setData, errors, processing } = form;
    const fieldError = (path: string): string | undefined => (errors as Record<string, string | undefined>)[path];

    const save = (event: FormEvent) => {
        event.preventDefault();

        if (urls.update !== null) {
            form.put(urls.update, { preserveScroll: true });
        }
    };

    const post = (url: string | null) => {
        if (url === null) {
            return;
        }

        setBusy(true);
        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setBusy(false);
                    setConfirm(null);
                },
            },
        );
    };

    const setSuggestions = (context: string, locale: 'ar' | 'en', value: string) => {
        const lines = value.split('\n').slice(0, 3);
        setData('suggestions', {
            ...data.suggestions,
            [context]: { ...(data.suggestions?.[context] ?? { ar: [], en: [] }), [locale]: lines },
        });
    };

    return (
        <AdminLayout>
            <Head title={t('admin.ai.title')} />
            <div className="space-y-6">
                <PageHeader
                    title={t('admin.ai.title')}
                    description={t('admin.ai.description')}
                    actions={
                        status.enabled ? (
                            <Button variant="danger" disabled={busy} onClick={() => setConfirm('disable')}>
                                <Power aria-hidden="true" className="size-4" />
                                {t('admin.ai.action.disable')}
                            </Button>
                        ) : undefined
                    }
                />

                <div className="grid gap-3 md:grid-cols-2">
                    <Alert
                        tone={status.enabled ? 'success' : 'warning'}
                        title={status.enabled ? t('admin.ai.status_on') : t('admin.ai.status_off')}
                    >
                        {status.masterSwitch ? t('admin.ai.master_on') : t('admin.ai.master_off')}
                    </Alert>
                    <Alert tone="info" title={t('admin.ai.model_title')}>
                        <span className="block" dir="ltr">
                            {status.model ?? t('admin.ai.model_none')}
                        </span>
                        <span className="block">{t('admin.ai.core_prompt_version', { version: status.corePromptVersion })}</span>
                        {status.activeVersion !== null ? (
                            <span className="block">{t('admin.ai.active_version', { version: status.activeVersion })}</span>
                        ) : null}
                    </Alert>
                </div>

                {pageErrors.workflow !== undefined ? (
                    <Alert tone="danger" role="alert">
                        {pageErrors.workflow}
                    </Alert>
                ) : null}

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard label={t('admin.ai.today_requests')} value={usage.todayRequests} icon={Activity} tone="brand" />
                    <StatCard
                        label={t('admin.ai.today_model_calls')}
                        value={usage.todayModelCalls}
                        icon={Bot}
                        tone="info"
                        hint={usage.pricingSet ? undefined : t('admin.ai.call_cap', { cap: usage.callCap })}
                    />
                    <StatCard
                        label={t('admin.ai.today_cost')}
                        value={
                            usage.pricingSet
                                ? `$${usage.todayCostUsd.toFixed(2)} / $${usage.budgetUsd.toFixed(2)}`
                                : t('admin.ai.cost_unknown')
                        }
                        icon={CircleDollarSign}
                        tone="warning"
                    />
                    <StatCard label={t('admin.ai.latency')} value={`${usage.averageLatencyMs} ms`} icon={Timer} tone="neutral" />
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="min-w-0 space-y-6 lg:col-span-2">
                        {draft === null ? (
                            <Panel title={t('admin.ai.draft_title')} description={t('admin.ai.no_draft')}>
                                <Button disabled={busy} onClick={() => post(urls.createDraft)}>
                                    <FilePlus2 aria-hidden="true" className="size-4" />
                                    {t('admin.ai.action.create_draft')}
                                </Button>
                            </Panel>
                        ) : (
                            <form onSubmit={save}>
                                <Panel
                                    title={t('admin.ai.draft_version', { version: draft.version })}
                                    description={t('admin.ai.safe_fields_hint')}
                                    action={
                                        <Button type="submit" size="sm" disabled={processing}>
                                            <Save aria-hidden="true" className="size-4" />
                                            {t('admin.ai.action.save')}
                                        </Button>
                                    }
                                >
                                    <div className="space-y-5">
                                        <label className="flex items-center gap-3 text-sm font-medium text-ink">
                                            <input
                                                type="checkbox"
                                                checked={data.enabled}
                                                onChange={(event) => setData('enabled', event.target.checked)}
                                                className="size-4 accent-brand"
                                            />
                                            {t('admin.ai.field.enabled')}
                                        </label>

                                        <div className="grid gap-4 md:grid-cols-2">
                                            {(['ar', 'en'] as const).map((locale) => (
                                                <Field
                                                    key={locale}
                                                    id={`welcome-${locale}`}
                                                    label={t(`admin.ai.field.welcome_${locale}`)}
                                                    error={fieldError(`welcome.${locale}`)}
                                                    help={t('admin.ai.field.welcome_help')}
                                                >
                                                    <textarea
                                                        id={`welcome-${locale}`}
                                                        rows={3}
                                                        maxLength={400}
                                                        dir={locale === 'ar' ? 'rtl' : 'ltr'}
                                                        value={data.welcome?.[locale] ?? ''}
                                                        onChange={(event) =>
                                                            setData('welcome', { ...data.welcome, [locale]: event.target.value })
                                                        }
                                                        className={inputClasses}
                                                    />
                                                </Field>
                                            ))}
                                        </div>

                                        <fieldset>
                                            <legend className="text-sm font-semibold text-ink">
                                                {t('admin.ai.field.suggestions')}
                                            </legend>
                                            <p className="mt-0.5 text-xs text-ink-subtle">
                                                {t('admin.ai.field.suggestions_help')}
                                            </p>
                                            <div className="mt-3 space-y-4">
                                                {contexts.map((context) => (
                                                    <div key={context} className="grid gap-3 md:grid-cols-2">
                                                        {(['ar', 'en'] as const).map((locale) => (
                                                            <Field
                                                                key={locale}
                                                                id={`suggest-${context}-${locale}`}
                                                                label={t('admin.ai.field.suggestions_for', {
                                                                    context: t(`admin.ai.context.${context}`),
                                                                    language: locale === 'ar' ? 'العربية' : 'English',
                                                                })}
                                                                error={
                                                                    fieldError(`suggestions.${context}.${locale}.0`) ??
                                                                    fieldError(`suggestions.${context}.${locale}.1`) ??
                                                                    fieldError(`suggestions.${context}.${locale}.2`)
                                                                }
                                                            >
                                                                <textarea
                                                                    id={`suggest-${context}-${locale}`}
                                                                    rows={3}
                                                                    dir={locale === 'ar' ? 'rtl' : 'ltr'}
                                                                    value={(data.suggestions?.[context]?.[locale] ?? []).join(
                                                                        '\n',
                                                                    )}
                                                                    onChange={(event) =>
                                                                        setSuggestions(context, locale, event.target.value)
                                                                    }
                                                                    className={inputClasses}
                                                                />
                                                            </Field>
                                                        ))}
                                                    </div>
                                                ))}
                                            </div>
                                        </fieldset>

                                        <div className="grid gap-4 md:grid-cols-2">
                                            <Field id="simplification" label={t('admin.ai.field.simplification')}>
                                                <SelectInput
                                                    id="simplification"
                                                    value={data.default_simplification}
                                                    onChange={(event) =>
                                                        setData('default_simplification', Number(event.target.value))
                                                    }
                                                >
                                                    {[1, 2, 3].map((level) => (
                                                        <option key={level} value={level}>
                                                            {t(`ai.simplification_${level}`)}
                                                        </option>
                                                    ))}
                                                </SelectInput>
                                            </Field>
                                            <Field
                                                id="budget"
                                                label={t('admin.ai.field.budget', { max: ceilings.dailyBudgetUsd })}
                                                error={fieldError('daily_budget_usd')}
                                            >
                                                <TextInput
                                                    id="budget"
                                                    type="number"
                                                    step="0.01"
                                                    min={0}
                                                    max={ceilings.dailyBudgetUsd}
                                                    value={data.daily_budget_usd}
                                                    onChange={(event) => setData('daily_budget_usd', Number(event.target.value))}
                                                />
                                            </Field>
                                            {LIMIT_KEYS.map((key) => (
                                                <Field
                                                    key={key}
                                                    id={`limit-${key}`}
                                                    label={t(`admin.ai.field.limit_${key}`, { max: ceilings.limits[key] ?? 0 })}
                                                    error={fieldError(`limits.${key}`)}
                                                >
                                                    <TextInput
                                                        id={`limit-${key}`}
                                                        type="number"
                                                        min={1}
                                                        max={ceilings.limits[key]}
                                                        value={data.limits?.[key] ?? ''}
                                                        placeholder={String(ceilings.limits[key])}
                                                        onChange={(event) =>
                                                            setData('limits', {
                                                                ...data.limits,
                                                                [key]:
                                                                    event.target.value === ''
                                                                        ? undefined
                                                                        : Number(event.target.value),
                                                            })
                                                        }
                                                    />
                                                </Field>
                                            ))}
                                        </div>
                                    </div>
                                </Panel>
                            </form>
                        )}

                        <Panel title={t('admin.ai.core_prompt')} description={t('admin.ai.core_prompt_hint')}>
                            <details>
                                <summary className="cursor-pointer text-sm font-semibold text-brand">
                                    {t('admin.ai.show_core_prompt')}
                                </summary>
                                <pre
                                    className="mt-3 max-h-96 overflow-auto rounded-xl bg-brand-deep p-4 text-xs leading-relaxed whitespace-pre-wrap text-mint"
                                    dir="ltr"
                                >
                                    {corePrompt.en}
                                </pre>
                            </details>
                        </Panel>
                    </div>

                    <div className="min-w-0 space-y-6">
                        {draft !== null ? (
                            <Panel title={t('admin.ai.activation')}>
                                <div className="space-y-3 text-sm">
                                    {draft.evalReport !== null ? (
                                        <p className={draft.evalCurrent ? 'text-success' : 'text-danger'}>
                                            {t('admin.ai.eval_summary', {
                                                passed: draft.evalReport.passed,
                                                total: draft.evalReport.total,
                                            })}
                                        </p>
                                    ) : (
                                        <p className="text-ink-muted">{t('admin.ai.eval_missing')}</p>
                                    )}
                                    {draft.evalReport?.failures.map((failure) => (
                                        <p key={failure.id} className="font-mono text-xs text-danger" dir="ltr">
                                            {t('admin.ai.eval_failure', {
                                                id: failure.id,
                                                expected: failure.expected,
                                                actual: failure.actual,
                                            })}
                                        </p>
                                    ))}
                                    <Button
                                        variant="secondary"
                                        className="w-full"
                                        disabled={busy}
                                        onClick={() => post(urls.evaluate)}
                                    >
                                        <FlaskConical aria-hidden="true" className="size-4" />
                                        {t('admin.ai.action.evaluate')}
                                    </Button>
                                    <Button
                                        className="w-full"
                                        disabled={busy || !draft.evalCurrent}
                                        onClick={() => setConfirm('activate')}
                                    >
                                        <Rocket aria-hidden="true" className="size-4" />
                                        {t('admin.ai.action.activate')}
                                    </Button>
                                </div>
                            </Panel>
                        ) : null}

                        <Panel title={t('admin.ai.outcomes')} description={t('admin.ai.outcomes_hint')}>
                            <ul className="space-y-2 text-sm">
                                {usage.byOutcome.map((row) => (
                                    <li key={row.outcome} className="flex items-center justify-between gap-3">
                                        <span className="text-ink-muted">{t(`admin.ai.outcome.${row.outcome}`)}</span>
                                        <Badge
                                            tone={
                                                row.outcome === 'answered'
                                                    ? 'success'
                                                    : row.outcome === 'blocked_redflag'
                                                      ? 'danger'
                                                      : 'neutral'
                                            }
                                        >
                                            <bdi dir="ltr">{row.total}</bdi>
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        </Panel>

                        <Panel title={t('admin.ai.history')}>
                            <ul className="space-y-2 text-sm">
                                {history.length === 0 ? <li className="text-ink-muted">{t('admin.ai.history_empty')}</li> : null}
                                {history.map((row) => (
                                    <li key={row.version} className="flex items-center justify-between gap-3">
                                        <span className="text-ink">{t('admin.ai.version_n', { version: row.version })}</span>
                                        <Badge tone={row.status === 'active' ? 'brand' : 'neutral'}>
                                            {t(`admin.ai.status.${row.status}`)}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        </Panel>
                    </div>
                </div>
            </div>

            <ConfirmDialog
                open={confirm !== null}
                title={confirm === 'disable' ? t('admin.ai.disable_title') : t('admin.ai.activate_title')}
                confirmLabel={confirm === 'disable' ? t('admin.ai.action.disable') : t('admin.ai.action.activate')}
                cancelLabel={t('clear.cancel')}
                tone={confirm === 'disable' ? 'danger' : 'brand'}
                busy={busy}
                onCancel={() => setConfirm(null)}
                onConfirm={() => post(confirm === 'disable' ? urls.disable : urls.activate)}
            >
                <p>{confirm === 'disable' ? t('admin.ai.disable_body') : t('admin.ai.activate_body')}</p>
            </ConfirmDialog>
        </AdminLayout>
    );
}
