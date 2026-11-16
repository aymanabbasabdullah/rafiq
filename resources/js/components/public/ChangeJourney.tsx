import { JourneyCard } from '@/components/public/JourneyCard';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

type ChangeJourneyProps = {
    journeys: { key: JourneyKey; url: string | null }[];
};

/**
 * Persistent journey switcher inside every journey (M02-FR-03, M02-FR-04).
 *
 * Switching journeys is a standard navigation that never carries over any answers.
 */
export function ChangeJourney({ journeys }: ChangeJourneyProps) {
    const t = useT();

    if (journeys.length === 0) {
        return null;
    }

    return (
        <section aria-labelledby="change-journey-title" className="border-t border-line pt-12">
            <h2 id="change-journey-title" className="text-2xl text-ink">
                {t('journey.change.title')}
            </h2>
            <p className="mt-2 text-ink-muted">{t('journey.change.no_carryover')}</p>
            <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {journeys.map((journey) => (
                    <li key={journey.key}>
                        <JourneyCard journey={journey.key} url={journey.url} />
                    </li>
                ))}
            </ul>
        </section>
    );
}
