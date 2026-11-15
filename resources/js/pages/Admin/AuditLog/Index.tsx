import { Head, router } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
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

/**
 * Read-only audit log viewer (Module-11 §14.3, M11A-FR-05, ADM-004).
 */
export default function AuditLogIndex({ logs, actors, filters, filterUrl }: AuditLogIndexProps) {
    const t = useT();
    const [form, setForm] = useState<AuditFilters>(filters);

    const submitFilter = (event: FormEvent) => {
        event.preventDefault();
        router.get(filterUrl, { ...form }, { preserveState: true });
    };

    const resetFilter = () => {
        const empty: AuditFilters = { actor_id: '', action: '', from: '', to: '' };
        setForm(empty);
        router.get(filterUrl, {}, { preserveState: true });
    };

    return (
        <AdminLayout>
            <Head title={t('admin.audit.title')} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-ink">{t('admin.audit.title')}</h1>
                    <p className="mt-1 text-sm text-ink-muted">{t('admin.audit.subtitle')}</p>
                </div>

                <form
                    onSubmit={submitFilter}
                    className="grid gap-4 rounded-xl border border-line bg-surface-raised p-4 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <div>
                        <label htmlFor="filter-actor" className="block text-xs font-medium text-ink">
                            {t('admin.audit.filter_actor')}
                        </label>
                        <select
                            id="filter-actor"
                            value={form.actor_id}
                            onChange={(e) => setForm({ ...form, actor_id: e.target.value })}
                            className="mt-1 block w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-sm text-ink focus:border-brand focus:outline-hidden"
                        >
                            <option value="">{t('admin.audit.all_actors')}</option>
                            {actors.map((actor) => (
                                <option key={actor.id} value={String(actor.id)}>
                                    {actor.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label htmlFor="filter-action" className="block text-xs font-medium text-ink">
                            {t('admin.audit.filter_action')}
                        </label>
                        <input
                            id="filter-action"
                            type="text"
                            dir="ltr"
                            value={form.action}
                            onChange={(e) => setForm({ ...form, action: e.target.value })}
                            className="mt-1 block w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-sm text-ink focus:border-brand focus:outline-hidden"
                        />
                    </div>

                    <div>
                        <label htmlFor="filter-from" className="block text-xs font-medium text-ink">
                            {t('admin.audit.filter_from')}
                        </label>
                        <input
                            id="filter-from"
                            type="date"
                            dir="ltr"
                            value={form.from}
                            onChange={(e) => setForm({ ...form, from: e.target.value })}
                            className="mt-1 block w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-sm text-ink focus:border-brand focus:outline-hidden"
                        />
                    </div>

                    <div>
                        <label htmlFor="filter-to" className="block text-xs font-medium text-ink">
                            {t('admin.audit.filter_to')}
                        </label>
                        <input
                            id="filter-to"
                            type="date"
                            dir="ltr"
                            value={form.to}
                            onChange={(e) => setForm({ ...form, to: e.target.value })}
                            className="mt-1 block w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-sm text-ink focus:border-brand focus:outline-hidden"
                        />
                    </div>

                    <div className="flex items-center gap-2 sm:col-span-2 lg:col-span-4">
                        <button
                            type="submit"
                            className="inline-flex min-h-9 items-center rounded-lg bg-brand px-4 py-1.5 text-xs font-semibold text-white hover:bg-brand-strong"
                        >
                            {t('admin.audit.filter_apply')}
                        </button>
                        <button
                            type="button"
                            onClick={resetFilter}
                            className="inline-flex min-h-9 items-center rounded-lg border border-line bg-surface px-4 py-1.5 text-xs font-medium text-ink hover:border-brand"
                        >
                            {t('admin.audit.filter_reset')}
                        </button>
                    </div>
                </form>

                {logs.length === 0 ? (
                    <div className="rounded-xl border border-line bg-surface-raised p-8 text-center text-sm text-ink-muted">
                        {t('admin.audit.empty')}
                    </div>
                ) : (
                    <div className="overflow-x-auto rounded-xl border border-line bg-surface-raised">
                        <table className="w-full text-start text-sm">
                            <thead className="border-b border-line bg-surface text-xs text-ink-muted">
                                <tr>
                                    <th className="px-4 py-3 text-start font-medium">{t('admin.audit.col_time')}</th>
                                    <th className="px-4 py-3 text-start font-medium">{t('admin.audit.col_actor')}</th>
                                    <th className="px-4 py-3 text-start font-medium">{t('admin.audit.col_action')}</th>
                                    <th className="px-4 py-3 text-start font-medium">{t('admin.audit.col_subject')}</th>
                                    <th className="px-4 py-3 text-start font-medium">{t('admin.audit.col_summary')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-line">
                                {logs.map((log) => {
                                    const subjectDisplay =
                                        log.subjectType && log.subjectId !== null
                                            ? `${log.subjectType} #${log.subjectId}`
                                            : (log.subjectType ?? '');
                                    const summaryDisplay = log.summary ? JSON.stringify(log.summary) : '';

                                    return (
                                        <tr key={log.id} className="align-top">
                                            <td className="px-4 py-3 font-mono text-xs text-ink-muted" dir="ltr">
                                                {log.createdAt}
                                            </td>
                                            <td className="px-4 py-3 text-xs text-ink">
                                                {log.actor ? log.actor.name : t('admin.audit.system_actor')}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs text-brand" dir="ltr">
                                                {log.action}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs text-ink-muted" dir="ltr">
                                                {subjectDisplay}
                                            </td>
                                            <td className="px-4 py-3 font-mono text-xs text-ink-muted" dir="ltr">
                                                {summaryDisplay}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
