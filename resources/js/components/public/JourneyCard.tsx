import { Link } from '@inertiajs/react';
import { ArrowRight, BookOpen, CalendarCheck, HeartPulse, Sprout, type LucideIcon } from 'lucide-react';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

const ICONS: Record<JourneyKey, LucideIcon> = {
    learn: BookOpen,
    concern: HeartPulse,
    conception: Sprout,
    doctor_prep: CalendarCheck,
};

/**
 * A journey choice (FR-003). Journeys whose entry page is not built yet show "coming soon" (Module-02 adds the links).
 */
export function JourneyCard({ journey, url }: { journey: JourneyKey; url: string | null }) {
    const t = useT();
    const Icon = ICONS[journey];
    const titleId = `journey-${journey}-title`;

    const content = (
        <>
            <div className="flex items-center justify-between gap-3">
                <span className="inline-flex size-11 items-center justify-center rounded-full bg-brand/10 text-brand">
                    <Icon aria-hidden="true" className="size-5" />
                </span>
                <span className="rounded-full bg-surface px-3 py-1 text-xs font-medium text-ink-muted">{t(`journey.${journey}.time`)}</span>
            </div>
            <h3 id={titleId} className="mt-4 text-lg font-semibold text-ink">
                {t(`journey.${journey}.title`)}
            </h3>
            <p className="mt-2 text-sm text-ink-muted">{t(`journey.${journey}.description`)}</p>
            <p className="mt-3 text-sm text-ink">
                <span className="font-medium">{t('journey.you_get')}</span> {t(`journey.${journey}.gets`)}
            </p>
            <span className="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold text-brand">
                {url !== null ? t('journey.start') : t('journey.coming_soon')}
                {url !== null ? <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" /> : null}
            </span>
        </>
    );

    const cardClasses = 'flex h-full flex-col rounded-xl border border-line bg-surface-raised p-5';

    if (url === null) {
        return (
            <article aria-labelledby={titleId} aria-disabled="true" className={`${cardClasses} opacity-75`}>
                {content}
            </article>
        );
    }

    return (
        <Link href={url} aria-labelledby={titleId} className={`${cardClasses} transition hover:border-brand hover:shadow-sm`}>
            {content}
        </Link>
    );
}
