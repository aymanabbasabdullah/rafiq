import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties } from 'react';
import { useT } from '@/lib/i18n';

export type ArticleCardData = {
    title: string;
    summary: string | null;
    url: string;
    category: string | null;
};

/**
 * Article teaser card with a gentle lift on hover.
 */
export function ArticleCard({ article, index = 0 }: { article: ArticleCardData; index?: number }) {
    const t = useT();

    return (
        <Link
            href={article.url}
            className="reveal hover-lift group flex h-full flex-col rounded-2xl border border-line bg-surface-raised p-5 shadow-card hover:border-brand/40"
            style={{ '--reveal-index': index } as CSSProperties}
        >
            {article.category !== null ? <span className="text-xs font-semibold text-brand">{article.category}</span> : null}
            <h3 className="mt-2 text-lg font-semibold text-ink">{article.title}</h3>
            {article.summary !== null ? <p className="mt-2 line-clamp-3 text-sm text-ink-muted">{article.summary}</p> : null}
            <span className="mt-auto inline-flex items-center gap-1 pt-4 text-sm font-semibold text-brand">
                {t('learn.read_article')}
                <ArrowRight
                    aria-hidden="true"
                    className="size-4 transition-transform group-hover:translate-x-0.5 rtl:-scale-x-100 rtl:group-hover:-translate-x-0.5"
                />
            </span>
        </Link>
    );
}
