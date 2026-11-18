import { router } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { BrandMark } from '@/components/public/BrandMark';
import { DemoBanner } from '@/components/public/DemoBanner';
import { LanguageSwitcher } from '@/components/public/LanguageSwitcher';
import { buttonClasses } from '@/components/ui/Button';
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
        <div className="flex min-h-screen flex-col bg-brand-deep text-white">
            <DemoBanner />
            <div className="flex justify-end p-4">
                <LanguageSwitcher tone="dark" />
            </div>
            <main className="relative flex flex-1 flex-col items-center justify-center overflow-hidden px-6 pb-24 text-center">
                <svg aria-hidden="true" viewBox="0 0 600 600" className="pointer-events-none absolute inset-0 m-auto size-[44rem] max-w-none text-mint">
                    {[120, 180, 240, 300].map((radius, index) => (
                        <circle key={radius} cx="300" cy="300" r={radius} fill="none" stroke="currentColor" strokeOpacity={0.16 - index * 0.035} />
                    ))}
                </svg>

                <div className="relative animate-fade-up">
                    <span className="mx-auto inline-flex size-24 items-center justify-center rounded-full bg-white/5 ring-1 ring-white/10">
                        <BrandMark className="size-14 text-mint" />
                    </span>
                    <h1 className="mt-8 text-4xl text-white md:text-5xl">{t('welcome.title')}</h1>
                    <p className="mx-auto mt-5 max-w-md text-lg text-white/75">{t('welcome.message')}</p>
                    <a
                        ref={startButton}
                        href={landingUrl}
                        onClick={(event) => {
                            event.preventDefault();
                            router.visit(landingUrl, { replace: true });
                        }}
                        className={buttonClasses('inverse', 'lg', 'mt-10 focus-visible:outline-mint')}
                    >
                        {t('welcome.start')}
                        <ArrowRight aria-hidden="true" className="size-5 rtl:-scale-x-100" />
                    </a>
                    <p className="mt-5 text-sm text-white/55 motion-reduce:hidden" aria-live="polite">
                        {t('welcome.auto_note')}
                    </p>
                </div>
            </main>
        </div>
    );
}
