import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { DemoBanner } from '@/components/public/DemoBanner';
import { LanguageSwitcher } from '@/components/public/LanguageSwitcher';
import { useT } from '@/lib/i18n';

/**
 * Structural public layout (Module-00). Header, footer and Quick Exit are completed in Module-01.
 */
export function PublicLayout({ children }: { children: ReactNode }) {
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

            <header className="border-b border-line bg-surface-raised">
                <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4 py-3">
                    <Link href="/" className="text-lg font-semibold text-brand">
                        {t('app.name')}
                    </Link>
                    <LanguageSwitcher />
                </div>
            </header>

            <main id="main-content" className="mx-auto w-full max-w-5xl flex-1 px-4 py-8">
                {children}
            </main>

            <footer className="border-t border-line bg-surface-raised">
                <p className="mx-auto max-w-5xl px-4 py-4 text-sm text-ink-muted">{t('footer.not_diagnosis')}</p>
            </footer>
        </div>
    );
}
