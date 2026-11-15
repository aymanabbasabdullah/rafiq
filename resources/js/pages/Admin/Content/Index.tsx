import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, ClipboardCheck, FilePlus2, FileText, Globe, Pencil } from 'lucide-react';
import { PublishBadge, ReviewBadge } from '@/components/admin/ContentStatus';
import { Badge } from '@/components/ui/Badge';
import { ButtonLink } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { PageHeader } from '@/components/ui/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type StatusSummary = { review: string; publish: string; live: boolean } | null;

type ArticleRow = {
    id: number;
    key: string;
    kind: string;
    title: string;
    category: string;
    translations: Record<'ar' | 'en', StatusSummary>;
    editUrl: string;
};

type ContentIndexProps = {
    articles: ArticleRow[];
    filters: { status: string };
    counts: { pending: number; published: number; dueSoon: number };
    createUrl: string;
    indexUrl: string;
    canCreate: boolean;
};

const FILTERS = ['', 'draft', 'pending_medical_review', 'approved', 'published'];
const LOCALES: ('ar' | 'en')[] = ['ar', 'en'];

/**
 * Articles overview with per-language review/publication status (M11B-FR-01).
 */
export default function ContentIndex({ articles, filters, counts, createUrl, indexUrl, canCreate }: ContentIndexProps) {
    const t = useT();

    return (
        <AdminLayout>
            <Head title={t('admin.content.title')} />

            <div className="space-y-8">
                <div className="animate-fade-up">
                    <PageHeader
                        title={t('admin.content.title')}
                        description={t('admin.content.subtitle')}
                        actions={
                            canCreate ? (
                                <ButtonLink href={createUrl}>
                                    <FilePlus2 aria-hidden="true" className="size-4" />
                                    {t('admin.content.new_article')}
                                </ButtonLink>
                            ) : undefined
                        }
                    />
                </div>

                <div className="stagger grid gap-4 sm:grid-cols-3">
                    <StatCard
                        icon={ClipboardCheck}
                        tone="warning"
                        value={counts.pending}
                        label={t('admin.content.count_pending')}
                    />
                    <StatCard icon={Globe} tone="brand" value={counts.published} label={t('admin.content.count_published')} />
                    <StatCard icon={CalendarClock} tone="info" value={counts.dueSoon} label={t('admin.content.count_due_soon')} />
                </div>

                <div role="tablist" aria-label={t('admin.content.filter')} className="flex flex-wrap gap-2">
                    {FILTERS.map((status) => {
                        const active = filters.status === status;

                        return (
                            <button
                                key={status || 'all'}
                                type="button"
                                role="tab"
                                aria-selected={active}
                                onClick={() =>
                                    router.get(indexUrl, status === '' ? {} : { status }, {
                                        preserveScroll: true,
                                        preserveState: true,
                                    })
                                }
                                className={`min-h-9 cursor-pointer rounded-full border px-4 text-sm font-medium transition-colors ${
                                    active
                                        ? 'border-brand bg-brand text-brand-ink'
                                        : 'border-line bg-surface-raised text-ink-muted hover:border-line-strong'
                                }`}
                            >
                                {t(`admin.content.filter_${status || 'all'}`)}
                            </button>
                        );
                    })}
                </div>

                {articles.length === 0 ? (
                    <EmptyState
                        icon={FileText}
                        title={t('admin.content.empty_title')}
                        description={t('admin.content.empty_body')}
                        action={
                            canCreate ? (
                                <ButtonLink href={createUrl} size="sm">
                                    {t('admin.content.new_article')}
                                </ButtonLink>
                            ) : undefined
                        }
                    />
                ) : (
                    <ul className="stagger grid gap-3">
                        {articles.map((article) => (
                            <li key={article.id}>
                                <Card className="hover-lift flex flex-col gap-4 p-5 lg:flex-row lg:items-center">
                                    <div className="min-w-0 flex-1">
                                        <div className="flex flex-wrap items-center gap-2 text-xs text-ink-subtle">
                                            <span className="font-medium text-brand">{article.category}</span>
                                            <Badge>{t(`admin.content.kind.${article.kind}`)}</Badge>
                                            <code dir="ltr" className="font-mono">
                                                {article.key}
                                            </code>
                                        </div>
                                        <Link
                                            href={article.editUrl}
                                            className="mt-1.5 block truncate text-base font-semibold text-ink hover:text-brand"
                                        >
                                            {article.title}
                                        </Link>
                                    </div>
                                    <div className="grid gap-2 sm:grid-cols-2 lg:w-[28rem]">
                                        {LOCALES.map((locale) => {
                                            const status = article.translations[locale];

                                            return (
                                                <div
                                                    key={locale}
                                                    className="flex flex-wrap items-center gap-1.5 rounded-xl bg-surface px-3 py-2"
                                                >
                                                    <span className="w-7 text-xs font-bold text-ink-subtle uppercase">
                                                        {locale}
                                                    </span>
                                                    {status === null ? (
                                                        <Badge>{t('admin.content.missing_translation')}</Badge>
                                                    ) : (
                                                        <>
                                                            <ReviewBadge status={status.review} />
                                                            <PublishBadge status={status.publish} live={status.live} />
                                                        </>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                    <ButtonLink href={article.editUrl} variant="secondary" size="sm">
                                        <Pencil aria-hidden="true" className="size-4" />
                                        {t('admin.content.edit')}
                                    </ButtonLink>
                                </Card>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AdminLayout>
    );
}
