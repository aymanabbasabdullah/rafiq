import { Head, router, usePage } from '@inertiajs/react';
import { Filter, RotateCcw, ScrollText } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field, SelectInput, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

interface AuditActor {
    id: number;
    name: string;
    email: string;
}

interface AuditLogEntry {
    id: number;
    action: string;
    actor: AuditActor | null;
    subjectType: string | null;
    subjectId: number | null;
    summary: Record<string, unknown> | null;
    createdAt: string | null;
}

interface AuditFilters {
    actor_id: string;
    action: string;
    from: string;
    to: string;
}

interface AuditLogIndexProps {
    logs: AuditLogEntry[];
    actors: AuditActor[];
    filters: AuditFilters;
    filterUrl: string;
}

function formatTime(iso: string | null, locale: string): string {
    if (iso === null) {
        return '';
    }

    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-u-nu-latn' : 'en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

function subjectOf(log: AuditLogEntry): string {
    if (log.subjectType === null) {
        return '';
    }

    const type = log.subjectType.split('\\').pop() ?? log.subjectType;

    return log.subjectId !== null ? `${type} #${log.subjectId}` : type;
}

/**
 * Read-only audit log (M11A-FR-05, ADM-004): table on wide screens, cards on mobile (ui-ux-pro-max: Table Handling).
 */
export default function AuditLogIndex({ logs, actors, filters, filterUrl }: AuditLogIndexProps) {
    const t = useT();
    const { locale } = usePage().props;
    const [form, setForm] = useState<AuditFilters>(filters);
    const hasFilters = Object.values(filters).some((value) => value !== '');

    const submitFilter = (event: FormEvent) => {
        event.preventDefault();
        router.get(filterUrl, { ...form }, { preserveState: true, preserveScroll: true });
    };

    const resetFilter = () => {
        setForm({ actor_id: '', action: '', from: '', to: '' });
        router.get(filterUrl, {}, { preserveState: true });
    };

    return (
        <AdminLayout>
            <Head title={t('admin.audit.title')} />

            <div className="space-y-6">
                <div className="animate-fade-up">
                    <PageHeader
                        title={t('admin.audit.title')}
                        description={t('admin.audit.subtitle')}
                        actions={<Badge tone="neutral">{t('admin.audit.read_only')}</Badge>}
                    />
                </div>

                <Card as="section" className="animate-fade-up p-5">
                    <form
                        onSubmit={submitFilter}
                        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-[repeat(4,minmax(0,1fr))_auto] lg:items-end"
                    >
                        <Field id="filter-actor" label={t('admin.audit.filter_actor')}>
                            <SelectInput
                                id="filter-actor"
                                value={form.actor_id}
                                onChange={(e) => setForm({ ...form, actor_id: e.target.value })}
                            >
                                <option value="">{t('admin.audit.filter_all_actors')}</option>
                                {actors.map((actor) => (
                                    <option key={actor.id} value={String(actor.id)}>
                                        {actor.name}
                                    </option>
                                ))}
                            </SelectInput>
                        </Field>
                        <Field id="filter-action" label={t('admin.audit.filter_action')}>
                            <TextInput
                                id="filter-action"
                                dir="ltr"
                                placeholder="role.granted"
                                value={form.action}
                                onChange={(e) => setForm({ ...form, action: e.target.value })}
                            />
                        </Field>
                        <Field id="filter-from" label={t('admin.audit.filter_from')}>
                            <TextInput
                                id="filter-from"
                                type="date"
                                dir="ltr"
                                value={form.from}
                                onChange={(e) => setForm({ ...form, from: e.target.value })}
                            />
                        </Field>
                        <Field id="filter-to" label={t('admin.audit.filter_to')}>
                            <TextInput
                                id="filter-to"
                                type="date"
                                dir="ltr"
                                value={form.to}
                                onChange={(e) => setForm({ ...form, to: e.target.value })}
                            />
                        </Field>
                        <div className="flex gap-2 sm:col-span-2 lg:col-span-1">
                            <Button type="submit" className="flex-1 lg:flex-none">
                                <Filter aria-hidden="true" className="size-4" />
                                {t('admin.audit.filter_submit')}
                            </Button>
                            {hasFilters ? (
                                <Button
                                    variant="secondary"
                                    onClick={resetFilter}
                                    aria-label={t('admin.audit.filter_reset')}
                                    title={t('admin.audit.filter_reset')}
                                >
                                    <RotateCcw aria-hidden="true" className="size-4" />
                                </Button>
                            ) : null}
                        </div>
                    </form>
                </Card>

                {logs.length === 0 ? (
                    <EmptyState
                        icon={ScrollText}
                        title={t('admin.audit.empty')}
                        description={hasFilters ? t('admin.audit.empty_filtered') : undefined}
                    />
                ) : (
                    <>
                        <Card className="hidden overflow-hidden md:block">
                            <table className="w-full text-sm">
                                <thead className="bg-surface-sunken text-xs text-ink-muted">
                                    <tr>
                                        <th scope="col" className="px-5 py-3 text-start font-semibold">
                                            {t('admin.audit.col_time')}
                                        </th>
                                        <th scope="col" className="px-5 py-3 text-start font-semibold">
                                            {t('admin.audit.col_actor')}
                                        </th>
                                        <th scope="col" className="px-5 py-3 text-start font-semibold">
                                            {t('admin.audit.col_action')}
                                        </th>
                                        <th scope="col" className="px-5 py-3 text-start font-semibold">
                                            {t('admin.audit.col_subject')}
                                        </th>
                                        <th scope="col" className="px-5 py-3 text-start font-semibold">
                                            {t('admin.audit.col_summary')}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-line">
                                    {logs.map((log) => (
                                        <tr key={log.id} className="align-top transition-colors hover:bg-surface">
                                            <td className="px-5 py-3.5 whitespace-nowrap text-ink-muted">
                                                <bdi>{formatTime(log.createdAt, locale.current)}</bdi>
                                            </td>
                                            <td className="px-5 py-3.5 text-ink">
                                                {log.actor?.name ?? t('admin.audit.system_actor')}
                                            </td>
                                            <td className="px-5 py-3.5">
                                                <code
                                                    className="rounded-md bg-brand-soft px-2 py-0.5 text-xs text-brand-strong"
                                                    dir="ltr"
                                                >
                                                    {log.action}
                                                </code>
                                            </td>
                                            <td className="px-5 py-3.5 font-mono text-xs text-ink-muted" dir="ltr">
                                                {subjectOf(log)}
                                            </td>
                                            <td
                                                className="max-w-xs truncate px-5 py-3.5 font-mono text-xs text-ink-subtle"
                                                dir="ltr"
                                                title={log.summary ? JSON.stringify(log.summary) : ''}
                                            >
                                                {log.summary ? JSON.stringify(log.summary) : ''}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </Card>

                        <ul className="stagger space-y-3 md:hidden">
                            {logs.map((log) => (
                                <li key={log.id}>
                                    <Card className="p-4">
                                        <code
                                            className="rounded-md bg-brand-soft px-2 py-0.5 text-xs text-brand-strong"
                                            dir="ltr"
                                        >
                                            {log.action}
                                        </code>
                                        <p className="mt-2 text-sm text-ink">
                                            {log.actor?.name ?? t('admin.audit.system_actor')}
                                        </p>
                                        <p className="mt-0.5 text-xs text-ink-subtle">
                                            <bdi>{formatTime(log.createdAt, locale.current)}</bdi>
                                            {subjectOf(log) !== '' ? (
                                                <>
                                                    <span
                                                        aria-hidden="true"
                                                        className="mx-1.5 inline-block size-1 rounded-full bg-current align-middle"
                                                    />
                                                    <bdi className="font-mono">{subjectOf(log)}</bdi>
                                                </>
                                            ) : null}
                                        </p>
                                    </Card>
                                </li>
                            ))}
                        </ul>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
