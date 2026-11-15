import { Link, usePage } from '@inertiajs/react';
import { ClearEverythingButton } from '@/components/public/ClearEverythingButton';
import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { useT } from '@/lib/i18n';
import type { LegalPageKey } from '@/types';

const LEGAL_PAGES: LegalPageKey[] = ['privacy', 'terms', 'limits', 'sources'];

/**
 * Shared footer: legal pages, emergency guidance and the "not a diagnostic tool" statement (FR-100).
 */
export function SiteFooter() {
    const { links } = usePage().props;
    const t = useT();

    return (
        <footer className="mt-16 border-t border-line bg-surface-raised">
            <div className="mx-auto grid max-w-6xl gap-8 px-4 py-10 md:grid-cols-2">
                <div className="space-y-4">
                    <nav aria-label={t('footer.links_label')}>
                        <ul className="flex flex-wrap gap-x-6 gap-y-2 text-sm font-medium">
                            {LEGAL_PAGES.map((page) => (
                                <li key={page}>
                                    <Link href={links[page]} className="text-ink underline-offset-4 hover:underline">
                                        {t(`legal.${page}.title`)}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </nav>
                    <p className="text-sm text-ink-muted">{t('footer.not_diagnosis')}</p>
                    <ClearEverythingButton />
                </div>
                <div>
                    <h2 className="mb-2 text-sm font-semibold text-ink">{t('footer.emergency_title')}</h2>
                    <EmergencyNotice />
                </div>
            </div>
        </footer>
    );
}
