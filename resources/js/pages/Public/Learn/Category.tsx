import { Link } from '@inertiajs/react';
import { ArrowLeft, FileText } from 'lucide-react';
import { ArticleList, type ArticleCardData } from '@/components/learn/ArticleCard';
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
            <div ref={container} className="mx-auto max-w-4xl">
                <nav aria-label={t('learn.breadcrumb')}>
                    <Link href={learnUrl} className="inline-flex min-h-11 items-center gap-2 text-sm font-medium text-brand hover:text-brand-strong">
                        <ArrowLeft aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                        {t('learn.back_to_center')}
                    </Link>
                </nav>

                <header className="mt-4 flex animate-fade-up flex-col gap-6 sm:flex-row sm:items-start">
                    <span className="inline-flex size-16 shrink-0 items-center justify-center rounded-2xl bg-brand-soft text-brand">
                        <CategoryIcon icon={category.icon} className="size-8" />
                    </span>
                    <div>
                        <h1 className="text-3xl text-ink md:text-5xl md:leading-[1.15]">{category.name}</h1>
                        {category.description !== null ? <p className="mt-4 max-w-2xl text-lg text-ink-muted">{category.description}</p> : null}
                    </div>
                </header>

                <div className="mt-12">
                    {articles.length === 0 ? <EmptyState icon={FileText} title={t('learn.category_empty')} /> : <ArticleList articles={articles} hideCategory />}
                </div>
            </div>
        </PublicLayout>
    );
}
