import { Link, router, usePage } from '@inertiajs/react';
import {
    BookOpenText,
    Bot,
    ChartColumn,
    CircleCheck,
    ExternalLink,
    FolderTree,
    Languages,
    LayoutDashboard,
    LogOut,
    Menu,
    Quote,
    ScrollText,
    Layers,
    Stethoscope,
    Users,
    X,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import { BrandMark } from '@/components/public/BrandMark';
import { DemoBanner } from '@/components/public/DemoBanner';
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
                  ...(user.canManageContent
                      ? [
                            { key: 'content', label: t('admin.nav.content'), icon: BookOpenText, href: links.content },
                            { key: 'sources', label: t('admin.nav.sources'), icon: Quote, href: links.sources },
                            { key: 'categories', label: t('admin.nav.categories'), icon: FolderTree, href: links.categories },
                        ]
                      : []),
                  ...(user.canViewClinical
                      ? [
                            { key: 'clinical', label: t('admin.nav.clinical'), icon: Stethoscope, href: links.clinicalRules },
                            { key: 'releases', label: t('admin.nav.releases'), icon: Layers, href: links.clinicalReleases },
                        ]
                      : []),
                  ...(user.canManageAi ? [{ key: 'ai', label: t('admin.nav.ai'), icon: Bot, href: links.ai }] : []),
                  ...(user.canManageUsers ? [{ key: 'users', label: t('admin.nav.users'), icon: Users, href: links.users }] : []),
                  ...(user.canViewAuditLog
                      ? [{ key: 'audit', label: t('admin.nav.audit'), icon: ScrollText, href: links.auditLog }]
                      : []),
              ]
            : [];

    const upcoming: NavItem[] = [{ key: 'analytics', label: t('admin.nav.analytics'), icon: ChartColumn, href: null }];

    // The most specific matching item wins, so /admin/content/sources highlights "Sources" only.
    const currentPath = page.url.split('?')[0] ?? '';
    const activeKey = items
        .map((item) => ({ key: item.key, path: item.href !== null ? new URL(item.href, window.location.origin).pathname : '' }))
        .filter(
            ({ path }) =>
                path !== '' &&
                (path === '/admin' ? currentPath === '/admin' : currentPath === path || currentPath.startsWith(`${path}/`)),
        )
        .sort((a, b) => b.path.length - a.path.length)[0]?.key;

    const isActive = (key: string) => key === activeKey;

    const sidebar = (
        <div className="flex h-full flex-col">
            <div className="flex items-center justify-between gap-2 px-5 py-5">
                <Link href={links?.dashboard ?? '/admin'} className="flex items-center gap-2.5 text-mint">
                    <BrandMark className="size-8" />
                    <span className="font-display text-lg font-semibold text-white">{t('admin.title')}</span>
                </Link>
                <button
                    type="button"
                    onClick={() => setDrawerOpen(false)}
                    className="inline-flex size-10 cursor-pointer items-center justify-center rounded-full text-white/70 hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label={t('admin.nav.close_menu')}
                >
                    <X aria-hidden="true" className="size-5" />
                </button>
            </div>

            <nav aria-label={t('admin.title')} className="flex-1 overflow-y-auto px-3">
                <ul className="space-y-1">
                    {items.map((item) => {
                        const active = isActive(item.key);

                        return (
                            <li key={item.key}>
                                <Link
                                    href={item.href ?? '#'}
                                    aria-current={active ? 'page' : undefined}
                                    className={`flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors duration-200 ${
                                        active
                                            ? 'bg-white/12 text-white shadow-[inset_3px_0_0_var(--color-mint)] rtl:shadow-[inset_-3px_0_0_var(--color-mint)]'
                                            : 'text-white/70 hover:bg-white/6 hover:text-white'
                                    }`}
                                >
                                    <item.icon aria-hidden="true" className="size-[18px] shrink-0" />
                                    <span>{item.label}</span>
                                </Link>
                            </li>
                        );
                    })}
                </ul>

                <p className="mt-8 mb-2 px-3 text-xs font-semibold text-white/45">{t('admin.nav.upcoming')}</p>
                <ul className="space-y-1">
                    {upcoming.map((item) => (
                        <li key={item.key}>
                            <span
                                aria-disabled="true"
                                className="flex min-h-10 items-center gap-3 rounded-xl px-3 text-sm text-white/40"
                            >
                                <item.icon aria-hidden="true" className="size-[18px] shrink-0" />
                                <span className="flex-1">{item.label}</span>
                                <span className="rounded-full bg-white/8 px-2 py-0.5 text-xs font-medium text-white/55">
                                    {t('admin.nav.soon')}
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>
            </nav>

            {user ? (
                <div className="m-3 rounded-2xl bg-white/6 p-4">
                    <div className="flex items-center gap-3">
                        <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-mint font-semibold text-brand-deep">
                            {user.name.replace('[SYNTHETIC]', '').trim().charAt(0)}
                        </span>
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-white">{user.name}</p>
                            <p className="truncate text-xs text-white/55" dir="ltr">
                                {user.email}
                            </p>
                        </div>
                    </div>
                    <div className="mt-3 flex flex-wrap gap-1.5">
                        {user.roles.map((role) => (
                            <span key={role} className="rounded-full bg-mint/15 px-2.5 py-0.5 text-xs font-medium text-mint">
                                {t(`admin.role.${role}`)}
                            </span>
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
                        <aside className="sticky top-0 hidden h-screen w-72 shrink-0 bg-brand-deep lg:block">{sidebar}</aside>

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
                                    className="absolute inset-0 animate-fade-in cursor-pointer bg-brand-deep/50 backdrop-blur-sm"
                                    onClick={() => setDrawerOpen(false)}
                                />
                                <aside className="absolute inset-y-0 start-0 w-[min(20rem,85vw)] animate-fade-in bg-brand-deep shadow-lift">
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
                                        className="inline-flex size-10 cursor-pointer items-center justify-center rounded-full border border-line text-ink hover:bg-surface-sunken lg:hidden"
                                        aria-label={t('admin.nav.open_menu')}
                                        aria-expanded={drawerOpen}
                                    >
                                        <Menu aria-hidden="true" className="size-5" />
                                    </button>
                                ) : null}
                                <span className="flex items-center gap-2 text-brand lg:hidden">
                                    <BrandMark className="size-7" />
                                    <span className="font-display font-semibold text-ink">{t('admin.title')}</span>
                                </span>
                            </div>

                            <div className="flex items-center gap-1.5">
                                <a
                                    href={publicLinks.home}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="hidden min-h-10 cursor-pointer items-center gap-1.5 rounded-full px-3 text-sm font-medium text-ink-muted hover:bg-surface-sunken hover:text-ink sm:inline-flex"
                                >
                                    <ExternalLink aria-hidden="true" className="size-4" />
                                    <span>{t('admin.nav.view_site')}</span>
                                </a>
                                {links ? (
                                    <button
                                        type="button"
                                        onClick={() => router.patch(links.locale, { locale: locale.alternate.code })}
                                        className="inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-full px-3 text-sm font-medium text-ink-muted hover:bg-surface-sunken hover:text-ink"
                                    >
                                        <Languages aria-hidden="true" className="size-4" />
                                        <span>{locale.alternate.name}</span>
                                    </button>
                                ) : null}
                                {user && links ? (
                                    <button
                                        type="button"
                                        onClick={() => router.post(links.logout)}
                                        className="inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-full px-3 text-sm font-medium text-ink-muted hover:bg-danger-soft hover:text-danger"
                                    >
                                        <LogOut aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                                        <span className="hidden sm:inline">{t('admin.nav.logout')}</span>
                                    </button>
                                ) : null}
                            </div>
                        </div>
                    </header>

                    <main id="admin-main" className="mx-auto w-full max-w-7xl flex-1 px-4 py-6 md:px-8 md:py-10">
                        {admin?.flash.success ? (
                            <p
                                key={page.url + admin.flash.success}
                                role="status"
                                className="mb-6 flex animate-fade-up items-center gap-2 rounded-xl border border-success/25 bg-success-soft px-4 py-3 text-sm font-medium text-success"
                            >
                                <CircleCheck aria-hidden="true" className="size-4 shrink-0" />
                                {admin.flash.success}
                            </p>
                        ) : null}
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}
