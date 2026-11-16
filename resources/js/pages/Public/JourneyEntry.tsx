import { Link, usePage } from '@inertiajs/react';
import { ArrowLeft, BookOpen, CalendarCheck, Clock, Construction, HeartPulse, Lock, Sprout, type LucideIcon } from 'lucide-react';
import { ChangeJourney } from '@/components/public/ChangeJourney';
import { PageIntro } from '@/components/public/PageIntro';
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
export default function JourneyEntry({ journey, showsRedFlagReminder, showsLocalOnlyNotice, concernUrl, otherJourneys }: JourneyEntryProps) {
    const { links } = usePage().props;
    const t = useT();

    return (
        <PublicLayout>
            <div className="space-y-12">
                <div>
                    <Link href={links.journeys} className="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-brand hover:text-brand-strong">
                        <ArrowLeft aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                        <span>{t('journey.entry.all_journeys')}</span>
                    </Link>

                    <div className="mt-4 grid gap-10 lg:grid-cols-12">
                        <div className="lg:col-span-7">
                            <PageIntro id="journey-title" title={t(`journey.${journey}.title`)} lead={t(`journey.${journey}.description`)} icon={ICONS[journey]}>
                                <p className="mt-5 inline-flex items-center gap-2 rounded-full bg-surface-raised px-3.5 py-1.5 text-sm font-medium text-ink-muted shadow-card">
                                    <Clock aria-hidden="true" className="size-4 text-brand" />
                                    {t(`journey.${journey}.time`)}
                                </p>
                            </PageIntro>
                        </div>
                        <dl className="space-y-6 rounded-[var(--radius-card)] bg-surface-raised p-7 shadow-card lg:col-span-5">
                            <div>
                                <dt className="text-sm font-semibold text-brand">{t('journey.you_get')}</dt>
                                <dd className="mt-1.5 text-ink">{t(`journey.${journey}.gets`)}</dd>
                            </div>
                            <div className="border-t border-line pt-6">
                                <dt className="text-sm font-semibold text-brand">{t('journey.we_ask')}</dt>
                                <dd className="mt-1.5 text-ink">{t(`journey.${journey}.asks`)}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {showsLocalOnlyNotice ? (
                    <section aria-labelledby="local-only-notice-title" className="flex items-start gap-4 rounded-[var(--radius-card)] bg-brand-soft p-6">
                        <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand">
                            <Lock aria-hidden="true" className="size-5" />
                        </span>
                        <div>
                            <h2 id="local-only-notice-title" className="font-display text-lg font-semibold text-ink">
                                {t('journey.doctor_prep.local_notice_title')}
                            </h2>
                            <p className="mt-1.5 text-ink-muted">{t('journey.doctor_prep.local_notice_body')}</p>
                        </div>
                    </section>
                ) : null}

                <section aria-labelledby="what-happens-title">
                    <h2 id="what-happens-title" className="text-2xl text-ink">
                        {t('journey.entry.what_happens_title')}
                    </h2>
                    <ol className="mt-8 grid gap-8 md:grid-cols-3">
                        {STEPS.map((step, index) => (
                            <li key={step} className="border-t-2 border-brand pt-5">
                                <span className="font-display text-sm font-semibold text-brand">
                                    <bdi>{index + 1}</bdi>
                                </span>
                                <p className="mt-2 text-ink">{t(`journey.${journey}.${step}`)}</p>
                            </li>
                        ))}
                    </ol>
                </section>

                <section aria-labelledby="journey-coming-soon-title" className="flex items-start gap-4 rounded-[var(--radius-card)] border border-dashed border-line-strong p-6">
                    <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-notice text-notice-ink">
                        <Construction aria-hidden="true" className="size-5" />
                    </span>
                    <div>
                        <span className="inline-block rounded-full bg-notice px-3 py-0.5 text-xs font-semibold text-notice-ink">{t('journey.coming_soon')}</span>
                        <h2 id="journey-coming-soon-title" className="mt-2 font-display text-lg font-semibold text-ink">
                            {t('journey.entry.coming_soon_title')}
                        </h2>
                        <p className="mt-1.5 text-ink-muted">{t('journey.entry.coming_soon_body')}</p>
                    </div>
                </section>

                {showsRedFlagReminder ? <RedFlagReminder concernUrl={concernUrl} /> : null}

                <ChangeJourney journeys={otherJourneys} />
            </div>
        </PublicLayout>
    );
}
