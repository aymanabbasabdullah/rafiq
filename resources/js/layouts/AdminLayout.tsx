import { Link, router, usePage } from '@inertiajs/react';
import { LayoutDashboard, LogOut, Shield, Users } from 'lucide-react';
import type { ReactNode } from 'react';
import { BrandMark } from '@/components/public/BrandMark';
import { DemoBanner } from '@/components/public/DemoBanner';
import { useT } from '@/lib/i18n';

/**
 * Bilingual RTL/LTR layout for Rafiq Admin (Module-11 §7, M11A-FR-07).
 */
export function AdminLayout({ children }: { children: ReactNode }) {
    const { admin, locale } = usePage().props;
    const t = useT();
    const user = admin?.user ?? null;
    const links = admin?.links;
    const nextLocale = locale.current === 'ar' ? 'en' : 'ar';

    const switchLocale = () => {
        if (!links) {
            return;
        }

        router.patch(links.locale, { locale: nextLocale });
    };

    const logout = () => {
        if (!links) {
            return;
        }

        router.post(links.logout);
    };

    return (
        <div className="flex min-h-screen flex-col bg-surface text-ink">
            <a
                href="#admin-main"
                className="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-surface-raised focus:px-4 focus:py-2"
            >
                {t('nav.skip_to_content')}
            </a>

            <DemoBanner />

            <header className="border-b border-line bg-surface-raised">
                <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div className="flex items-center gap-3">
                        <span className="flex items-center gap-2 text-brand">
                            <BrandMark />
                            <span className="text-lg font-semibold text-ink">{t('admin.title')}</span>
                        </span>
                        {user ? (
                            <div className="flex flex-wrap items-center gap-1.5">
                                {user.roles.map((role) => (
                                    <span
                                        key={role}
                                        className="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-medium text-brand"
                                    >
                                        {t(`admin.role.${role}`)}
                                    </span>
                                ))}
                            </div>
                        ) : null}
                    </div>

                    <div className="flex items-center gap-2">
                        {user ? <span className="text-sm text-ink-muted">{user.name}</span> : null}
                        {links ? (
                            <button
                                type="button"
                                onClick={switchLocale}
                                className="inline-flex min-h-9 items-center rounded-md border border-line bg-surface px-3 py-1.5 text-xs font-medium text-ink hover:border-brand"
                            >
                                {locale.alternate.name}
                            </button>
                        ) : null}
                        {user && links ? (
                            <button
                                type="button"
                                onClick={logout}
                                className="inline-flex min-h-9 items-center gap-1.5 rounded-md border border-line bg-surface px-3 py-1.5 text-xs font-medium text-ink hover:border-danger hover:text-danger"
                            >
                                <LogOut aria-hidden="true" className="size-3.5" />
                                <span>{t('admin.nav.logout')}</span>
                            </button>
                        ) : null}
                    </div>
                </div>
            </header>

            <div className="mx-auto flex w-full max-w-7xl flex-1 flex-col md:flex-row">
                {user && links ? (
                    <aside className="w-full border-b border-line bg-surface-raised p-4 md:w-60 md:border-b-0 md:border-e">
                        <nav aria-label={t('admin.title')}>
                            <ul className="space-y-1">
                                <li>
                                    <Link
                                        href={links.dashboard}
                                        className="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-ink hover:bg-surface"
                                    >
                                        <LayoutDashboard aria-hidden="true" className="size-4 text-brand" />
                                        <span>{t('admin.nav.dashboard')}</span>
                                    </Link>
                                </li>
                                {user.canManageUsers ? (
                                    <li>
                                        <Link
                                            href={links.users}
                                            className="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-ink hover:bg-surface"
                                        >
                                            <Users aria-hidden="true" className="size-4 text-brand" />
                                            <span>{t('admin.nav.users')}</span>
                                        </Link>
                                    </li>
                                ) : null}
                                {user.canViewAuditLog ? (
                                    <li>
                                        <Link
                                            href={links.auditLog}
                                            className="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-medium text-ink hover:bg-surface"
                                        >
                                            <Shield aria-hidden="true" className="size-4 text-brand" />
                                            <span>{t('admin.nav.audit')}</span>
                                        </Link>
                                    </li>
                                ) : null}
                            </ul>
                        </nav>
                    </aside>
                ) : null}

                <main id="admin-main" className="flex-1 p-4 md:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
