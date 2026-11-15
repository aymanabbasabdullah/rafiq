import { router, usePage } from '@inertiajs/react';
import { Languages, LockKeyhole, ScrollText, ShieldCheck, type LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { BrandMark } from '@/components/public/BrandMark';
import { DemoBanner } from '@/components/public/DemoBanner';
import { useT } from '@/lib/i18n';

const TRUST_POINTS: { key: string; icon: LucideIcon }[] = [
    { key: 'two_factor', icon: LockKeyhole },
    { key: 'audit', icon: ScrollText },
    { key: 'no_visitor_data', icon: ShieldCheck },
];

/**
 * Split-screen layout for admin sign-in, 2FA and password screens.
 * Desktop: brand panel with gentle floating shapes + form. Mobile: form card only.
 */
export function AdminAuthLayout({
    title,
    subtitle,
    icon: Icon,
    children,
}: {
    title: string;
    subtitle?: string;
    icon?: LucideIcon;
    children: ReactNode;
}) {
    const t = useT();
    const { locale, admin } = usePage().props;

    return (
        <div className="flex min-h-screen flex-col bg-surface">
            <DemoBanner />
            <div className="grid flex-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
                <aside className="relative hidden overflow-hidden bg-brand-strong p-12 text-brand-ink lg:flex lg:flex-col lg:justify-between">
                    <div aria-hidden="true" className="pointer-events-none absolute inset-0">
                        <div className="absolute -top-24 -start-20 size-80 animate-float rounded-full bg-brand opacity-60 blur-3xl" />
                        <div className="absolute end-[-6rem] bottom-[-4rem] size-96 animate-float rounded-full bg-accent opacity-25 blur-3xl [animation-delay:-4s]" />
                    </div>

                    <div className="relative flex items-center gap-3">
                        <BrandMark className="size-10 text-brand-soft" />
                        <span className="text-xl font-semibold">{t('admin.title')}</span>
                    </div>

                    <div className="relative max-w-md">
                        <h2 className="text-3xl leading-tight font-semibold">{t('admin.auth.panel_title')}</h2>
                        <p className="mt-3 text-brand-soft/90">{t('admin.auth.panel_body')}</p>
                        <ul className="stagger mt-10 space-y-4">
                            {TRUST_POINTS.map(({ key, icon: PointIcon }) => (
                                <li key={key} className="flex items-center gap-3 rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
                                    <span className="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-white/15">
                                        <PointIcon aria-hidden="true" className="size-5" />
                                    </span>
                                    <span className="text-sm">{t(`admin.auth.trust.${key}`)}</span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <p className="relative text-xs text-brand-soft/70">{t('footer.not_diagnosis')}</p>
                </aside>

                <main className="flex items-center justify-center px-4 py-10 sm:px-8">
                    <div className="w-full max-w-md animate-fade-up">
                        <div className="mb-8 flex items-center justify-between gap-3">
                            <span className="flex items-center gap-2 text-brand lg:invisible">
                                <BrandMark className="size-8" />
                                <span className="font-semibold text-ink">{t('admin.title')}</span>
                            </span>
                            {admin?.links ? (
                                <button
                                    type="button"
                                    onClick={() => router.patch(admin.links.locale, { locale: locale.alternate.code })}
                                    className="inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-ink-muted hover:bg-surface-sunken hover:text-ink"
                                >
                                    <Languages aria-hidden="true" className="size-4" />
                                    {locale.alternate.name}
                                </button>
                            ) : null}
                        </div>

                        <div className="rounded-3xl border border-line bg-surface-raised p-6 shadow-card sm:p-8">
                            {Icon !== undefined ? (
                                <span className="mb-5 inline-flex size-12 items-center justify-center rounded-2xl bg-brand-soft text-brand">
                                    <Icon aria-hidden="true" className="size-6" />
                                </span>
                            ) : null}
                            <h1 className="text-2xl font-semibold text-ink">{title}</h1>
                            {subtitle !== undefined ? <p className="mt-2 text-sm text-ink-muted">{subtitle}</p> : null}
                            <div className="mt-7">{children}</div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    );
}
