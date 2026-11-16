import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties } from 'react';

export type ArticleCardData = {
    title: string;
    summary: string | null;
    url: string;
    category: string | null;
};

/**
 * Article teaser as an editorial row: topic label, title, summary and a quiet arrow. Rows sit in a
 * divided list, which reads faster than a grid of identical cards.
 */
export function ArticleCard({ article, index = 0 }: { article: ArticleCardData; index?: number }) {
    return (
        <Link
            href={article.url}
            className="reveal group flex items-start gap-6 py-6 transition-colors"
            style={{ '--reveal-index': index } as CSSProperties}
        >
            <div className="min-w-0 flex-1">
                {article.category !== null ? <span className="text-sm font-semibold text-brand">{article.category}</span> : null}
                <h3 className="mt-1 font-display text-xl font-semibold text-ink transition-colors group-hover:text-brand-strong">
                    {article.title}
                </h3>
                {article.summary !== null ? (
                    <p className="mt-2 line-clamp-2 max-w-2xl text-ink-muted">{article.summary}</p>
                ) : null}
            </div>
            <span className="mt-1 inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-line text-ink-muted transition-colors group-hover:border-brand group-hover:bg-brand group-hover:text-brand-ink">
                <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
            </span>
        </Link>
    );
}

/**
 * Divided list wrapper for article rows.
 */
export function ArticleList({ articles, hideCategory = false }: { articles: ArticleCardData[]; hideCategory?: boolean }) {
    return (
        <ul className="divide-y divide-line border-y border-line">
            {articles.map((article, index) => (
                <li key={article.url}>
                    <ArticleCard article={hideCategory ? { ...article, category: null } : article} index={index} />
                </li>
            ))}
        </ul>
    );
}
