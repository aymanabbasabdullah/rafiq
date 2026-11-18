import { Head, useForm } from '@inertiajs/react';
import { ExternalLink, Plus, Quote, Save } from 'lucide-react';
import type { FormEvent } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field, SelectInput, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type SourceRow = {
    id: number;
    title: string;
    publisher: string;
    url: string;
    publication_date: string | null;
    accessed_at: string | null;
    evidence_type: string;
    access_note: string | null;
    ai_use_restricted: boolean;
    articlesCount: number;
    updateUrl: string;
};

type SourcesProps = {
    sources: SourceRow[];
    evidenceTypes: string[];
    storeUrl: string;
    indexUrl: string;
    canManage: boolean;
};

type SourceForm = {
    title: string;
    publisher: string;
    url: string;
    publication_date: string;
    accessed_at: string;
    evidence_type: string;
    access_note: string;
    ai_use_restricted: boolean;
};

function SourceFields({
    form,
    evidenceTypes,
    prefix,
}: {
    form: ReturnType<typeof useForm<SourceForm>>;
    evidenceTypes: string[];
    prefix: string;
}) {
    const t = useT();
    const { data, setData, errors } = form;

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <div className="sm:col-span-2">
                <Field id={`${prefix}-title`} label={t('admin.sources.field.title')} error={errors.title}>
                    <TextInput
                        id={`${prefix}-title`}
                        dir="auto"
                        value={data.title}
                        onChange={(event) => setData('title', event.target.value)}
                    />
                </Field>
            </div>
            <Field id={`${prefix}-publisher`} label={t('admin.sources.field.publisher')} error={errors.publisher}>
                <TextInput
                    id={`${prefix}-publisher`}
                    dir="auto"
                    value={data.publisher}
                    onChange={(event) => setData('publisher', event.target.value)}
                />
            </Field>
            <Field id={`${prefix}-type`} label={t('admin.sources.field.evidence_type')}>
                <SelectInput
                    id={`${prefix}-type`}
                    value={data.evidence_type}
                    onChange={(event) => setData('evidence_type', event.target.value)}
                >
                    {evidenceTypes.map((type) => (
                        <option key={type} value={type}>
                            {t(`admin.sources.type.${type}`)}
                        </option>
                    ))}
                </SelectInput>
            </Field>
            <div className="sm:col-span-2">
                <Field id={`${prefix}-url`} label={t('admin.sources.field.url')} error={errors.url}>
                    <TextInput
                        id={`${prefix}-url`}
                        type="url"
                        dir="ltr"
                        placeholder="https://"
                        value={data.url}
                        onChange={(event) => setData('url', event.target.value)}
                    />
                </Field>
            </div>
            <Field id={`${prefix}-published`} label={t('admin.sources.field.publication_date')} error={errors.publication_date}>
                <TextInput
                    id={`${prefix}-published`}
                    dir="ltr"
                    placeholder="2025-06-26"
                    value={data.publication_date}
                    onChange={(event) => setData('publication_date', event.target.value)}
                />
            </Field>
            <Field id={`${prefix}-accessed`} label={t('admin.sources.field.accessed_at')} error={errors.accessed_at}>
                <TextInput
                    id={`${prefix}-accessed`}
                    type="date"
                    dir="ltr"
                    value={data.accessed_at}
                    onChange={(event) => setData('accessed_at', event.target.value)}
                />
            </Field>
            <div className="sm:col-span-2">
                <Field id={`${prefix}-note`} label={t('admin.sources.field.access_note')}>
                    <TextInput
                        id={`${prefix}-note`}
                        dir="auto"
                        value={data.access_note}
                        onChange={(event) => setData('access_note', event.target.value)}
                    />
                </Field>
            </div>
            <label className="flex cursor-pointer items-start gap-3 text-sm text-ink sm:col-span-2">
                <input
                    type="checkbox"
                    checked={data.ai_use_restricted}
                    onChange={(event) => setData('ai_use_restricted', event.target.checked)}
                    className="mt-0.5 size-4 accent-brand"
                />
                <span>
                    {t('admin.sources.field.ai_restricted')}
                    <span className="block text-xs text-ink-subtle">{t('admin.sources.field.ai_restricted_help')}</span>
                </span>
            </label>
        </div>
    );
}

function toForm(source?: SourceRow): SourceForm {
    return {
        title: source?.title ?? '',
        publisher: source?.publisher ?? '',
        url: source?.url ?? '',
        publication_date: source?.publication_date ?? '',
        accessed_at: source?.accessed_at ?? '',
        evidence_type: source?.evidence_type ?? 'public_health_body',
        access_note: source?.access_note ?? '',
        ai_use_restricted: source?.ai_use_restricted ?? false,
    };
}

function SourceItem({ source, evidenceTypes, canManage }: { source: SourceRow; evidenceTypes: string[]; canManage: boolean }) {
    const t = useT();
    const form = useForm<SourceForm>(toForm(source));

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(source.updateUrl, { preserveScroll: true });
    };

    return (
        <Card as="article" className="p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0" dir="auto">
                    <a
                        href={source.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-start gap-1.5 font-semibold text-ink hover:text-brand"
                    >
                        {source.title}
                        <ExternalLink aria-hidden="true" className="mt-1 size-3.5 shrink-0" />
                    </a>
                    <p className="text-sm text-ink-muted">
                        {source.publisher}
                        {source.publication_date !== null ? ` · ${source.publication_date}` : ''}
                    </p>
                </div>
                <div className="flex flex-wrap gap-1.5">
                    <Badge tone="info">{t(`admin.sources.type.${source.evidence_type}`)}</Badge>
                    <Badge>{t('admin.sources.used_in', { count: source.articlesCount })}</Badge>
                    {source.ai_use_restricted ? <Badge tone="warning">{t('admin.sources.ai_restricted_badge')}</Badge> : null}
                </div>
            </div>
            {canManage ? (
                <details className="mt-4">
                    <summary className="cursor-pointer text-sm font-medium text-brand">{t('admin.content.edit')}</summary>
                    <form onSubmit={save} className="mt-4 space-y-4" noValidate>
                        <SourceFields form={form} evidenceTypes={evidenceTypes} prefix={`source-${source.id}`} />
                        <Button type="submit" size="sm" disabled={form.processing || !form.isDirty}>
                            <Save aria-hidden="true" className="size-4" />
                            {t('admin.content.save')}
                        </Button>
                    </form>
                </details>
            ) : null}
        </Card>
    );
}

/**
 * Reference source register (ADM-011).
 */
export default function Sources({ sources, evidenceTypes, storeUrl, canManage }: SourcesProps) {
    const t = useT();
    const form = useForm<SourceForm>(toForm());

    const create = (event: FormEvent) => {
        event.preventDefault();
        form.post(storeUrl, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <AdminLayout>
            <Head title={t('admin.sources.title')} />
            <div className="space-y-8">
                <div className="animate-fade-up">
                    <PageHeader title={t('admin.sources.title')} description={t('admin.sources.subtitle')} />
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_26rem]">
                    <section className="order-2 space-y-4 xl:order-1">
                        {sources.length === 0 ? (
                            <EmptyState icon={Quote} title={t('admin.sources.empty')} />
                        ) : (
                            <div className="stagger space-y-4">
                                {sources.map((source) => (
                                    <SourceItem
                                        key={source.id}
                                        source={source}
                                        evidenceTypes={evidenceTypes}
                                        canManage={canManage}
                                    />
                                ))}
                            </div>
                        )}
                    </section>

                    {canManage ? (
                        <Card as="section" className="order-1 p-5 xl:sticky xl:top-24 xl:order-2">
                            <h2 className="flex items-center gap-2 font-semibold text-ink">
                                <Plus aria-hidden="true" className="size-5 text-brand" />
                                {t('admin.sources.add')}
                            </h2>
                            <form onSubmit={create} className="mt-5 space-y-4" noValidate>
                                <SourceFields form={form} evidenceTypes={evidenceTypes} prefix="new-source" />
                                <Button type="submit" disabled={form.processing} className="w-full">
                                    {t('admin.sources.add')}
                                </Button>
                            </form>
                        </Card>
                    ) : null}
                </div>
            </div>
        </AdminLayout>
    );
}
