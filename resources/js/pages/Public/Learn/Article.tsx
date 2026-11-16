import { Link } from '@inertiajs/react';
import { ArrowRight, ExternalLink, MessagesSquare } from 'lucide-react';
import { ArticleList, type ArticleCardData } from '@/components/learn/ArticleCard';
import { ReviewStamp } from '@/components/learn/ReviewStamp';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';
import { useReveal } from '@/lib/useReveal';

type ArticleProps = {
    article: {
        key: string;
        title: string;
        summary: string | null;
        bodyHtml: string;
        version: number;
        approvedAt: string;
        reviewDueAt: string | null;
        aiEnabled: boolean;
    };
    category: { name: string; url: string };
    sources: { id: number; title: string; publisher: string; url: string; date: string | null }[];
    related: ArticleCardData[];
    learnUrl: string;
};

/**
 * Article page (M08-FR-03): approved revision, review stamp, sources and related articles.
 * `bodyHtml` is sanitised on the server by MarkdownRenderer (raw HTML stripped, unsafe links removed — SEC-010).
 */
export default function Article({ article, category, sources, related, learnUrl }: ArticleProps) {
    const t = useT();
    const container = useReveal<HTMLDivElement>();

    return (
        <PublicLayout>
            <div ref={container} className="mx-auto max-w-3xl space-y-10">
                <nav aria-label={t('learn.breadcrumb')} className="flex flex-wrap items-center gap-2 text-sm text-ink-muted">
                    <Link href={learnUrl} className="font-medium text-brand hover:underline">
                        {t('learn.eyebrow')}
                    </Link>
                    <ArrowRight aria-hidden="true" className="size-3.5 rtl:-scale-x-100" />
                    <Link href={category.url} className="font-medium text-brand hover:underline">
                        {category.name}
                    </Link>
                </nav>

                <header className="animate-fade-up space-y-6">
                    <h1 className="text-3xl text-ink md:text-5xl md:leading-[1.15]">{article.title}</h1>
                    {article.summary !== null ? <p className="text-lg text-ink-muted md:text-xl">{article.summary}</p> : null}
                    <ReviewStamp approvedAt={article.approvedAt} reviewDueAt={article.reviewDueAt} version={article.version} />
                </header>

                {article.aiEnabled ? (
                    <div className="flex items-center gap-3 rounded-2xl border border-dashed border-brand/30 bg-brand-mist px-4 py-3 text-sm text-brand-strong">
                        <MessagesSquare aria-hidden="true" className="size-4 shrink-0" />
                        <span>{t('learn.ai_coming')}</span>
                    </div>
                ) : null}

                <article
                    className="rafiq-prose animate-fade-up"
                    dangerouslySetInnerHTML={{ __html: article.bodyHtml }}
                />

                {sources.length > 0 ? (
                    <section aria-labelledby="sources-title" className="reveal rounded-[var(--radius-card)] bg-surface-raised p-6 shadow-card sm:p-7">
                        <h2 id="sources-title" className="font-display text-xl font-semibold text-ink">
                            {t('learn.sources_title')}
                        </h2>
                        <ol className="mt-3 list-decimal space-y-3 ps-5 text-sm">
                            {sources.map((source) => (
                                <li key={source.id}>
                                    <a
                                        href={source.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        dir="ltr"
                                        lang="en"
                                        className="inline-flex items-start gap-1.5 font-medium text-brand underline-offset-4 hover:underline"
                                    >
                                        {source.title}
                                        <ExternalLink aria-hidden="true" className="mt-0.5 size-3.5 shrink-0" />
                                    </a>
                                    <span className="sr-only">{t('legal.sources.opens_new_tab')}</span>
                                    <p className="text-ink-subtle" dir="ltr" lang="en">
                                        {source.publisher}
                                        {source.date !== null ? `, ${source.date}` : ''}
                                    </p>
                                </li>
                            ))}
                        </ol>
                        <p className="mt-4 text-xs text-ink-subtle">{t('learn.sources_note')}</p>
                    </section>
                ) : null}

                <p className="border-s-4 border-brand ps-4 text-ink-muted">{t('footer.not_diagnosis')}</p>

                {related.length > 0 ? (
                    <section aria-labelledby="related-title">
                        <h2 id="related-title" className="text-2xl text-ink">
                            {t('learn.related')}
                        </h2>
                        <div className="mt-4">
                            <ArticleList articles={related} hideCategory />
                        </div>
                    </section>
                ) : null}
            </div>
        </PublicLayout>
    );
}
