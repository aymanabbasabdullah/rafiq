import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, BookOpen, CalendarCheck, HeartPulse, Sprout, type LucideIcon } from 'lucide-react';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

const ICONS: Record<JourneyKey, LucideIcon> = {
    learn: BookOpen,
    concern: HeartPulse,
    conception: Sprout,
    doctor_prep: CalendarCheck,
};

/**
 * A journey choice (FR-003, M02-FR-01). Shows title, description, what you get, estimated time,
 * what we ask/don't ask, and the urgent warning callout on J2.
 */
export function JourneyCard({ journey, url }: { journey: JourneyKey; url: string | null }) {
    const t = useT();
    const Icon = ICONS[journey];
    const titleId = `journey-${journey}-title`;
    const isConcern = journey === 'concern';

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
            <dl className="mt-4 space-y-2 border-t border-line pt-3 text-sm">
                <div>
                    <dt className="inline font-medium text-ink">{t('journey.you_get')}</dt>{' '}
                    <dd className="inline text-ink-muted">{t(`journey.${journey}.gets`)}</dd>
                </div>
                <div>
                    <dt className="inline font-medium text-ink">{t('journey.we_ask')}</dt>{' '}
                    <dd className="inline text-ink-muted">{t(`journey.${journey}.asks`)}</dd>
                </div>
            </dl>
            {isConcern ? (
                <p className="mt-4 flex items-start gap-2 rounded-lg border border-danger/30 bg-danger/5 px-3 py-2 text-xs font-medium text-danger">
                    <AlertTriangle aria-hidden="true" className="mt-0.5 size-4 shrink-0" />
                    <span>{t('journey.concern.urgent_note')}</span>
                </p>
            ) : null}
            <span className="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold text-brand">
                {url !== null ? t('journey.start') : t('journey.coming_soon')}
                {url !== null ? <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" /> : null}
            </span>
        </>
    );

    const borderClasses = isConcern ? 'border-danger/40' : 'border-line';
    const cardClasses = `flex h-full flex-col rounded-xl border ${borderClasses} bg-surface-raised p-5`;

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
