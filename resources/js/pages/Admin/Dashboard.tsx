import { Head } from '@inertiajs/react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

interface DashboardSummary {
    pendingReviewsCount: number;
    reviewsDueSoonCount: number;
    activeRuleset: string | null;
    aiEnabled: boolean;
    demoMode: boolean;
    activeAdminsCount: number;
    recentAuditCount: number | null;
}

interface DashboardProps {
    recoveryCodes?: string[] | null;
    summary: DashboardSummary;
}

/**
 * Admin dashboard home page (Module-11 §14.1).
 */
export default function Dashboard({ recoveryCodes, summary }: DashboardProps) {
    const t = useT();

    return (
        <AdminLayout>
            <Head title={t('admin.dashboard.title')} />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-ink">{t('admin.dashboard.title')}</h1>
                    <p className="mt-1 text-sm text-ink-muted">{t('admin.dashboard.subtitle')}</p>
                </div>

                {recoveryCodes && recoveryCodes.length > 0 ? (
                    <section
                        aria-labelledby="recovery-codes-title"
                        className="rounded-xl border border-warning bg-surface-raised p-5 shadow-xs"
                    >
                        <h2 id="recovery-codes-title" className="text-base font-bold text-ink">
                            {t('admin.two_factor.recovery_saved_title')}
                        </h2>
                        <p className="mt-1 text-sm text-ink-muted">{t('admin.two_factor.recovery_saved_body')}</p>
                        <ul className="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4" dir="ltr">
                            {recoveryCodes.map((code) => (
                                <li
                                    key={code}
                                    className="rounded-md border border-line bg-surface px-3 py-1.5 font-mono text-xs text-ink"
                                >
                                    {code}
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div className="rounded-xl border border-line bg-surface-raised p-5">
                        <p className="text-xs font-medium text-ink-muted">{t('admin.dashboard.pending_reviews')}</p>
                        <p className="mt-2 text-2xl font-bold text-ink">{summary.pendingReviewsCount}</p>
                    </div>

                    <div className="rounded-xl border border-line bg-surface-raised p-5">
                        <p className="text-xs font-medium text-ink-muted">{t('admin.dashboard.reviews_due_soon')}</p>
                        <p className="mt-2 text-2xl font-bold text-ink">{summary.reviewsDueSoonCount}</p>
                    </div>

                    <div className="rounded-xl border border-line bg-surface-raised p-5">
                        <p className="text-xs font-medium text-ink-muted">{t('admin.dashboard.active_ruleset')}</p>
                        <p className="mt-2 text-base font-semibold text-ink">
                            {summary.activeRuleset ?? t('admin.dashboard.not_configured')}
                        </p>
                    </div>

                    <div className="rounded-xl border border-line bg-surface-raised p-5">
                        <p className="text-xs font-medium text-ink-muted">{t('admin.dashboard.ai_status')}</p>
                        <p className="mt-2 text-base font-semibold text-ink">
                            {summary.aiEnabled ? t('admin.dashboard.enabled') : t('admin.dashboard.disabled')}
                        </p>
                    </div>

                    <div className="rounded-xl border border-line bg-surface-raised p-5">
                        <p className="text-xs font-medium text-ink-muted">{t('admin.dashboard.active_admins')}</p>
                        <p className="mt-2 text-2xl font-bold text-ink">{summary.activeAdminsCount}</p>
                    </div>

                    {summary.recentAuditCount !== null ? (
                        <div className="rounded-xl border border-line bg-surface-raised p-5">
                            <p className="text-xs font-medium text-ink-muted">{t('admin.dashboard.recent_audit_7d')}</p>
                            <p className="mt-2 text-2xl font-bold text-ink">{summary.recentAuditCount}</p>
                        </div>
                    ) : null}
                </div>
            </div>
        </AdminLayout>
    );
}
