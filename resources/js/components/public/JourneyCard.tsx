import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, BookOpen, CalendarCheck, HeartPulse, Sprout, type LucideIcon } from 'lucide-react';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

/**
 * Each journey has its own icon and tint so the four paths are told apart at a glance.
 * Tints carry identity only; urgency is always stated in words (concern journey note).
 */
const JOURNEYS: Record<JourneyKey, { icon: LucideIcon; tint: string; ink: string; number: string }> = {
    learn: { icon: BookOpen, tint: 'bg-tint-learn', ink: 'text-brand', number: '01' },
    concern: { icon: HeartPulse, tint: 'bg-tint-concern', ink: 'text-accent', number: '02' },
    conception: { icon: Sprout, tint: 'bg-tint-conception', ink: 'text-sage', number: '03' },
    doctor_prep: { icon: CalendarCheck, tint: 'bg-tint-doctor', ink: 'text-slate', number: '04' },
};

/**
 * A journey choice (FR-003, M02-FR-01). `compact` (landing) shows the essentials; `detailed`
 * (journeys page) adds what you get and what we ask. The concern journey always shows its urgent note.
 */
export function JourneyCard({ journey, url, variant = 'compact' }: { journey: JourneyKey; url: string | null; variant?: 'compact' | 'detailed' }) {
    const t = useT();
    const { icon: Icon, tint, ink, number } = JOURNEYS[journey];
    const titleId = `journey-${journey}-title`;
    const detailed = variant === 'detailed';

    const content = (
        <>
            <div className="flex items-start justify-between gap-3">
                <span className={`inline-flex size-12 items-center justify-center rounded-2xl bg-surface-raised ${ink} shadow-card`}>
                    <Icon aria-hidden="true" className="size-6" />
                </span>
                <span aria-hidden="true" className="font-display text-sm font-semibold text-ink/30 tabular-nums">
                    {number}
                </span>
            </div>

            <h3 id={titleId} className={`mt-6 font-display font-semibold text-ink ${detailed ? 'text-2xl' : 'text-xl'}`}>
                {t(`journey.${journey}.title`)}
            </h3>
            <p className="mt-2 text-ink-muted">{t(`journey.${journey}.description`)}</p>

            {detailed ? (
                <dl className="mt-5 space-y-3 border-t border-ink/10 pt-5 text-sm">
                    <div>
                        <dt className="font-semibold text-ink">{t('journey.you_get')}</dt>
                        <dd className="mt-0.5 text-ink-muted">{t(`journey.${journey}.gets`)}</dd>
                    </div>
                    <div>
                        <dt className="font-semibold text-ink">{t('journey.we_ask')}</dt>
                        <dd className="mt-0.5 text-ink-muted">{t(`journey.${journey}.asks`)}</dd>
                    </div>
                </dl>
            ) : null}

            {journey === 'concern' ? (
                <p className="mt-5 flex items-start gap-2 rounded-xl bg-surface-raised/80 px-3 py-2.5 text-sm font-medium text-danger">
                    <AlertTriangle aria-hidden="true" className="mt-0.5 size-4 shrink-0" />
                    <span>{t('journey.concern.urgent_note')}</span>
                </p>
            ) : null}

            <div className="mt-auto flex items-center justify-between gap-3 pt-6">
                <span className="rounded-full bg-surface-raised/70 px-3 py-1 text-xs font-medium text-ink-muted">{t(`journey.${journey}.time`)}</span>
                {url !== null ? (
                    <span className="inline-flex items-center gap-2 text-sm font-semibold text-ink">
                        {t('journey.start')}
                        <span className="inline-flex size-9 items-center justify-center rounded-full bg-ink text-white transition-transform duration-300 group-hover:translate-x-1 rtl:group-hover:-translate-x-1">
                            <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                        </span>
                    </span>
                ) : (
                    <span className="text-sm font-semibold text-ink-subtle">{t('journey.coming_soon')}</span>
                )}
            </div>
        </>
    );

    const classes = `group flex h-full flex-col rounded-[var(--radius-card)] ${tint} p-6 sm:p-7`;

    if (url === null) {
        return (
            <article aria-labelledby={titleId} aria-disabled="true" className={`${classes} opacity-70`}>
                {content}
            </article>
        );
    }

    return (
        <Link href={url} aria-labelledby={titleId} className={`${classes} hover-lift`}>
            {content}
        </Link>
    );
}
