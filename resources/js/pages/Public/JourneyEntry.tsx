import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, BookOpen, CalendarCheck, HeartPulse, Lock, Sprout, type LucideIcon } from 'lucide-react';
import { ChangeJourney } from '@/components/public/ChangeJourney';
import { RedFlagReminder } from '@/components/public/RedFlagReminder';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

type JourneyEntryProps = {
    journey: JourneyKey;
    showsRedFlagReminder: boolean;
    showsLocalOnlyNotice: boolean;
    concernUrl: string | null;
    otherJourneys: { key: JourneyKey; url: string | null }[];
};

const ICONS: Record<JourneyKey, LucideIcon> = {
    learn: BookOpen,
    concern: HeartPulse,
    conception: Sprout,
    doctor_prep: CalendarCheck,
};

const STEPS = ['step1', 'step2', 'step3'] as const;

/**
 * Journey entry page (M02-FR-02..M02-FR-06, M03-FR-08).
 *
 * Explains what happens in the chosen journey, displays the contextual J4 local-only
 * privacy notice or the J1/J4 permanent warning-signs reminder, and provides the
 * persistent "Change your journey" switcher until M04–M10 replace the body with
 * each journey's interactive tools.
 */
export default function JourneyEntry({
    journey,
    showsRedFlagReminder,
    showsLocalOnlyNotice,
    concernUrl,
    otherJourneys,
}: JourneyEntryProps) {
    const { links } = usePage().props;
    const t = useT();
    const Icon = ICONS[journey];

    return (
        <PublicLayout>
            <div className="max-w-4xl">
                <Link
                    href={links.journeys}
                    className="inline-flex items-center gap-1.5 text-sm font-medium text-brand hover:underline"
                >
                    <ArrowLeft aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                    <span>{t('journey.entry.all_journeys')}</span>
                </Link>

                <header className="mt-4 rounded-xl border border-line bg-surface-raised p-6">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <span className="inline-flex size-12 items-center justify-center rounded-full bg-brand/10 text-brand">
                            <Icon aria-hidden="true" className="size-6" />
                        </span>
                        <span className="rounded-full bg-surface px-3 py-1 text-xs font-medium text-ink-muted">
                            {t(`journey.${journey}.time`)}
                        </span>
                    </div>
                    <h1 className="mt-4 text-3xl font-semibold text-ink">{t(`journey.${journey}.title`)}</h1>
                    <p className="mt-2 text-lg text-ink-muted">{t(`journey.${journey}.description`)}</p>

                    <dl className="mt-5 grid gap-3 border-t border-line pt-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="font-semibold text-ink">{t('journey.you_get')}</dt>
                            <dd className="mt-1 text-ink-muted">{t(`journey.${journey}.gets`)}</dd>
                        </div>
                        <div>
                            <dt className="font-semibold text-ink">{t('journey.we_ask')}</dt>
                            <dd className="mt-1 text-ink-muted">{t(`journey.${journey}.asks`)}</dd>
                        </div>
                    </dl>
                </header>

                {showsLocalOnlyNotice ? (
                    <section
                        aria-labelledby="local-only-notice-title"
                        className="mt-6 flex items-start gap-3 rounded-xl border border-brand/30 bg-brand/5 p-5"
                    >
                        <Lock aria-hidden="true" className="mt-0.5 size-5 shrink-0 text-brand" />
                        <div>
                            <h2 id="local-only-notice-title" className="font-semibold text-ink">
                                {t('journey.doctor_prep.local_notice_title')}
                            </h2>
                            <p className="mt-1 text-sm text-ink-muted">{t('journey.doctor_prep.local_notice_body')}</p>
                        </div>
                    </section>
                ) : null}

                <section aria-labelledby="what-happens-title" className="mt-8">
                    <h2 id="what-happens-title" className="text-xl font-semibold text-ink">
                        {t('journey.entry.what_happens_title')}
                    </h2>
                    <ol className="mt-4 grid gap-4 md:grid-cols-3">
                        {STEPS.map((step, index) => (
                            <li key={step} className="flex gap-3 rounded-xl border border-line bg-surface-raised p-4">
                                <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-brand text-sm font-semibold text-brand-ink">
                                    <bdi>{index + 1}</bdi>
                                </span>
                                <p className="text-sm text-ink">{t(`journey.${journey}.${step}`)}</p>
                            </li>
                        ))}
                    </ol>
                </section>

                <section
                    aria-labelledby="journey-coming-soon-title"
                    className="mt-8 rounded-xl border border-line bg-surface-raised p-6"
                >
                    <span className="inline-block rounded-full bg-notice px-3 py-1 text-xs font-semibold text-notice-ink">
                        {t('journey.coming_soon')}
                    </span>
                    <h2 id="journey-coming-soon-title" className="mt-3 text-lg font-semibold text-ink">
                        {t('journey.entry.coming_soon_title')}
                    </h2>
                    <p className="mt-2 text-sm text-ink-muted">{t('journey.entry.coming_soon_body')}</p>
                </section>

                {showsRedFlagReminder ? <RedFlagReminder concernUrl={concernUrl} /> : null}

                <ChangeJourney journeys={otherJourneys} />
            </div>
        </PublicLayout>
    );
}
