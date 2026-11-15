import { Link, router, usePage } from '@inertiajs/react';
import {
    BookOpenText,
    Bot,
    ChartColumn,
    ExternalLink,
    Languages,
    LayoutDashboard,
    LogOut,
    Menu,
    ScrollText,
    Stethoscope,
    Users,
    X,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { BrandMark } from '@/components/public/BrandMark';
import { DemoBanner } from '@/components/public/DemoBanner';
import { Badge } from '@/components/ui/Badge';
import { useT } from '@/lib/i18n';

type NavItem = {
    key: string;
    label: string;
    icon: LucideIcon;
    href: string | null;
};

/**
 * Responsive bilingual layout for Rafiq Admin (Module-11 §7).
 *
 * - ≥ lg: fixed sidebar beside the content.
 * - < lg: sticky top bar with a menu button that opens the sidebar as a drawer.
 * Logical properties only, so the sidebar sits on the right in Arabic and on the left in English.
 */
export function AdminLayout({ children }: { children: ReactNode }) {
    const { admin, locale, links: publicLinks } = usePage().props;
    const page = usePage();
    const t = useT();
    const [drawerOpen, setDrawerOpen] = useState(false);

    const user = admin?.user ?? null;
    const links = admin?.links ?? null;

    useEffect(() => {
        const close = () => setDrawerOpen(false);
        const removeListener = router.on('navigate', close);
        const onKeyDown = (event: KeyboardEvent) => event.key === 'Escape' && close();
        window.addEventListener('keydown', onKeyDown);

        return () => {
            removeListener();
            window.removeEventListener('keydown', onKeyDown);
        };
    }, []);

    const items: NavItem[] =
        user && links
            ? [
                  { key: 'dashboard', label: t('admin.nav.dashboard'), icon: LayoutDashboard, href: links.dashboard },
                  ...(user.canManageUsers ? [{ key: 'users', label: t('admin.nav.users'), icon: Users, href: links.users }] : []),
                  ...(user.canViewAuditLog
                      ? [{ key: 'audit', label: t('admin.nav.audit'), icon: ScrollText, href: links.auditLog }]
                      : []),
              ]
            : [];

    const upcoming: NavItem[] = [
        { key: 'content', label: t('admin.nav.content'), icon: BookOpenText, href: null },
        { key: 'clinical', label: t('admin.nav.clinical'), icon: Stethoscope, href: null },
        { key: 'ai', label: t('admin.nav.ai'), icon: Bot, href: null },
        { key: 'analytics', label: t('admin.nav.analytics'), icon: ChartColumn, href: null },
    ];

    const isActive = (href: string | null) => {
        if (href === null) {
            return false;
        }

        const path = new URL(href, window.location.origin).pathname;
        const current = page.url.split('?')[0] ?? '';

        return path === '/admin' ? current === '/admin' : current.startsWith(path);
    };

    const sidebar = (
        <div className="flex h-full flex-col">
            <div className="flex items-center justify-between gap-2 px-5 py-5">
                <Link href={links?.dashboard ?? '/admin'} className="flex items-center gap-2.5 text-brand">
                    <BrandMark className="size-8" />
                    <span className="text-lg font-semibold text-ink">{t('admin.title')}</span>
                </Link>
                <button
                    type="button"
                    onClick={() => setDrawerOpen(false)}
                    className="inline-flex size-10 cursor-pointer items-center justify-center rounded-lg text-ink-muted hover:bg-surface-sunken lg:hidden"
                    aria-label={t('admin.nav.close_menu')}
                >
                    <X aria-hidden="true" className="size-5" />
                </button>
            </div>

            <nav aria-label={t('admin.title')} className="flex-1 overflow-y-auto px-3">
                <ul className="space-y-1">
                    {items.map((item) => {
                        const active = isActive(item.href);

                        return (
                            <li key={item.key}>
                                <Link
                                    href={item.href ?? '#'}
                                    aria-current={active ? 'page' : undefined}
                                    className={`flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors duration-200 ${
                                        active
                                            ? 'bg-brand text-brand-ink shadow-sm'
                                            : 'text-ink-muted hover:bg-surface-sunken hover:text-ink'
                                    }`}
                                >
                                    <item.icon aria-hidden="true" className="size-[18px] shrink-0" />
                                    <span>{item.label}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>

                <p className="mt-7 mb-2 px-3 text-xs font-semibold tracking-wide text-ink-subtle">{t('admin.nav.upcoming')}</p>
                <ul className="space-y-1">
                    {upcoming.map((item) => (
                        <li key={item.key}>
                            <span
                                aria-disabled="true"
                                className="flex min-h-10 items-center gap-3 rounded-xl px-3 text-sm text-ink-subtle"
                            >
                                <item.icon aria-hidden="true" className="size-[18px] shrink-0" />
                                <span className="flex-1">{item.label}</span>
                                <Badge>{t('admin.nav.soon')}</Badge>
                            </span>
                        </li>
                    ))}
                </ul>
            </nav>

            {user ? (
                <div className="m-3 rounded-2xl border border-line bg-surface p-4">
                    <div className="flex items-center gap-3">
                        <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-soft font-semibold text-brand">
                            {user.name.replace('[SYNTHETIC]', '').trim().charAt(0)}
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-ink">{user.name}</p>
                            <p className="truncate text-xs text-ink-subtle" dir="ltr">
                                {user.email}
                            </p>
                        </div>
                    </div>
                    <div className="mt-3 flex flex-wrap gap-1.5">
                        {user.roles.map((role) => (
                            <Badge key={role} tone="brand">
                                {t(`admin.role.${role}`)}
                            </Badge>
                        ))}
                    </div>
                </div>
            ) : null}
        </div>
    );

    return (
        <div className="min-h-screen bg-surface text-ink">
            <a
                href="#admin-main"
                className="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-surface-raised focus:px-4 focus:py-2"
            >
                {t('nav.skip_to_content')}
            </a>

            <DemoBanner />

            <div className="flex">
                {user ? (
                    <>
                        <aside className="sticky top-0 hidden h-screen w-72 shrink-0 border-e border-line bg-surface-raised lg:block">
                            {sidebar}
                        </aside>

                        {drawerOpen ? (
                            <div
                                className="fixed inset-0 z-40 lg:hidden"
                                role="dialog"
                                aria-modal="true"
                                aria-label={t('admin.title')}
                            >
                                <button
                                    type="button"
                                    aria-label={t('admin.nav.close_menu')}
                                    className="absolute inset-0 animate-fade-in cursor-pointer bg-ink/40"
                                    onClick={() => setDrawerOpen(false)}
                                />
                                <aside className="absolute inset-y-0 start-0 w-[min(20rem,85vw)] animate-fade-in bg-surface-raised shadow-lift">
                                    {sidebar}
                                </aside>
                            </div>
                        ) : null}
                    </>
                ) : null}

                <div className="flex min-w-0 flex-1 flex-col">
                    <header className="sticky top-0 z-30 border-b border-line bg-surface-raised/90 backdrop-blur">
                        <div className="flex min-h-16 items-center justify-between gap-3 px-4 md:px-8">
                            <div className="flex items-center gap-2">
                                {user ? (
                                    <button
                                        type="button"
                                        onClick={() => setDrawerOpen(true)}
                                        className="inline-flex size-10 cursor-pointer items-center justify-center rounded-lg border border-line text-ink hover:bg-surface-sunken lg:hidden"
                                        aria-label={t('admin.nav.open_menu')}
                                        aria-expanded={drawerOpen}
                                    >
                                        <Menu aria-hidden="true" className="size-5" />
                                    </button>
                                ) : null}
                                <span className="flex items-center gap-2 text-brand lg:hidden">
                                    <BrandMark className="size-7" />
                                    <span className="font-semibold text-ink">{t('admin.title')}</span>
                                </span>
                            </div>

                            <div className="flex items-center gap-1.5">
                                <a
                                    href={publicLinks.home}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="hidden min-h-10 cursor-pointer items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-ink-muted hover:bg-surface-sunken hover:text-ink sm:inline-flex"
                                >
                                    <ExternalLink aria-hidden="true" className="size-4" />
                                    <span>{t('admin.nav.view_site')}</span>
                                </a>
                                {links ? (
                                    <button
                                        type="button"
                                        onClick={() => router.patch(links.locale, { locale: locale.alternate.code })}
                                        className="inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-ink-muted hover:bg-surface-sunken hover:text-ink"
                                    >
                                        <Languages aria-hidden="true" className="size-4" />
                                        <span>{locale.alternate.name}</span>
                                    </button>
                                ) : null}
                                {user && links ? (
                                    <button
                                        type="button"
                                        onClick={() => router.post(links.logout)}
                                        className="inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-ink-muted hover:bg-danger-soft hover:text-danger"
                                    >
                                        <LogOut aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                                        <span className="hidden sm:inline">{t('admin.nav.logout')}</span>
                                    </button>
                                ) : null}
                            </div>
                        </div>
                    </header>

                    <main id="admin-main" className="mx-auto w-full max-w-7xl flex-1 px-4 py-6 md:px-8 md:py-10">
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}
