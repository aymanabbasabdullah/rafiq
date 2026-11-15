import { Link, usePage } from '@inertiajs/react';
import { Bot, ClipboardList, Compass, LibraryBig, Lock, type LucideIcon } from 'lucide-react';
import { CheckList } from '@/components/public/CheckList';
import { EmergencyStrip } from '@/components/public/EmergencyStrip';
import { JourneyCard } from '@/components/public/JourneyCard';
import { Section } from '@/components/public/Section';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';
import type { JourneyKey } from '@/types';

type LandingProps = {
    journeys: { key: JourneyKey; url: string | null }[];
};

const OFFERS: { key: string; icon: LucideIcon }[] = [
    { key: 'learn', icon: LibraryBig },
    { key: 'guidance', icon: Compass },
    { key: 'prepare', icon: ClipboardList },
    { key: 'ai', icon: Bot },
];

const STEPS = ['step1', 'step2', 'step3'];
const PRIVACY_POINTS = ['point1', 'point2', 'point3', 'point4', 'point5'];
const FAQ = ['1', '2', '3', '4'];

/**
 * Landing page (FR-002..FR-004): what Rafiq is, what it offers, how it works, privacy, the AI's role,
 * limits (never collapsed — BR-01-1), the four journeys and short FAQs.
 */
export default function Landing({ journeys }: LandingProps) {
    const { links } = usePage().props;
    const t = useT();

    return (
        <PublicLayout beforeMain={<EmergencyStrip />}>
            <section aria-labelledby="hero-title" className="py-8 md:py-14">
                <p className="text-sm font-semibold text-brand">{t('landing.hero.eyebrow')}</p>
                <h1 id="hero-title" className="mt-3 max-w-3xl text-3xl font-semibold text-ink md:text-5xl">
                    {t('landing.hero.title')}
                </h1>
                <p className="mt-4 max-w-2xl text-lg text-ink-muted">{t('landing.hero.subtitle')}</p>
                <div className="mt-8 flex flex-wrap gap-3">
                    <Link
                        href={links.journeys}
                        className="inline-flex min-h-11 items-center rounded-md bg-brand px-5 py-3 font-semibold text-brand-ink"
                    >
                        {t('landing.hero.cta_primary')}
                    </Link>
                    <a
                        href="#limits"
                        className="inline-flex min-h-11 items-center rounded-md border border-line bg-surface-raised px-5 py-3 font-semibold text-ink"
                    >
                        {t('landing.hero.cta_secondary')}
                    </a>
                </div>
            </section>

            <Section id="what" title={t('landing.what.title')}>
                <p className="max-w-3xl text-lg text-ink">{t('landing.what.body')}</p>
            </Section>

            <Section id="offers" title={t('landing.offers.title')}>
                <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {OFFERS.map(({ key, icon: Icon }) => (
                        <li key={key} className="rounded-xl border border-line bg-surface-raised p-5">
                            <Icon aria-hidden="true" className="size-6 text-brand" />
                            <h3 className="mt-3 font-semibold text-ink">{t(`landing.offers.${key}.title`)}</h3>
                            <p className="mt-2 text-sm text-ink-muted">{t(`landing.offers.${key}.body`)}</p>
                        </li>
                    ))}
                </ul>
            </Section>

            <Section id="how" title={t('landing.how.title')}>
                <ol className="grid gap-4 md:grid-cols-3">
                    {STEPS.map((step, index) => (
                        <li key={step} className="flex gap-4 rounded-xl border border-line bg-surface-raised p-5">
                            <span className="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-brand font-semibold text-brand-ink">
                                <bdi>{index + 1}</bdi>
                            </span>
                            <div>
                                <h3 className="font-semibold text-ink">{t(`landing.how.${step}.title`)}</h3>
                                <p className="mt-1 text-sm text-ink-muted">{t(`landing.how.${step}.body`)}</p>
                            </div>
                        </li>
                    ))}
                </ol>
            </Section>

            <Section id="privacy" title={t('landing.privacy.title')}>
                <div className="rounded-xl border border-line bg-surface-raised p-6">
                    <ul className="space-y-3">
                        {PRIVACY_POINTS.map((point) => (
                            <li key={point} className="flex items-start gap-3 text-ink">
                                <Lock aria-hidden="true" className="mt-1 size-4 shrink-0 text-brand" />
                                <span>{t(`landing.privacy.${point}`)}</span>
                            </li>
                        ))}
                    </ul>
                    <p className="mt-5 border-t border-line pt-4 text-sm text-ink-muted">{t('landing.privacy.honesty')}</p>
                </div>
            </Section>

            <Section id="ai" title={t('landing.ai.title')} intro={t('landing.ai.body')}>
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-line bg-surface-raised p-5">
                        <h3 className="mb-3 font-semibold text-ink">{t('landing.ai.does_title')}</h3>
                        <CheckList variant="yes" items={[t('landing.ai.does1'), t('landing.ai.does2'), t('landing.ai.does3')]} />
                    </div>
                    <div className="rounded-xl border border-line bg-surface-raised p-5">
                        <h3 className="mb-3 font-semibold text-ink">{t('landing.ai.doesnt_title')}</h3>
                        <CheckList variant="no" items={[t('landing.ai.doesnt1'), t('landing.ai.doesnt2'), t('landing.ai.doesnt3')]} />
                    </div>
                </div>
            </Section>

            <Section id="limits" title={t('landing.limits.title')}>
                <div className="grid gap-4 md:grid-cols-2">
                    <div className="rounded-xl border border-line bg-surface-raised p-5">
                        <h3 className="mb-3 font-semibold text-ink">{t('landing.limits.can_title')}</h3>
                        <CheckList
                            variant="yes"
                            items={['can1', 'can2', 'can3', 'can4'].map((key) => t(`landing.limits.${key}`))}
                        />
                    </div>
                    <div className="rounded-xl border border-danger/30 bg-surface-raised p-5">
                        <h3 className="mb-3 font-semibold text-ink">{t('landing.limits.cannot_title')}</h3>
                        <CheckList
                            variant="no"
                            items={['cannot1', 'cannot2', 'cannot3', 'cannot4', 'cannot5', 'cannot6'].map((key) =>
                                t(`landing.limits.${key}`),
                            )}
                        />
                    </div>
                </div>
            </Section>

            <Section id="journeys" title={t('landing.journeys.title')} intro={t('landing.journeys.subtitle')}>
                <ul className="grid gap-4 sm:grid-cols-2">
                    {journeys.map((journey) => (
                        <li key={journey.key}>
                            <JourneyCard journey={journey.key} url={journey.url} />
                        </li>
                    ))}
                </ul>
            </Section>

            <Section id="faq" title={t('landing.faq.title')}>
                <div className="divide-y divide-line rounded-xl border border-line bg-surface-raised">
                    {FAQ.map((number) => (
                        <details key={number} className="group p-5">
                            <summary className="cursor-pointer list-none font-semibold text-ink marker:hidden">
                                {t(`landing.faq.q${number}`)}
                            </summary>
                            <p className="mt-3 text-ink-muted">{t(`landing.faq.a${number}`)}</p>
                        </details>
                    ))}
                </div>
            </Section>
        </PublicLayout >
    );
}
