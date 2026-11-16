import { Link } from '@inertiajs/react';
import { ArrowRight, BookA, LibraryBig } from 'lucide-react';
import type { CSSProperties } from 'react';
import { ArticleList, type ArticleCardData } from '@/components/learn/ArticleCard';
import { CategoryIcon } from '@/components/learn/CategoryIcon';
import { KnowledgeSearch } from '@/components/learn/KnowledgeSearch';
import { ChangeJourney } from '@/components/public/ChangeJourney';
import { RedFlagReminder } from '@/components/public/RedFlagReminder';
import { Eyebrow } from '@/components/public/Section';
import { EmptyState } from '@/components/ui/EmptyState';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';
import { useReveal } from '@/lib/useReveal';
import type { JourneyKey } from '@/types';

type CategoryCard = { key: string; icon: string; name: string; description: string | null; url: string; count: number };

type LearnIndexProps = {
    categories: CategoryCard[];
    latest: ArticleCardData[];
    searchUrl: string;
    glossaryUrl: string;
    concernUrl: string | null;
    otherJourneys: { key: JourneyKey; url: string | null }[];
};

/**
 * Knowledge Center home — the J1 "I want to learn" journey (FR-011, FR-070). No questionnaire.
 * Search first, then topics as an index, then the latest articles as an editorial list.
 */
export default function LearnIndex({ categories, latest, searchUrl, glossaryUrl, concernUrl, otherJourneys }: LearnIndexProps) {
    const t = useT();
    const container = useReveal<HTMLDivElement>();

    return (
        <PublicLayout>
            <div ref={container} className="space-y-16 md:space-y-20">
                <section aria-labelledby="learn-title" className="relative overflow-hidden rounded-[var(--radius-panel)] bg-brand-soft px-6 py-12 sm:px-10 md:px-14 md:py-16">
                    <svg aria-hidden="true" viewBox="0 0 400 400" className="pointer-events-none absolute -end-24 -top-24 size-[30rem] text-brand/10">
                        {[60, 100, 140, 180].map((radius) => (
                            <circle key={radius} cx="200" cy="200" r={radius} fill="none" stroke="currentColor" strokeWidth="1.5" />
                        ))}
                    </svg>
                    <div className="relative max-w-2xl animate-fade-up">
                        <Eyebrow>{t('learn.eyebrow')}</Eyebrow>
                        <h1 id="learn-title" className="mt-4 text-3xl text-ink md:text-5xl md:leading-[1.15]">
                            {t('learn.title')}
                        </h1>
                        <p className="mt-4 text-lg text-ink-muted">{t('learn.subtitle')}</p>
                        <div className="mt-8">
                            <KnowledgeSearch searchUrl={searchUrl} />
                        </div>
                    </div>
                </section>

                <RedFlagReminder concernUrl={concernUrl} />

                <section aria-labelledby="topics-title">
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <h2 id="topics-title" className="text-3xl text-ink">
                            {t('learn.topics')}
                        </h2>
                        <Link href={glossaryUrl} className="inline-flex min-h-11 items-center gap-2 font-semibold text-brand hover:text-brand-strong">
                            <BookA aria-hidden="true" className="size-4" />
                            {t('learn.glossary_link')}
                        </Link>
                    </div>
                    {categories.length === 0 ? (
                        <div className="mt-8">
                            <EmptyState icon={LibraryBig} title={t('learn.empty_title')} description={t('learn.empty_body')} />
                        </div>
                    ) : (
                        <ul className="mt-8 grid border-t border-line md:grid-cols-2 md:gap-x-10">
                            {categories.map((category, index) => (
                                <li key={category.key} className="border-b border-line">
                                    <Link
                                        href={category.url}
                                        className="reveal group flex h-full items-start gap-5 py-6"
                                        style={{ '--reveal-index': index } as CSSProperties}
                                    >
                                        <span className="inline-flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand-soft text-brand transition-colors duration-300 group-hover:bg-brand group-hover:text-brand-ink">
                                            <CategoryIcon icon={category.icon} className="size-6" />
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="flex flex-wrap items-baseline justify-between gap-x-3">
                                                <span className="font-display text-xl font-semibold text-ink group-hover:text-brand-strong">{category.name}</span>
                                                <span className="text-sm text-ink-subtle">{t('learn.article_count', { count: category.count })}</span>
                                            </span>
                                            {category.description !== null ? <span className="mt-1.5 block text-ink-muted">{category.description}</span> : null}
                                            <span className="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand">
                                                {t('learn.browse_topic')}
                                                <ArrowRight aria-hidden="true" className="size-4 transition-transform group-hover:translate-x-1 rtl:-scale-x-100 rtl:group-hover:-translate-x-1" />
                                            </span>
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {latest.length > 0 ? (
                    <section aria-labelledby="latest-title" className="grid gap-8 lg:grid-cols-12">
                        <h2 id="latest-title" className="text-3xl text-ink lg:col-span-4">
                            {t('learn.latest')}
                        </h2>
                        <div className="lg:col-span-8">
                            <ArticleList articles={latest} />
                        </div>
                    </section>
                ) : null}

                <ChangeJourney journeys={otherJourneys} />
            </div>
        </PublicLayout>
    );
}
