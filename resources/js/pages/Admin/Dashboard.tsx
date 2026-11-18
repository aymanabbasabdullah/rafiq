import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    Bot,
    CalendarClock,
    ClipboardCheck,
    FlaskConical,
    KeyRound,
    MessageSquareWarning,
    ScrollText,
    ShieldCheck,
    Stethoscope,
    Users,
} from 'lucide-react';
import { Badge } from '@/components/ui/Badge';
import { ButtonLink } from '@/components/ui/Button';
import { Card, Panel } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
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

interface ActivityItem {
    id: number;
    action: string;
    actor: string | null;
    createdAt: string | null;
}

interface DashboardProps {
    recoveryCodes?: string[] | null;
    summary: DashboardSummary;
    recentActivity: ActivityItem[];
    tasks: { id: number; kind: 'review' | 'returned'; title: string; locale: string; url: string }[];
}

function formatDateTime(iso: string | null, locale: string): string {
    if (iso === null) {
        return '';
    }

    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar-u-nu-latn' : 'en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

/**
 * Admin overview (M11A-FR-06): greeting, KPIs, tasks for the role, system status, quick actions and recent activity.
 * Shows administrative data only — the platform stores no visitor answers (ADM-060).
 */
export default function Dashboard({ recoveryCodes, summary, recentActivity, tasks }: DashboardProps) {
    const t = useT();
    const { admin, locale } = usePage().props;
    const user = admin?.user ?? null;
    const links = admin?.links ?? null;
    const firstName = (user?.name ?? '').replace('[SYNTHETIC]', '').trim();
    const today = new Intl.DateTimeFormat(locale.current === 'ar' ? 'ar-u-nu-latn' : 'en-GB', { dateStyle: 'full' }).format(
        new Date(),
    );

    return (
        <AdminLayout>
            <Head title={t('admin.dashboard.title')} />

            <div className="space-y-8">
                <div className="animate-fade-up">
                    <PageHeader
                        eyebrow={today}
                        title={t('admin.dashboard.greeting', { name: firstName })}
                        description={t('admin.dashboard.subtitle')}
                        actions={
                            links && user?.canManageUsers ? (
                                <ButtonLink href={links.users}>
                                    <Users aria-hidden="true" className="size-4" />
                                    {t('admin.nav.users')}
                                </ButtonLink>
                            ) : undefined
                        }
                    />
                </div>

                {recoveryCodes && recoveryCodes.length > 0 ? (
                    <Card as="section" className="animate-fade-up border-warning/40 bg-warning-soft/40 p-5">
                        <div className="flex items-start gap-3">
                            <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-warning-soft text-warning">
                                <KeyRound aria-hidden="true" className="size-5" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <h2 className="font-semibold text-ink">{t('admin.two_factor.recovery_saved_title')}</h2>
                                <p className="mt-1 text-sm text-ink-muted">{t('admin.two_factor.recovery_saved_body')}</p>
                                <ul className="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4" dir="ltr">
                                    {recoveryCodes.map((code) => (
                                        <li
                                            key={code}
                                            className="rounded-lg border border-line bg-surface-raised px-3 py-2 text-center font-mono text-sm text-ink"
                                        >
                                            {code}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </Card>
                ) : null}

                <div className="stagger grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={ClipboardCheck}
                        tone="brand"
                        value={summary.pendingReviewsCount}
                        label={t('admin.dashboard.pending_reviews')}
                        href={user?.canManageContent && links ? links.content : undefined}
                    />
                    <StatCard
                        icon={CalendarClock}
                        tone="warning"
                        value={summary.reviewsDueSoonCount}
                        label={t('admin.dashboard.reviews_due')}
                        href={user?.canManageContent && links ? links.content : undefined}
                    />
                    <StatCard
                        icon={Users}
                        tone="info"
                        value={summary.activeAdminsCount}
                        label={t('admin.dashboard.active_admins')}
                        href={user?.canManageUsers && links ? links.users : undefined}
                    />
                    {summary.recentAuditCount !== null ? (
                        <StatCard
                            icon={Activity}
                            tone="success"
                            value={summary.recentAuditCount}
                            label={t('admin.dashboard.recent_audit')}
                            href={links?.auditLog}
                        />
                    ) : (
                        <StatCard icon={ShieldCheck} tone="neutral" value="2FA" label={t('admin.dashboard.security_on')} />
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    <Panel
                        className="animate-fade-up lg:col-span-2"
                        title={t('admin.dashboard.tasks_title')}
                        description={t('admin.dashboard.tasks_subtitle')}
                    >
                        {tasks.length === 0 ? (
                            <EmptyState
                                icon={ClipboardCheck}
                                title={t('admin.dashboard.tasks_empty_title')}
                                description={t('admin.dashboard.tasks_empty_body')}
                            />
                        ) : (
                            <ul className="stagger divide-y divide-line">
                                {tasks.map((task) => (
                                    <li key={`${task.kind}-${task.id}`}>
                                        <Link href={task.url} className="group flex items-center gap-3 py-3">
                                            <span
                                                className={`inline-flex size-9 shrink-0 items-center justify-center rounded-xl ${
                                                    task.kind === 'review'
                                                        ? 'bg-warning-soft text-warning'
                                                        : 'bg-info-soft text-info'
                                                }`}
                                            >
                                                {task.kind === 'review' ? (
                                                    <ClipboardCheck aria-hidden="true" className="size-4" />
                                                ) : (
                                                    <MessageSquareWarning aria-hidden="true" className="size-4" />
                                                )}
                                            </span>
                                            <span className="min-w-0 flex-1">
                                                <span
                                                    className="block truncate font-medium text-ink group-hover:text-brand"
                                                    dir="auto"
                                                >
                                                    {task.title}
                                                </span>
                                                <span className="text-xs text-ink-subtle">
                                                    {t(`admin.dashboard.task_${task.kind}`)}
                                                </span>
                                            </span>
                                            <Badge>{task.locale.toUpperCase()}</Badge>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>

                    <Panel className="animate-fade-up" title={t('admin.dashboard.status_title')}>
                        <ul className="space-y-4">
                            <li className="flex items-center justify-between gap-3">
                                <span className="flex items-center gap-2.5 text-sm text-ink">
                                    <FlaskConical aria-hidden="true" className="size-4 text-ink-subtle" />
                                    {t('admin.dashboard.demo_mode')}
                                </span>
                                <Badge tone={summary.demoMode ? 'warning' : 'success'} dot>
                                    {summary.demoMode ? t('admin.dashboard.on') : t('admin.dashboard.off')}
                                </Badge>
                            </li>
                            <li className="flex items-center justify-between gap-3">
                                <span className="flex items-center gap-2.5 text-sm text-ink">
                                    <Bot aria-hidden="true" className="size-4 text-ink-subtle" />
                                    {t('admin.dashboard.ai_status')}
                                </span>
                                <Badge tone={summary.aiEnabled ? 'success' : 'neutral'} dot>
                                    {summary.aiEnabled ? t('admin.dashboard.ai_enabled') : t('admin.dashboard.ai_disabled')}
                                </Badge>
                            </li>
                            <li className="flex items-start justify-between gap-3">
                                <span className="flex items-center gap-2.5 text-sm text-ink">
                                    <Stethoscope aria-hidden="true" className="size-4 text-ink-subtle" />
                                    {t('admin.dashboard.active_ruleset')}
                                </span>
                                <Badge tone={summary.activeRuleset !== null ? 'success' : 'neutral'} dot>
                                    {summary.activeRuleset ?? t('admin.dashboard.ruleset_none')}
                                </Badge>
                            </li>
                        </ul>
                        <p className="mt-5 rounded-xl bg-surface-sunken px-3 py-2.5 text-xs text-ink-muted">
                            {t('admin.dashboard.privacy_note')}
                        </p>
                    </Panel>
                </div>

                {user?.canViewAuditLog && recentActivity.length > 0 ? (
                    <Panel
                        className="animate-fade-up"
                        title={t('admin.dashboard.recent_activity')}
                        action={
                            links ? (
                                <ButtonLink href={links.auditLog} variant="ghost" size="sm">
                                    <ScrollText aria-hidden="true" className="size-4" />
                                    {t('admin.dashboard.view_all')}
                                </ButtonLink>
                            ) : undefined
                        }
                    >
                        <ol className="relative space-y-4 border-s border-line ps-5">
                            {recentActivity.map((item) => (
                                <li key={item.id} className="relative">
                                    <span
                                        aria-hidden="true"
                                        className="absolute -start-[25px] top-1.5 size-2.5 rounded-full border-2 border-surface-raised bg-brand"
                                    />
                                    <p className="font-mono text-sm text-ink" dir="ltr">
                                        {item.action}
                                    </p>
                                    <p className="mt-0.5 text-xs text-ink-subtle">
                                        {item.actor ?? t('admin.audit.system_actor')}
                                        <span
                                            aria-hidden="true"
                                            className="mx-1.5 inline-block size-1 rounded-full bg-current align-middle"
                                        />
                                        <bdi>{formatDateTime(item.createdAt, locale.current)}</bdi>
                                    </p>
                                </li>
                            ))}
                        </ol>
                    </Panel>
                ) : null}
            </div>
        </AdminLayout>
    );
}
