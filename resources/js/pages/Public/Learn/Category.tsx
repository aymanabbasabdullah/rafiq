import { Link } from '@inertiajs/react';
import { ArrowRight, FileText } from 'lucide-react';
import { ArticleCard, type ArticleCardData } from '@/components/learn/ArticleCard';
import { CategoryIcon } from '@/components/learn/CategoryIcon';
import { EmptyState } from '@/components/ui/EmptyState';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';
import { useReveal } from '@/lib/useReveal';

type CategoryProps = {
    category: { name: string; description: string | null; icon: string };
    articles: ArticleCardData[];
    learnUrl: string;
};

/**
 * Articles in one Knowledge Center topic (M08-FR-02).
 */
export default function Category({ category, articles, learnUrl }: CategoryProps) {
    const t = useT();
    const container = useReveal<HTMLDivElement>();

    return (
        <PublicLayout>
            <div ref={container} className="space-y-8">
                <nav aria-label={t('learn.breadcrumb')} className="text-sm">
                    <Link href={learnUrl} className="inline-flex items-center gap-1 font-medium text-brand hover:underline">
                        <ArrowRight aria-hidden="true" className="size-4 rotate-180 rtl:rotate-0" />
                        {t('learn.back_to_center')}
                    </Link>
                </nav>

                <header className="flex animate-fade-up items-start gap-4">
                    <span className="inline-flex size-14 shrink-0 items-center justify-center rounded-2xl bg-brand-soft text-brand">
                        <CategoryIcon icon={category.icon} className="size-7" />
                    </span>
                    <div>
                        <h1 className="text-3xl font-semibold text-ink">{category.name}</h1>
                        {category.description !== null ? <p className="mt-2 max-w-2xl text-ink-muted">{category.description}</p> : null}
                    </div>
                </header>

                {articles.length === 0 ? (
                    <EmptyState icon={FileText} title={t('learn.category_empty')} />
                ) : (
                    <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {articles.map((article, index) => (
                            <li key={article.url}>
                                <ArticleCard article={{ ...article, category: null }} index={index} />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </PublicLayout>
    );
}
