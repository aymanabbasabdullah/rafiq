import { Link } from '@inertiajs/react';
import { BookA, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { EmptyState } from '@/components/ui/EmptyState';
import { ExplainTermButton } from '@/features/ai-chat/ExplainTermButton';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';

type GlossaryProps = {
    terms: { id: number; termId: number; term: string; definitionHtml: string }[];
    learnUrl: string;
    aiEnabled: boolean;
};

/**
 * Glossary of approved terms (FR-073) with instant local filtering — nothing is sent to the server.
 */
export default function Glossary({ terms, learnUrl, aiEnabled }: GlossaryProps) {
    const t = useT();
    const [filter, setFilter] = useState('');

    const visible = useMemo(() => {
        const needle = filter.trim().toLocaleLowerCase();

        return needle === '' ? terms : terms.filter((term) => term.term.toLocaleLowerCase().includes(needle));
    }, [filter, terms]);

    return (
        <PublicLayout>
            <div className="mx-auto max-w-3xl space-y-8">
                <header className="animate-fade-up">
                    <Link href={learnUrl} className="text-sm font-medium text-brand hover:underline">
                        {t('learn.back_to_center')}
                    </Link>
                    <h1 className="mt-3 flex items-center gap-3 text-3xl font-semibold text-ink">
                        <BookA aria-hidden="true" className="size-8 text-brand" />
                        {t('learn.glossary_title')}
                    </h1>
                    <p className="mt-2 text-ink-muted">{t('learn.glossary_subtitle')}</p>
                </header>

                <div className="relative">
                    <label htmlFor="glossary-filter" className="sr-only">
                        {t('learn.glossary_filter')}
                    </label>
                    <Search aria-hidden="true" className="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-ink-subtle" />
                    <input
                        id="glossary-filter"
                        type="search"
                        value={filter}
                        onChange={(event) => setFilter(event.target.value)}
                        placeholder={t('learn.glossary_filter')}
                        className="block min-h-12 w-full rounded-2xl border border-line bg-surface-raised ps-12 pe-4 text-ink shadow-card focus:border-brand focus:ring-4 focus:ring-brand/15 focus:outline-none"
                    />
                </div>

                {visible.length === 0 ? (
                    <EmptyState icon={BookA} title={t('learn.glossary_empty')} />
                ) : (
                    <dl className="stagger divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface-raised shadow-card">
                        {visible.map((term) => (
                            <div key={term.id} className="px-5 py-4">
                                <dt className="font-semibold text-ink">{term.term}</dt>
                                <dd
                                    className="rafiq-prose mt-1 text-sm"
                                    dangerouslySetInnerHTML={{ __html: term.definitionHtml }}
                                />
                                {aiEnabled ? <ExplainTermButton termId={term.termId} term={term.term} /> : null}
                            </div>
                        ))}
                    </dl>
                )}
            </div>
        </PublicLayout>
    );
}
