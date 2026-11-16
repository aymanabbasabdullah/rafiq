import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import { EmergencyStrip } from '@/components/public/EmergencyStrip';
import { JourneyCard } from '@/components/public/JourneyCard';
import { PageIntro } from '@/components/public/PageIntro';
import { buttonClasses } from '@/components/ui/Button';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

type JourneysProps = {
    journeys: { key: JourneyKey; url: string | null }[];
    concernUrl: string | null;
};

/**
 * "Choose your journey" page (M02-FR-01, FR-010): presents the four journeys without forcing
 * a questionnaire, highlights emergency care, and offers a direct link to J2 Safety Screening.
 */
export default function Journeys({ journeys, concernUrl }: JourneysProps) {
    const t = useT();

    return (
        <PublicLayout beforeMain={<EmergencyStrip />}>
            <section aria-labelledby="journeys-page-title">
                <PageIntro id="journeys-page-title" eyebrow={t('journeys.eyebrow')} title={t('journeys.title')} lead={t('journeys.subtitle')} />

                <aside
                    aria-labelledby="urgent-box-title"
                    className="mt-10 flex flex-col gap-5 rounded-[var(--radius-card)] border border-danger/20 bg-surface-raised p-6 sm:p-7 md:flex-row md:items-center md:justify-between"
                >
                    <div className="flex items-start gap-4">
                        <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-danger-soft text-danger">
                            <AlertTriangle aria-hidden="true" className="size-5" />
                        </span>
                        <div>
                            <h2 id="urgent-box-title" className="font-display text-lg font-semibold text-ink">
                                {t('journeys.urgent_box_title')}
                            </h2>
                            <p className="mt-1.5 max-w-2xl text-ink-muted">{t('journeys.urgent_box_body')}</p>
                        </div>
                    </div>
                    {concernUrl !== null ? (
                        <Link href={concernUrl} className={buttonClasses('destructive', 'md', 'shrink-0 self-start md:self-center')}>
                            <span>{t('journey.concern.safety_cta')}</span>
                            <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                        </Link>
                    ) : null}
                </aside>

                <ul className="mt-8 grid gap-4 md:grid-cols-2">
                    {journeys.map((journey) => (
                        <li key={journey.key}>
                            <JourneyCard journey={journey.key} url={journey.url} variant="detailed" />
                        </li>
                    ))}
                </ul>
            </section>
        </PublicLayout>
    );
}
