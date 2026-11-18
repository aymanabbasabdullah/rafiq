import { Link } from '@inertiajs/react';
import { Loader2, Search, X } from 'lucide-react';
import { type FormEvent, useRef, useState } from 'react';
import { postJson } from '@/lib/http';
import { useT } from '@/lib/i18n';

type Result = { title: string; summary: string | null; url: string };

/**
 * Search box that sends the query as JSON POST, so it never lands in the URL or browser history (PRIV-003).
 */
export function KnowledgeSearch({ searchUrl }: { searchUrl: string }) {
    const t = useT();
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Result[] | null>(null);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const controller = useRef<AbortController | null>(null);

    const submit = async (event: FormEvent) => {
        event.preventDefault();

        if (query.trim().length < 2) {
            return;
        }

        controller.current?.abort();
        controller.current = new AbortController();
        setLoading(true);
        setFailed(false);

        try {
            const response = await postJson<{ results: Result[] }>(searchUrl, { query }, controller.current.signal);
            setResults(response.results);
        } catch {
            setFailed(true);
        } finally {
            setLoading(false);
        }
    };

    const clear = () => {
        setQuery('');
        setResults(null);
        setFailed(false);
    };

    return (
        <div>
            <form onSubmit={(event) => void submit(event)} role="search" className="relative">
                <label htmlFor="knowledge-search" className="sr-only">
                    {t('learn.search_label')}
                </label>
                <Search
                    aria-hidden="true"
                    className="pointer-events-none absolute start-4 top-1/2 size-5 -translate-y-1/2 text-ink-subtle"
                />
                <input
                    id="knowledge-search"
                    type="search"
                    value={query}
                    maxLength={100}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder={t('learn.search_placeholder')}
                    autoComplete="off"
                    className="block min-h-15 w-full rounded-full border border-line bg-surface-raised ps-12 pe-32 text-base text-ink shadow-card transition-shadow placeholder:text-ink-subtle focus:border-brand focus:ring-4 focus:ring-brand/15 focus:outline-none"
                />
                <div className="absolute end-2 top-1/2 flex -translate-y-1/2 items-center gap-1">
                    {query !== '' ? (
                        <button
                            type="button"
                            onClick={clear}
                            aria-label={t('learn.search_clear')}
                            className="inline-flex size-10 cursor-pointer items-center justify-center rounded-full text-ink-subtle hover:bg-surface-sunken"
                        >
                            <X aria-hidden="true" className="size-4" />
                        </button>
                    ) : null}
                    <button
                        type="submit"
                        className="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-full bg-brand px-5 text-sm font-semibold text-brand-ink hover:bg-brand-strong"
                    >
                        {loading ? <Loader2 aria-hidden="true" className="size-4 animate-spin" /> : null}
                        {t('learn.search_button')}
                    </button>
                </div>
            </form>
            <p className="mt-2 text-xs text-ink-subtle">{t('learn.search_privacy')}</p>

            <div aria-live="polite">
                {failed ? <p className="mt-4 text-sm font-medium text-danger">{t('learn.search_error')}</p> : null}
                {results !== null && !failed ? (
                    results.length === 0 ? (
                        <p className="mt-4 rounded-xl bg-surface-sunken px-4 py-3 text-sm text-ink-muted">
                            {t('learn.search_empty')}
                        </p>
                    ) : (
                        <ul className="stagger mt-4 divide-y divide-line overflow-hidden rounded-[var(--radius-card)] bg-surface-raised shadow-card">
                            {results.map((result) => (
                                <li key={result.url}>
                                    <Link href={result.url} className="block px-5 py-4 transition-colors hover:bg-surface">
                                        <span className="font-semibold text-ink">{result.title}</span>
                                        {result.summary !== null ? (
                                            <span className="mt-1 block line-clamp-2 text-sm text-ink-muted">
                                                {result.summary}
                                            </span>
                                        ) : null}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )
                ) : null}
            </div>
        </div>
    );
}
