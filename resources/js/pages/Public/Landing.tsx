import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BookA,
    ChartNoAxesColumn,
    Check,
    Compass,
    Eraser,
    FileText,
    ListChecks,
    MessageSquareLock,
    Minus,
    Plus,
    ScanText,
    ServerOff,
    ShieldCheck,
    UserRoundX,
    WholeWord,
    X,
    type LucideIcon,
} from 'lucide-react';
import type { CSSProperties } from 'react';
import { CategoryIcon } from '@/components/learn/CategoryIcon';
import { EmergencyStrip } from '@/components/public/EmergencyStrip';
import { HeroVisual } from '@/components/public/HeroVisual';
import { JourneyCard } from '@/components/public/JourneyCard';
import { Container, Eyebrow, SectionHeading } from '@/components/public/Section';
import { buttonClasses } from '@/components/ui/Button';
import { EmptyState } from '@/components/ui/EmptyState';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';
import { useReveal } from '@/lib/useReveal';
import type { JourneyKey } from '@/types';

type KnowledgePreview = {
    categories: { key: string; icon: string; name: string; description: string | null; url: string; count: number }[];
    featured: { title: string; summary: string | null; url: string; category: string | null } | null;
};

type LandingProps = {
    journeys: { key: JourneyKey; url: string | null }[];
    knowledge: KnowledgePreview | null;
};

const STEPS = ['step1', 'step2', 'step3'];

const AI_CAPABILITIES: { key: string; icon: LucideIcon }[] = [
    { key: 'cap_understand', icon: ScanText },
    { key: 'cap_terms', icon: WholeWord },
    { key: 'cap_summarize', icon: FileText },
    { key: 'cap_navigate', icon: Compass },
    { key: 'cap_questions', icon: ListChecks },
];

const PRIVACY_POINTS: { key: string; icon: LucideIcon }[] = [
    { key: 'point1', icon: UserRoundX },
    { key: 'point2', icon: ServerOff },
    { key: 'point3', icon: ChartNoAxesColumn },
    { key: 'point4', icon: MessageSquareLock },
    { key: 'point5', icon: Eraser },
];

const CAN = ['can1', 'can2', 'can3', 'can4'];
const CANNOT = ['cannot1', 'cannot2', 'cannot3', 'cannot4', 'cannot5', 'cannot6'];
const FAQ = ['1', '2', '3', '4'];

/**
 * Landing page (FR-002..FR-004). Order follows the visitor's questions: what is this and is it for me
 * (hero), where do I start (journeys), how does it work, what does the AI do, what can I read, is it
 * private, what are the limits (never collapsed — BR-01-1), and the remaining questions.
 */
export default function Landing({ journeys, knowledge }: LandingProps) {
    const { links } = usePage().props;
    const t = useT();
    const page = useReveal<HTMLDivElement>();

    return (
        <PublicLayout beforeMain={<EmergencyStrip />} bleed>
            <div ref={page}>
                {/* Hero */}
                <section aria-labelledby="hero-title" className="overflow-hidden">
                    <Container className="grid items-center gap-12 pt-10 pb-16 md:pt-16 lg:grid-cols-12 lg:gap-10 lg:pb-24">
                        <div className="animate-fade-up lg:col-span-7">
                            <Eyebrow>{t('landing.hero.eyebrow')}</Eyebrow>
                            <h1 id="hero-title" className="mt-5 text-4xl leading-[1.12] text-ink sm:text-5xl lg:text-[3.5rem]">
                                {t('landing.hero.title')}
                            </h1>
                            <p className="mt-6 max-w-xl text-lg text-ink-muted md:text-xl">{t('landing.hero.subtitle')}</p>
                            <div className="mt-9 flex flex-col gap-3 sm:flex-row">
                                <Link href={links.journeys} className={buttonClasses('primary', 'lg')}>
                                    {t('landing.hero.cta_primary')}
                                    <ArrowRight aria-hidden="true" className="size-5 rtl:-scale-x-100" />
                                </Link>
                                {links.learn !== null ? (
                                    <Link href={links.learn} className={buttonClasses('secondary', 'lg')}>
                                        {t('landing.hero.cta_secondary')}
                                    </Link>
                                ) : null}
                            </div>
                            <ul className="mt-10 flex flex-wrap gap-x-6 gap-y-3 text-sm text-ink-muted">
                                {['trust1', 'trust2', 'trust3'].map((key) => (
                                    <li key={key} className="flex items-center gap-2">
                                        <ShieldCheck aria-hidden="true" className="size-4 text-brand" />
                                        {t(`landing.hero.${key}`)}
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div className="mx-auto w-full max-w-md lg:col-span-5 lg:max-w-none">
                            <HeroVisual />
                        </div>
                    </Container>
                </section>

                {/* Journeys */}
                <section id="journeys" aria-labelledby="journeys-title" className="scroll-mt-24 pb-16 md:pb-24">
                    <Container>
                        <SectionHeading
                            id="journeys-title"
                            eyebrow={t('landing.journeys.eyebrow')}
                            title={t('landing.journeys.title')}
                            intro={t('landing.journeys.subtitle')}
                            action={
                                <Link href={links.journeys} className="inline-flex min-h-11 items-center gap-2 font-semibold text-brand hover:text-brand-strong">
                                    {t('landing.journeys.compare')}
                                    <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                                </Link>
                            }
                        />
                        <ul className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {journeys.map((journey, index) => (
                                <li key={journey.key} className="reveal" style={{ '--reveal-index': index } as CSSProperties}>
                                    <JourneyCard journey={journey.key} url={journey.url} />
                                </li>
                            ))}
                        </ul>
                    </Container>
                </section>

                {/* How it works */}
                <section id="how" aria-labelledby="how-title" className="scroll-mt-24 border-y border-line bg-surface-raised py-16 md:py-24">
                    <Container>
                        <SectionHeading id="how-title" eyebrow={t('landing.how.eyebrow')} title={t('landing.how.title')} />
                        <ol className="relative mt-12 grid gap-10 md:grid-cols-3 md:gap-8">
                            <span aria-hidden="true" className="absolute start-6 top-6 bottom-6 w-px bg-line-strong md:start-6 md:end-[calc((100%-4rem)/3-1.5rem)] md:top-6 md:bottom-auto md:h-px md:w-auto" />
                            {STEPS.map((step, index) => (
                                <li key={step} className="reveal relative flex gap-5 md:flex-col" style={{ '--reveal-index': index } as CSSProperties}>
                                    <span className="relative z-10 inline-flex size-12 shrink-0 items-center justify-center rounded-full bg-brand font-display text-base font-semibold text-brand-ink ring-8 ring-surface-raised">
                                        <bdi>{index + 1}</bdi>
                                    </span>
                                    <div>
                                        <h3 className="font-display text-xl font-semibold text-ink">{t(`landing.how.${step}.title`)}</h3>
                                        <p className="mt-2 max-w-sm text-ink-muted">{t(`landing.how.${step}.body`)}</p>
                                    </div>
                                </li>
                            ))}
                        </ol>
                    </Container>
                </section>

                {/* Rafiq AI */}
                <section id="ai" aria-labelledby="ai-title" className="scroll-mt-24 py-16 md:py-24">
                    <Container>
                        <div className="overflow-hidden rounded-[var(--radius-panel)] bg-brand-deep text-white">
                            <div className="grid gap-12 p-7 sm:p-10 lg:grid-cols-12 lg:p-14">
                                <div className="lg:col-span-6">
                                    <div className="flex flex-wrap items-center gap-3">
                                        <Eyebrow tone="mint">{t('landing.ai.eyebrow')}</Eyebrow>
                                        <span className="rounded-full border border-mint/40 px-2.5 py-0.5 text-xs font-semibold text-mint">
                                            {t('landing.ai.status')}
                                        </span>
                                    </div>
                                    <h2 id="ai-title" className="mt-4 text-3xl text-white md:text-4xl">
                                        {t('landing.ai.title')}
                                    </h2>
                                    <p className="mt-4 text-lg text-white/75">{t('landing.ai.body')}</p>

                                    <h3 className="mt-10 text-sm font-semibold text-mint">{t('landing.ai.helps_title')}</h3>
                                    <ul className="mt-4 grid gap-3 sm:grid-cols-2">
                                        {AI_CAPABILITIES.map(({ key, icon: Icon }) => (
                                            <li key={key} className="flex items-start gap-3 text-white/90">
                                                <Icon aria-hidden="true" className="mt-0.5 size-5 shrink-0 text-mint" />
                                                <span>{t(`landing.ai.${key}`)}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>

                                <figure className="flex flex-col justify-center lg:col-span-6">
                                    <div aria-hidden="true" className="relative mx-auto w-full max-w-md">
                                        <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
                                            <p className="text-xs font-semibold text-mint">{t('landing.ai.preview_term')}</p>
                                            <div className="mt-4 space-y-2.5">
                                                <div className="h-3 w-2/5 rounded-full bg-white/35" />
                                                <div className="h-2.5 w-11/12 rounded-full bg-white/12" />
                                                <div className="h-2.5 w-4/5 rounded-full bg-white/12" />
                                            </div>
                                        </div>
                                        <div className="flex justify-center py-3">
                                            <span className="inline-flex size-9 items-center justify-center rounded-full bg-mint text-brand-deep">
                                                <ArrowRight className="size-4 rotate-90" />
                                            </span>
                                        </div>
                                        <div className="rounded-2xl bg-surface-raised p-5 text-ink shadow-lift">
                                            <p className="text-xs font-semibold text-brand">{t('landing.ai.preview_plain')}</p>
                                            <div className="mt-4 space-y-2.5">
                                                <div className="h-2.5 w-full rounded-full bg-brand-soft" />
                                                <div className="h-2.5 w-10/12 rounded-full bg-brand-soft" />
                                                <div className="h-2.5 w-7/12 rounded-full bg-brand-soft" />
                                            </div>
                                        </div>
                                    </div>
                                    <figcaption className="mx-auto mt-5 max-w-md text-center text-sm text-white/60">
                                        <span className="block font-semibold text-white/80">{t('landing.ai.preview_label')}</span>
                                        <span className="block">{t('landing.ai.status_note')}</span>
                                    </figcaption>
                                </figure>
                            </div>

                            <div className="grid gap-6 border-t border-white/10 p-7 sm:p-10 lg:grid-cols-12 lg:px-14">
                                <p className="flex items-start gap-3 text-white/85 lg:col-span-5">
                                    <ShieldCheck aria-hidden="true" className="mt-0.5 size-5 shrink-0 text-mint" />
                                    <span>{t('landing.ai.guardrail')}</span>
                                </p>
                                <div className="lg:col-span-7">
                                    <h3 className="text-sm font-semibold text-mint">{t('landing.ai.doesnt_title')}</h3>
                                    <ul className="mt-3 grid gap-3 sm:grid-cols-3">
                                        {['doesnt1', 'doesnt2', 'doesnt3'].map((key) => (
                                            <li key={key} className="flex items-start gap-2 text-sm text-white/75">
                                                <X aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-mint" />
                                                <span>{t(`landing.ai.${key}`)}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </Container>
                </section>

                {/* Knowledge Center preview */}
                {knowledge !== null && links.learn !== null ? (
                    <section id="learn" aria-labelledby="learn-title" className="scroll-mt-24 pb-16 md:pb-24">
                        <Container>
                            <SectionHeading
                                id="learn-title"
                                eyebrow={t('landing.learn.eyebrow')}
                                title={t('landing.learn.title')}
                                intro={t('landing.learn.body')}
                                action={
                                    <Link href={links.learn} className={buttonClasses('secondary')}>
                                        {t('landing.learn.cta')}
                                    </Link>
                                }
                            />

                            {knowledge.categories.length === 0 ? (
                                <div className="mt-10">
                                    <EmptyState icon={BookA} title={t('learn.empty_title')} description={t('landing.learn.empty')} />
                                </div>
                            ) : (
                                <div className="mt-10 grid gap-6 lg:grid-cols-12">
                                    {knowledge.featured !== null ? (
                                        <Link
                                            href={knowledge.featured.url}
                                            className="group reveal relative flex min-h-80 flex-col overflow-hidden rounded-[var(--radius-panel)] bg-brand-soft p-7 sm:p-10 lg:col-span-7"
                                        >
                                            <svg aria-hidden="true" viewBox="0 0 200 200" className="pointer-events-none absolute -end-16 -bottom-20 size-80 text-brand/10">
                                                {[40, 60, 80, 100].map((radius) => (
                                                    <circle key={radius} cx="100" cy="100" r={radius} fill="none" stroke="currentColor" strokeWidth="1.5" />
                                                ))}
                                            </svg>
                                            <p className="relative text-sm font-semibold text-brand">
                                                {t('landing.learn.featured')}
                                                {knowledge.featured.category !== null ? (
                                                    <>
                                                        <span aria-hidden="true" className="mx-2 inline-block size-1 rounded-full bg-ink-subtle align-middle" />
                                                        <span className="text-ink-subtle">{knowledge.featured.category}</span>
                                                    </>
                                                ) : null}
                                            </p>
                                            <h3 className="relative mt-4 max-w-lg font-display text-2xl font-semibold text-ink md:text-3xl">
                                                {knowledge.featured.title}
                                            </h3>
                                            {knowledge.featured.summary !== null ? (
                                                <p className="relative mt-4 max-w-lg text-ink-muted">{knowledge.featured.summary}</p>
                                            ) : null}
                                            <span className="relative mt-auto inline-flex items-center gap-2 pt-8 font-semibold text-brand-strong">
                                                {t('learn.read_article')}
                                                <ArrowRight aria-hidden="true" className="size-4 transition-transform group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" />
                                            </span>
                                        </Link>
                                    ) : null}

                                    <div className={knowledge.featured !== null ? 'lg:col-span-5' : 'lg:col-span-12'}>
                                        <ul className="divide-y divide-line border-y border-line">
                                            {knowledge.categories.map((category) => (
                                                <li key={category.key}>
                                                    <Link href={category.url} className="group flex items-center gap-4 py-4 transition-colors">
                                                        <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand transition-colors group-hover:bg-brand group-hover:text-brand-ink">
                                                            <CategoryIcon icon={category.icon} />
                                                        </span>
                                                        <span className="min-w-0 flex-1">
                                                            <span className="block font-semibold text-ink">{category.name}</span>
                                                            <span className="block text-sm text-ink-subtle">
                                                                {t('learn.article_count', { count: String(category.count) })}
                                                            </span>
                                                        </span>
                                                        <ArrowRight aria-hidden="true" className="size-4 text-ink-subtle transition-colors group-hover:text-brand rtl:-scale-x-100" />
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                        {links.glossary !== null ? (
                                            <Link href={links.glossary} className="mt-5 inline-flex min-h-11 items-center gap-2 font-semibold text-brand hover:text-brand-strong">
                                                <BookA aria-hidden="true" className="size-4" />
                                                {t('learn.glossary_link')}
                                            </Link>
                                        ) : null}
                                    </div>
                                </div>
                            )}
                        </Container>
                    </section>
                ) : null}

                {/* Privacy */}
                <section id="privacy" aria-labelledby="privacy-title" className="scroll-mt-24 border-y border-line bg-surface-raised py-16 md:py-24">
                    <Container className="grid gap-12 lg:grid-cols-12">
                        <div className="lg:col-span-5">
                            <div className="lg:sticky lg:top-28">
                                <Eyebrow>{t('landing.privacy.eyebrow')}</Eyebrow>
                                <h2 id="privacy-title" className="mt-3 text-3xl text-ink md:text-4xl">
                                    {t('landing.privacy.title')}
                                </h2>
                                <p className="mt-6 rounded-2xl bg-surface p-5 text-sm text-ink-muted">{t('landing.privacy.honesty')}</p>
                            </div>
                        </div>
                        <ol className="divide-y divide-line lg:col-span-7">
                            {PRIVACY_POINTS.map(({ key, icon: Icon }, index) => (
                                <li key={key} className="reveal flex gap-5 py-6 first:pt-0" style={{ '--reveal-index': index } as CSSProperties}>
                                    <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand">
                                        <Icon aria-hidden="true" className="size-5" />
                                    </span>
                                    <p className="pt-2 text-lg text-ink">{t(`landing.privacy.${key}`)}</p>
                                </li>
                            ))}
                        </ol>
                    </Container>
                </section>

                {/* About Rafiq and its limits (always expanded — BR-01-1) */}
                <section id="about" aria-labelledby="about-title" className="scroll-mt-24 py-16 md:py-24">
                    <Container>
                        <div className="max-w-3xl">
                            <Eyebrow>{t('landing.about.eyebrow')}</Eyebrow>
                            <h2 id="about-title" className="mt-3 text-3xl text-ink md:text-4xl">
                                {t('landing.what.title')}
                            </h2>
                            <p className="mt-5 text-lg text-ink-muted md:text-xl">{t('landing.what.body')}</p>
                        </div>

                        <div id="limits" className="mt-12 grid gap-4 md:grid-cols-2">
                            <div className="rounded-[var(--radius-card)] bg-brand-soft p-7">
                                <h3 className="font-display text-xl font-semibold text-ink">{t('landing.limits.can_title')}</h3>
                                <ul className="mt-5 space-y-3">
                                    {CAN.map((key) => (
                                        <li key={key} className="flex items-start gap-3 text-ink">
                                            <Check aria-hidden="true" className="mt-1 size-4 shrink-0 text-brand" />
                                            <span>{t(`landing.limits.${key}`)}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div className="rounded-[var(--radius-card)] border border-danger/20 bg-surface-raised p-7">
                                <h3 className="font-display text-xl font-semibold text-ink">{t('landing.limits.cannot_title')}</h3>
                                <ul className="mt-5 grid gap-3 sm:grid-cols-2">
                                    {CANNOT.map((key) => (
                                        <li key={key} className="flex items-start gap-3 text-ink">
                                            <X aria-hidden="true" className="mt-1 size-4 shrink-0 text-danger" />
                                            <span>{t(`landing.limits.${key}`)}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    </Container>
                </section>

                {/* FAQ */}
                <section id="faq" aria-labelledby="faq-title" className="scroll-mt-24 pb-16 md:pb-24">
                    <Container className="grid gap-10 lg:grid-cols-12">
                        <div className="lg:col-span-4">
                            <Eyebrow>{t('landing.faq.eyebrow')}</Eyebrow>
                            <h2 id="faq-title" className="mt-3 text-3xl text-ink md:text-4xl">
                                {t('landing.faq.title')}
                            </h2>
                        </div>
                        <div className="divide-y divide-line border-y border-line lg:col-span-8">
                            {FAQ.map((number) => (
                                <details key={number} className="group py-2">
                                    <summary className="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 rounded-lg py-3 text-lg font-semibold text-ink marker:hidden [&::-webkit-details-marker]:hidden">
                                        {t(`landing.faq.q${number}`)}
                                        <span className="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-soft text-brand">
                                            <Plus aria-hidden="true" className="size-4 group-open:hidden" />
                                            <Minus aria-hidden="true" className="hidden size-4 group-open:block" />
                                        </span>
                                    </summary>
                                    <p className="max-w-2xl pb-5 text-ink-muted">{t(`landing.faq.a${number}`)}</p>
                                </details>
                            ))}
                        </div>
                    </Container>
                </section>

                {/* Closing call to action */}
                <section aria-labelledby="cta-title" className="pb-20 md:pb-28">
                    <Container>
                        <div className="flex flex-col items-start gap-6 rounded-[var(--radius-panel)] bg-brand p-8 text-brand-ink sm:p-12 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 id="cta-title" className="text-3xl text-white">
                                    {t('landing.cta.title')}
                                </h2>
                                <p className="mt-2 text-white/80">{t('landing.cta.body')}</p>
                            </div>
                            <Link href={links.journeys} className={buttonClasses('inverse', 'lg')}>
                                {t('nav.start')}
                                <ArrowRight aria-hidden="true" className="size-5 rtl:-scale-x-100" />
                            </Link>
                        </div>
                    </Container>
                </section>
            </div>
        </PublicLayout>
    );
}
