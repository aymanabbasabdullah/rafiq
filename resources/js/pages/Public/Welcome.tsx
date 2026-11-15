import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { BrandMark } from '@/components/public/BrandMark';
import { DemoBanner } from '@/components/public/DemoBanner';
import { LanguageSwitcher } from '@/components/public/LanguageSwitcher';
import { useT } from '@/lib/i18n';

type WelcomeProps = {
    landingUrl: string;
    autoAdvanceSeconds: number;
};

/**
 * Short welcome screen (FR-001). Moves on by itself unless the visitor interacts with the page
 * or prefers reduced motion; the Start button is available immediately.
 */
export default function Welcome({ landingUrl, autoAdvanceSeconds }: WelcomeProps) {
    const t = useT();
    const startButton = useRef<HTMLAnchorElement>(null);

    useEffect(() => {
        startButton.current?.focus();

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const timer = window.setTimeout(() => router.visit(landingUrl, { replace: true }), autoAdvanceSeconds * 1000);
        const cancel = () => window.clearTimeout(timer);
        const interactions: (keyof WindowEventMap)[] = ['pointerdown', 'keydown', 'wheel', 'touchstart'];

        interactions.forEach((event) => window.addEventListener(event, cancel, { once: true }));

        return () => {
            cancel();
            interactions.forEach((event) => window.removeEventListener(event, cancel));
        };
    }, [landingUrl, autoAdvanceSeconds]);

    return (
        <div className="flex min-h-screen flex-col">
            <DemoBanner />
            <div className="flex justify-end p-4">
                <LanguageSwitcher />
            </div>
            <main className="flex flex-1 flex-col items-center justify-center px-6 pb-20 text-center">
                <BrandMark className="size-20 text-brand motion-safe:animate-pulse" />
                <h1 className="mt-6 text-3xl font-semibold text-ink md:text-4xl">{t('welcome.title')}</h1>
                <p className="mt-4 max-w-md text-lg text-ink-muted">{t('welcome.message')}</p>
                <a
                    ref={startButton}
                    href={landingUrl}
                    onClick={(event) => {
                        event.preventDefault();
                        router.visit(landingUrl, { replace: true });
                    }}
                    className="mt-8 inline-flex min-h-11 items-center rounded-md bg-brand px-8 py-3 text-lg font-semibold text-brand-ink"
                >
                    {t('welcome.start')}
                </a>
                <p className="mt-4 text-sm text-ink-muted motion-reduce:hidden" aria-live="polite">
                    {t('welcome.auto_note')}
                </p>
            </main>
        </div>
    );
}
