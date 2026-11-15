import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import { EmergencyStrip } from '@/components/public/EmergencyStrip';
import { JourneyCard } from '@/components/public/JourneyCard';
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
            <section aria-labelledby="journeys-page-title" className="py-6">
                <p className="text-sm font-semibold text-brand">{t('journeys.eyebrow')}</p>
                <h1 id="journeys-page-title" className="mt-2 max-w-3xl text-3xl font-semibold text-ink md:text-4xl">
                    {t('journeys.title')}
                </h1>
                <p className="mt-3 max-w-2xl text-lg text-ink-muted">{t('journeys.subtitle')}</p>

                <div className="mt-6 rounded-xl border border-danger/30 bg-danger/5 p-5">
                    <div className="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <div className="flex items-start gap-3">
                            <AlertTriangle aria-hidden="true" className="mt-0.5 size-5 shrink-0 text-danger" />
                            <div>
                                <h2 className="font-semibold text-ink">{t('journeys.urgent_box_title')}</h2>
                                <p className="mt-1 text-sm text-ink-muted">{t('journeys.urgent_box_body')}</p>
                            </div>
                        </div>
                        {concernUrl !== null ? (
                            <Link
                                href={concernUrl}
                                className="inline-flex min-h-11 shrink-0 items-center gap-2 self-start rounded-md bg-danger px-4 py-2.5 text-sm font-semibold text-white md:self-center"
                            >
                                <span>{t('journey.concern.safety_cta')}</span>
                                <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                            </Link>
                        ) : null}
                    </div>
                </div>

                <ul className="mt-8 grid gap-4 md:grid-cols-2">
                    {journeys.map((journey) => (
                        <li key={journey.key}>
                            <JourneyCard journey={journey.key} url={journey.url} />
                        </li>
                    ))}
                </ul>
            </section>
        </PublicLayout>
    );
}
