import type { ReactNode } from 'react';
import { DemoBanner } from '@/components/public/DemoBanner';
import { SiteFooter } from '@/components/public/SiteFooter';
import { SiteHeader } from '@/components/public/SiteHeader';
import { useT } from '@/lib/i18n';

/**
 * Public layout: skip link, demo banner, header (language switch + Quick exit), page content and footer.
 *
 * `beforeMain` renders full-width content between the header and the main column (e.g. the emergency strip).
 */
export function PublicLayout({ children, beforeMain }: { children: ReactNode; beforeMain?: ReactNode }) {
    const t = useT();

    return (
        <div className="flex min-h-screen flex-col">
            <a
                href="#main-content"
                className="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-surface-raised focus:px-4 focus:py-2"
            >
                {t('nav.skip_to_content')}
            </a>

            <DemoBanner />
            <SiteHeader />
            {beforeMain}

            <main id="main-content" className="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
                {children}
            </main>

            <SiteFooter />
        </div>
    );
}
