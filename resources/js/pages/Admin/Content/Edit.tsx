import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Archive,
    ArrowLeft,
    CheckCircle2,
    Eye,
    EyeOff,
    History,
    Loader2,
    PencilLine,
    Plus,
    Save,
    Send,
    Trash2,
    Upload,
    XCircle,
} from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { PublishBadge, ReviewBadge } from '@/components/admin/ContentStatus';
import { DiffViewer } from '@/components/admin/DiffViewer';
import { ConfirmDialog } from '@/components/public/ConfirmDialog';
import { Badge } from '@/components/ui/Badge';
import { Button, buttonClasses } from '@/components/ui/Button';
import { Card, Panel } from '@/components/ui/Card';
import { Field, SelectInput, TextInput, inputClasses } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AdminLayout } from '@/layouts/AdminLayout';
import { postJson } from '@/lib/http';
import { useT } from '@/lib/i18n';

type LocaleKey = 'ar' | 'en';
type Action = 'submit' | 'approve' | 'reject' | 'publish' | 'hide' | 'archive';

type TranslationPayload = {
    id: number;
    title: string;
    slug: string;
    summary: string | null;
    body_markdown: string;
    reviewStatus: string;
    publishStatus: string;
    live: boolean;
    version: number;
    reviewComment: string | null;
    reviewDueAt: string | null;
    publicUrl: string | null;
    latestApproved: { version: number; title: string; body_markdown: string } | null;
    liveIsLatest: boolean;
    can: Record<Action, boolean>;
    actionUrl: string;
};

type SourceLink = { id: number; support_level: string; claim_note: string | null };

type EditProps = {
    article: {
        id: number;
        key: string;
        kind: string;
        contentCategoryId: number;
        aiEnabled: boolean;
        sortOrder: number;
        sources: SourceLink[];
        translations: Record<LocaleKey, TranslationPayload | null>;
    } | null;
    categories: { id: number; name: string }[];
    sourceOptions: { id: number; label: string }[];
    kinds: string[];
    supportLevels: string[];
    submitUrl: string;
    indexUrl: string;
    sourcesUrl: string;
    previewUrl: string;
};

type FormTranslation = { title: string; slug: string; summary: string; body_markdown: string };

const LOCALES: LocaleKey[] = ['ar', 'en'];

function slugify(value: string): string {
    return value
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[ً-ٰٟـ]/g, '')
        .replace(/[^\p{L}\p{N}]+/gu, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 120);
}

/**
 * Bilingual article editor with sources and the medical review workflow (M11B-FR-02..FR-05).
 */
export default function Edit({
    article,
    categories,
    sourceOptions,
    kinds,
    supportLevels,
    submitUrl,
    indexUrl,
    sourcesUrl,
    previewUrl,
}: EditProps) {
    const t = useT();
    const { errors: pageErrors } = usePage().props as { errors: Record<string, string> };
    const [tab, setTab] = useState<LocaleKey>('ar');
    const [preview, setPreview] = useState<Record<LocaleKey, string | null>>({ ar: null, en: null });
    const [previewing, setPreviewing] = useState(false);
    const [decision, setDecision] = useState<{ action: 'approve' | 'reject'; translation: TranslationPayload } | null>(null);
    const [comment, setComment] = useState('');
    const [acting, setActing] = useState(false);
    const [newSource, setNewSource] = useState('');

    const initialTranslation = (locale: LocaleKey): FormTranslation => {
        const existing = article?.translations[locale];

        return {
            title: existing?.title ?? '',
            slug: existing?.slug ?? '',
            summary: existing?.summary ?? '',
            body_markdown: existing?.body_markdown ?? '',
        };
    };

    const form = useForm({
        key: article?.key ?? '',
        content_category_id: article?.contentCategoryId ?? categories[0]?.id ?? 0,
        kind: article?.kind ?? 'article',
        ai_enabled: article?.aiEnabled ?? true,
        sort_order: article?.sortOrder ?? 0,
        sources: article?.sources ?? ([] as SourceLink[]),
        translations: { ar: initialTranslation('ar'), en: initialTranslation('en') },
    });
    const { data, setData, processing, errors, isDirty } = form;
    const fieldError = (path: string): string | undefined => (errors as Record<string, string | undefined>)[path];

    const current = data.translations[tab];
    const status = article?.translations[tab] ?? null;

    const updateTranslation = (field: keyof FormTranslation, value: string) => {
        const next = { ...current, [field]: value };

        if (field === 'title' && (current.slug === '' || current.slug === slugify(current.title))) {
            next.slug = slugify(value);
        }

        setData('translations', { ...data.translations, [tab]: next });
        setPreview((existing) => ({ ...existing, [tab]: null }));
    };

    const togglePreview = async () => {
        if (preview[tab] !== null) {
            setPreview((existing) => ({ ...existing, [tab]: null }));

            return;
        }

        setPreviewing(true);

        try {
            const response = await postJson<{ html: string }>(previewUrl, { markdown: current.body_markdown });
            setPreview((existing) => ({ ...existing, [tab]: response.html }));
        } finally {
            setPreviewing(false);
        }
    };

    const save = (event: FormEvent) => {
        event.preventDefault();

        if (article === null) {
            form.post(submitUrl, { preserveScroll: true });
        } else {
            form.put(submitUrl, { preserveScroll: true, onSuccess: () => form.setDefaults() });
        }
    };

    const runAction = (translation: TranslationPayload, action: Action, note?: string) => {
        setActing(true);
        router.post(
            translation.actionUrl.replace('__action__', action),
            { comment: note ?? null },
            {
                preserveScroll: true,
                onFinish: () => {
                    setActing(false);
                    setDecision(null);
                    setComment('');
                },
            },
        );
    };

    const addSource = () => {
        const id = Number(newSource);

        if (id > 0 && !data.sources.some((source) => source.id === id)) {
            setData('sources', [...data.sources, { id, support_level: 'direct', claim_note: null }]);
        }

        setNewSource('');
    };

    const sourceLabel = (id: number) => sourceOptions.find((option) => option.id === id)?.label ?? `#${id}`;
    const textDir = tab === 'ar' ? 'rtl' : 'ltr';

    return (
        <AdminLayout>
            <Head title={article === null ? t('admin.content.new_article') : t('admin.content.edit_article')} />

            <form onSubmit={save} className="space-y-6" noValidate>
                <div className="animate-fade-up">
                    <Link
                        href={indexUrl}
                        className="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-brand hover:underline"
                    >
                        <ArrowLeft aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                        {t('admin.content.back')}
                    </Link>
                    <PageHeader
                        title={
                            article === null
                                ? t('admin.content.new_article')
                                : (article.translations.ar?.title ?? article.translations.en?.title ?? article.key)
                        }
                        description={t('admin.content.editor_hint')}
                        actions={
                            <Button type="submit" disabled={processing || (article !== null && !isDirty)}>
                                {processing ? (
                                    <Loader2 aria-hidden="true" className="size-4 animate-spin" />
                                ) : (
                                    <Save aria-hidden="true" className="size-4" />
                                )}
                                {t('admin.content.save_draft')}
                            </Button>
                        }
                    />
                </div>

                {pageErrors.workflow !== undefined ? (
                    <p
                        role="alert"
                        className="rounded-xl border border-danger/30 bg-danger-soft px-4 py-3 text-sm font-medium text-danger"
                    >
                        {pageErrors.workflow}
                    </p>
                ) : null}
                {fieldError('translations') !== undefined ? (
                    <p
                        role="alert"
                        className="rounded-xl border border-danger/30 bg-danger-soft px-4 py-3 text-sm font-medium text-danger"
                    >
                        {fieldError('translations')}
                    </p>
                ) : null}

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="space-y-6">
                        <div
                            role="tablist"
                            aria-label={t('admin.content.languages')}
                            className="inline-flex rounded-xl bg-surface-sunken p-1"
                        >
                            {LOCALES.map((locale) => (
                                <button
                                    key={locale}
                                    type="button"
                                    role="tab"
                                    aria-selected={tab === locale}
                                    onClick={() => setTab(locale)}
                                    className={`min-h-10 cursor-pointer rounded-lg px-5 text-sm font-semibold transition-colors ${
                                        tab === locale ? 'bg-surface-raised text-ink shadow-sm' : 'text-ink-muted hover:text-ink'
                                    }`}
                                >
                                    {t(`admin.locale.${locale}`)}
                                    {article?.translations[locale] ? null : (
                                        <span className="ms-1.5 text-xs text-ink-subtle">{t('admin.content.new_badge')}</span>
                                    )}
                                </button>
                            ))}
                        </div>

                        {status !== null ? (
                            <Card className="animate-fade-up p-5">
                                <div className="flex flex-wrap items-center gap-2">
                                    <ReviewBadge status={status.reviewStatus} />
                                    <PublishBadge status={status.publishStatus} live={status.live} />
                                    {status.version > 0 ? <Badge>{t('learn.version', { version: status.version })}</Badge> : null}
                                    {status.live && !status.liveIsLatest ? (
                                        <Badge tone="info">{t('admin.content.live_older')}</Badge>
                                    ) : null}
                                    {status.publicUrl !== null ? (
                                        <a
                                            href={status.publicUrl}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="ms-auto text-sm font-medium text-brand hover:underline"
                                        >
                                            {t('admin.content.view_live')}
                                        </a>
                                    ) : null}
                                </div>

                                {status.reviewStatus === 'draft' && status.reviewComment !== null ? (
                                    <p className="mt-4 rounded-xl border border-warning/30 bg-warning-soft px-4 py-3 text-sm text-ink">
                                        <span className="font-semibold">{t('admin.content.reviewer_comment')}</span>{' '}
                                        {status.reviewComment}
                                    </p>
                                ) : null}

                                <div className="mt-4 flex flex-wrap gap-2">
                                    {status.can.submit ? (
                                        <Button
                                            size="sm"
                                            disabled={acting || isDirty}
                                            onClick={() => runAction(status, 'submit')}
                                        >
                                            <Send aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                                            {t('admin.content.action.submit')}
                                        </Button>
                                    ) : null}
                                    {status.can.approve ? (
                                        <Button
                                            size="sm"
                                            disabled={acting || isDirty}
                                            onClick={() => setDecision({ action: 'approve', translation: status })}
                                        >
                                            <CheckCircle2 aria-hidden="true" className="size-4" />
                                            {t('admin.content.action.approve')}
                                        </Button>
                                    ) : null}
                                    {status.can.reject ? (
                                        <Button
                                            size="sm"
                                            variant="danger"
                                            disabled={acting || isDirty}
                                            onClick={() => setDecision({ action: 'reject', translation: status })}
                                        >
                                            <XCircle aria-hidden="true" className="size-4" />
                                            {t('admin.content.action.reject')}
                                        </Button>
                                    ) : null}
                                    {status.can.publish ? (
                                        <Button
                                            size="sm"
                                            disabled={acting || isDirty}
                                            onClick={() => runAction(status, 'publish')}
                                        >
                                            <Upload aria-hidden="true" className="size-4" />
                                            {t('admin.content.action.publish')}
                                        </Button>
                                    ) : null}
                                    {status.can.hide ? (
                                        <Button
                                            size="sm"
                                            variant="secondary"
                                            disabled={acting}
                                            onClick={() => runAction(status, 'hide')}
                                        >
                                            <EyeOff aria-hidden="true" className="size-4" />
                                            {t('admin.content.action.hide')}
                                        </Button>
                                    ) : null}
                                    {status.can.archive ? (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            disabled={acting}
                                            onClick={() => runAction(status, 'archive')}
                                        >
                                            <Archive aria-hidden="true" className="size-4" />
                                            {t('admin.content.action.archive')}
                                        </Button>
                                    ) : null}
                                </div>
                                {isDirty ? <p className="mt-3 text-xs text-ink-subtle">{t('admin.content.save_first')}</p> : null}
                            </Card>
                        ) : null}

                        <Card className="space-y-5 p-5">
                            <Field
                                id="title"
                                label={t('admin.content.field.title')}
                                error={fieldError(`translations.${tab}.title`)}
                            >
                                <TextInput
                                    id="title"
                                    dir={textDir}
                                    value={current.title}
                                    onChange={(event) => updateTranslation('title', event.target.value)}
                                />
                            </Field>
                            <Field
                                id="slug"
                                label={t('admin.content.field.slug')}
                                help={t('admin.content.field.slug_help')}
                                error={fieldError(`translations.${tab}.slug`)}
                            >
                                <TextInput
                                    id="slug"
                                    dir="ltr"
                                    value={current.slug}
                                    onChange={(event) => updateTranslation('slug', event.target.value)}
                                />
                            </Field>
                            <Field
                                id="summary"
                                label={t('admin.content.field.summary')}
                                error={fieldError(`translations.${tab}.summary`)}
                            >
                                <textarea
                                    id="summary"
                                    dir={textDir}
                                    rows={2}
                                    maxLength={500}
                                    value={current.summary}
                                    onChange={(event) => updateTranslation('summary', event.target.value)}
                                    className={inputClasses}
                                />
                            </Field>

                            <div>
                                <div className="flex items-center justify-between gap-3">
                                    <label htmlFor="body" className="text-sm font-medium text-ink">
                                        {t('admin.content.field.body')}
                                    </label>
                                    <Button size="sm" variant="ghost" onClick={() => void togglePreview()}>
                                        {previewing ? (
                                            <Loader2 aria-hidden="true" className="size-4 animate-spin" />
                                        ) : preview[tab] !== null ? (
                                            <PencilLine aria-hidden="true" className="size-4" />
                                        ) : (
                                            <Eye aria-hidden="true" className="size-4" />
                                        )}
                                        {preview[tab] !== null ? t('admin.content.write') : t('admin.content.preview')}
                                    </Button>
                                </div>
                                {preview[tab] !== null ? (
                                    <div
                                        dir={textDir}
                                        className="rafiq-prose mt-2 min-h-80 rounded-xl border border-line bg-surface p-5"
                                        dangerouslySetInnerHTML={{ __html: preview[tab] ?? '' }}
                                    />
                                ) : (
                                    <textarea
                                        id="body"
                                        dir={textDir}
                                        rows={18}
                                        value={current.body_markdown}
                                        onChange={(event) => updateTranslation('body_markdown', event.target.value)}
                                        className={`${inputClasses} mt-2 font-mono text-sm leading-relaxed`}
                                    />
                                )}
                                <p className="mt-1.5 text-xs text-ink-subtle">{t('admin.content.markdown_help')}</p>
                                {fieldError(`translations.${tab}.body_markdown`) !== undefined ? (
                                    <p role="alert" className="mt-1.5 text-xs font-medium text-danger">
                                        {fieldError(`translations.${tab}.body_markdown`)}
                                    </p>
                                ) : null}
                            </div>
                        </Card>

                        {status?.latestApproved ? (
                            <details className="rounded-2xl border border-line bg-surface-raised p-5 shadow-card" open>
                                <summary className="flex cursor-pointer items-center gap-2 font-semibold text-ink">
                                    <History aria-hidden="true" className="size-4 text-brand" />
                                    {t('admin.content.last_approved', { version: status.latestApproved.version })}
                                </summary>
                                <p className="mt-4 font-semibold text-ink" dir={textDir}>
                                    {status.latestApproved.title}
                                </p>
                                <DiffViewer
                                    previousText={status.latestApproved.body_markdown}
                                    currentText={current.body_markdown}
                                    dir={textDir}
                                />
                            </details>
                        ) : null}
                    </div>

                    <div className="space-y-6 xl:sticky xl:top-24">
                        <Panel title={t('admin.content.meta')}>
                            <div className="space-y-4">
                                {article === null ? (
                                    <Field
                                        id="key"
                                        label={t('admin.content.field.key')}
                                        help={t('admin.content.field.key_help')}
                                        error={fieldError('key')}
                                    >
                                        <TextInput
                                            id="key"
                                            dir="ltr"
                                            value={data.key}
                                            onChange={(event) => setData('key', slugify(event.target.value))}
                                        />
                                    </Field>
                                ) : (
                                    <p className="text-xs text-ink-subtle">
                                        {t('admin.content.field.key')}{' '}
                                        <code dir="ltr" className="font-mono text-ink">
                                            {article.key}
                                        </code>
                                    </p>
                                )}
                                <Field
                                    id="category"
                                    label={t('admin.content.field.category')}
                                    error={fieldError('content_category_id')}
                                >
                                    <SelectInput
                                        id="category"
                                        value={data.content_category_id}
                                        onChange={(event) => setData('content_category_id', Number(event.target.value))}
                                    >
                                        {categories.map((category) => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </SelectInput>
                                </Field>
                                <Field id="kind" label={t('admin.content.field.kind')}>
                                    <SelectInput
                                        id="kind"
                                        value={data.kind}
                                        onChange={(event) => setData('kind', event.target.value)}
                                    >
                                        {kinds.map((kind) => (
                                            <option key={kind} value={kind}>
                                                {t(`admin.content.kind.${kind}`)}
                                            </option>
                                        ))}
                                    </SelectInput>
                                </Field>
                                <Field id="sort" label={t('admin.content.field.sort_order')}>
                                    <TextInput
                                        id="sort"
                                        type="number"
                                        min={0}
                                        dir="ltr"
                                        value={data.sort_order}
                                        onChange={(event) => setData('sort_order', Number(event.target.value))}
                                    />
                                </Field>
                                <label className="flex cursor-pointer items-start gap-3 text-sm text-ink">
                                    <input
                                        type="checkbox"
                                        checked={data.ai_enabled}
                                        onChange={(event) => setData('ai_enabled', event.target.checked)}
                                        className="mt-0.5 size-4 accent-brand"
                                    />
                                    <span>
                                        {t('admin.content.field.ai_enabled')}
                                        <span className="block text-xs text-ink-subtle">{t('admin.content.field.ai_help')}</span>
                                    </span>
                                </label>
                            </div>
                        </Panel>

                        <Panel
                            title={t('admin.content.sources')}
                            description={
                                data.kind === 'platform_guide'
                                    ? t('admin.content.sources_optional')
                                    : t('admin.content.sources_required')
                            }
                            action={
                                <Link href={sourcesUrl} className="text-xs font-medium text-brand hover:underline">
                                    {t('admin.content.manage_sources')}
                                </Link>
                            }
                        >
                            <ul className="space-y-3">
                                {data.sources.map((source, index) => (
                                    <li key={source.id} className="rounded-xl border border-line bg-surface p-3">
                                        <div className="flex items-start justify-between gap-2">
                                            <p className="text-sm font-medium text-ink" dir="ltr">
                                                {sourceLabel(source.id)}
                                            </p>
                                            <button
                                                type="button"
                                                aria-label={t('admin.content.remove_source')}
                                                onClick={() =>
                                                    setData(
                                                        'sources',
                                                        data.sources.filter((item) => item.id !== source.id),
                                                    )
                                                }
                                                className="cursor-pointer rounded-lg p-1 text-ink-subtle hover:bg-danger-soft hover:text-danger"
                                            >
                                                <Trash2 aria-hidden="true" className="size-4" />
                                            </button>
                                        </div>
                                        <SelectInput
                                            aria-label={t('admin.content.support_level')}
                                            value={source.support_level}
                                            onChange={(event) =>
                                                setData(
                                                    'sources',
                                                    data.sources.map((item, position) =>
                                                        position === index
                                                            ? { ...item, support_level: event.target.value }
                                                            : item,
                                                    ),
                                                )
                                            }
                                            className="mt-2"
                                        >
                                            {supportLevels.map((level) => (
                                                <option key={level} value={level}>
                                                    {t(`admin.content.support.${level}`)}
                                                </option>
                                            ))}
                                        </SelectInput>
                                    </li>
                                ))}
                            </ul>
                            <div className="mt-3 flex gap-2">
                                <SelectInput
                                    aria-label={t('admin.content.add_source')}
                                    value={newSource}
                                    onChange={(event) => setNewSource(event.target.value)}
                                >
                                    <option value="">{t('admin.content.choose_source')}</option>
                                    {sourceOptions
                                        .filter((option) => !data.sources.some((source) => source.id === option.id))
                                        .map((option) => (
                                            <option key={option.id} value={option.id}>
                                                {option.label}
                                            </option>
                                        ))}
                                </SelectInput>
                                <button
                                    type="button"
                                    onClick={addSource}
                                    aria-label={t('admin.content.add_source')}
                                    className={buttonClasses('secondary', 'md')}
                                >
                                    <Plus aria-hidden="true" className="size-4" />
                                </button>
                            </div>
                        </Panel>
                    </div>
                </div>
            </form>

            <ConfirmDialog
                open={decision !== null}
                title={decision?.action === 'reject' ? t('admin.content.reject_title') : t('admin.content.approve_title')}
                confirmLabel={
                    decision?.action === 'reject' ? t('admin.content.action.reject') : t('admin.content.action.approve')
                }
                cancelLabel={t('clear.cancel')}
                tone={decision?.action === 'reject' ? 'danger' : 'brand'}
                busy={acting || (decision?.action === 'reject' && comment.trim() === '')}
                onCancel={() => {
                    setDecision(null);
                    setComment('');
                }}
                onConfirm={() => decision !== null && runAction(decision.translation, decision.action, comment)}
            >
                <p className="mb-3 text-sm">
                    {decision?.action === 'reject' ? t('admin.content.reject_body') : t('admin.content.approve_body')}
                </p>
                <label htmlFor="decision-comment" className="text-sm font-medium text-ink">
                    {decision?.action === 'reject' ? t('admin.content.comment_required') : t('admin.content.comment_optional')}
                </label>
                <textarea
                    id="decision-comment"
                    rows={3}
                    value={comment}
                    onChange={(event) => setComment(event.target.value)}
                    className={`${inputClasses} mt-1.5`}
                />
            </ConfirmDialog>
        </AdminLayout>
    );
}
