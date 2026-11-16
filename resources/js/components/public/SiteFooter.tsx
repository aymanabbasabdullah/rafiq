import { Link, usePage } from '@inertiajs/react';
import { BrandLockup } from '@/components/public/BrandMark';
import { ClearEverythingButton } from '@/components/public/ClearEverythingButton';
import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { useT } from '@/lib/i18n';
import type { LegalPageKey } from '@/types';

const LEGAL_PAGES: LegalPageKey[] = ['privacy', 'terms', 'limits', 'sources'];

/**
 * Shared footer: identity, platform and policy links, emergency guidance, "Clear everything" and the
 * "not a diagnostic tool" statement (FR-100).
 */
export function SiteFooter() {
    const { links } = usePage().props;
    const t = useT();

    const platformLinks = [
        { key: 'journeys', label: t('footer.journeys'), href: links.journeys },
        ...(links.learn !== null ? [{ key: 'learn', label: t('nav.learn'), href: links.learn }] : []),
        ...(links.glossary !== null ? [{ key: 'glossary', label: t('learn.glossary_link'), href: links.glossary }] : []),
    ];

    const linkClass = 'text-sm text-ink-muted transition-colors hover:text-brand-strong';

    return (
        <footer className="mt-auto border-t border-line bg-surface-raised">
            <div className="mx-auto grid max-w-6xl gap-12 px-4 py-14 sm:px-6 md:grid-cols-12 lg:px-8">
                <div className="space-y-5 md:col-span-4">
                    <BrandLockup name={t('app.name')} />
                    <p className="max-w-xs text-sm text-ink-muted">{t('app.tagline')}</p>
                    <ClearEverythingButton />
                </div>

                <nav aria-label={t('footer.links_label')} className="grid grid-cols-2 gap-8 md:col-span-4">
                    <div>
                        <h2 className="text-sm font-semibold text-ink">{t('footer.platform')}</h2>
                        <ul className="mt-4 space-y-3">
                            {platformLinks.map((link) => (
                                <li key={link.key}>
                                    <Link href={link.href} className={linkClass}>
                                        {link.label}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                    <div>
                        <h2 className="text-sm font-semibold text-ink">{t('footer.policies')}</h2>
                        <ul className="mt-4 space-y-3">
                            {LEGAL_PAGES.map((page) => (
                                <li key={page}>
                                    <Link href={links[page]} className={linkClass}>
                                        {t(`legal.${page}.title`)}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </nav>

                <div className="md:col-span-4">
                    <h2 className="mb-4 text-sm font-semibold text-ink">{t('footer.emergency_title')}</h2>
                    <EmergencyNotice />
                </div>
            </div>
            <div className="border-t border-line">
                <p className="mx-auto max-w-6xl px-4 py-6 text-sm text-ink-subtle sm:px-6 lg:px-8">{t('footer.not_diagnosis')}</p>
            </div>
        </footer>
    );
}
