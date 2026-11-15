import { Link } from '@inertiajs/react';
import { ArrowRight, BookA, LibraryBig } from 'lucide-react';
import type { CSSProperties } from 'react';
import { ArticleCard, type ArticleCardData } from '@/components/learn/ArticleCard';
import { CategoryIcon } from '@/components/learn/CategoryIcon';
import { KnowledgeSearch } from '@/components/learn/KnowledgeSearch';
import { ChangeJourney } from '@/components/public/ChangeJourney';
import { RedFlagReminder } from '@/components/public/RedFlagReminder';
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
 */
export default function LearnIndex({ categories, latest, searchUrl, glossaryUrl, concernUrl, otherJourneys }: LearnIndexProps) {
    const t = useT();
    const container = useReveal<HTMLDivElement>();

    return (
        <PublicLayout>
            <div ref={container} className="space-y-14">
                <section aria-labelledby="learn-title" className="relative overflow-hidden rounded-3xl bg-brand-soft px-6 py-10 md:px-12 md:py-14">
                    <div aria-hidden="true" className="pointer-events-none absolute inset-0">
                        <div className="absolute -top-16 end-[-4rem] size-64 animate-float rounded-full bg-brand/15 blur-2xl" />
                        <div className="absolute start-[-3rem] bottom-[-5rem] size-72 animate-float rounded-full bg-accent/15 blur-2xl [animation-delay:-5s]" />
                    </div>
                    <div className="relative max-w-2xl animate-fade-up">
                        <span className="inline-flex items-center gap-2 rounded-full bg-surface-raised/80 px-3 py-1 text-sm font-semibold text-brand">
                            <LibraryBig aria-hidden="true" className="size-4" />
                            {t('learn.eyebrow')}
                        </span>
                        <h1 id="learn-title" className="mt-4 text-3xl font-semibold text-ink md:text-4xl">
                            {t('learn.title')}
                        </h1>
                        <p className="mt-3 text-lg text-ink-muted">{t('learn.subtitle')}</p>
                        <div className="mt-7">
                            <KnowledgeSearch searchUrl={searchUrl} />
                        </div>
                    </div>
                </section>

                <RedFlagReminder concernUrl={concernUrl} />

                <section aria-labelledby="topics-title">
                    <div className="flex flex-wrap items-end justify-between gap-3">
                        <h2 id="topics-title" className="text-2xl font-semibold text-ink">
                            {t('learn.topics')}
                        </h2>
                        <Link href={glossaryUrl} className="inline-flex items-center gap-2 text-sm font-semibold text-brand underline-offset-4 hover:underline">
                            <BookA aria-hidden="true" className="size-4" />
                            {t('learn.glossary_link')}
                        </Link>
                    </div>
                    {categories.length === 0 ? (
                        <div className="mt-6">
                            <EmptyState icon={LibraryBig} title={t('learn.empty_title')} description={t('learn.empty_body')} />
                        </div>
                    ) : (
                        <ul className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {categories.map((category, index) => (
                                <li key={category.key}>
                                    <Link
                                        href={category.url}
                                        className="reveal hover-lift group flex h-full flex-col rounded-2xl border border-line bg-surface-raised p-5 shadow-card hover:border-brand/40"
                                        style={{ '--reveal-index': index } as CSSProperties}
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="inline-flex size-12 items-center justify-center rounded-2xl bg-brand-soft text-brand transition-transform duration-300 group-hover:scale-105">
                                                <CategoryIcon icon={category.icon} className="size-6" />
                                            </span>
                                            <span className="rounded-full bg-surface px-3 py-1 text-xs font-medium text-ink-muted">
                                                {t('learn.article_count', { count: category.count })}
                                            </span>
                                        </div>
                                        <h3 className="mt-4 text-lg font-semibold text-ink">{category.name}</h3>
                                        {category.description !== null ? <p className="mt-1.5 text-sm text-ink-muted">{category.description}</p> : null}
                                        <span className="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-brand">
                                            {t('learn.browse_topic')}
                                            <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>

                {latest.length > 0 ? (
                    <section aria-labelledby="latest-title">
                        <h2 id="latest-title" className="text-2xl font-semibold text-ink">
                            {t('learn.latest')}
                        </h2>
                        <ul className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {latest.map((article, index) => (
                                <li key={article.url}>
                                    <ArticleCard article={article} index={index} />
                                </li>
                            ))}
                        </ul>
                    </section>
                ) : null}

                <ChangeJourney journeys={otherJourneys} />
            </div>
        </PublicLayout>
    );
}
