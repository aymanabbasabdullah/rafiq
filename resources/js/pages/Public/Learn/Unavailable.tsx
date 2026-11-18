import { Link } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';

type UnavailableProps = {
    available: { locale: string; name: string; title: string | null; url: string }[];
    learnUrl: string;
};

/**
 * Shown when an article is not yet approved in the visitor's language. Medical content never falls back
 * silently to another language (FR-072); the visitor chooses explicitly.
 */
export default function Unavailable({ available, learnUrl }: UnavailableProps) {
    const t = useT();

    return (
        <PublicLayout>
            <section className="mx-auto max-w-xl animate-fade-up py-10 text-center">
                <span className="inline-flex size-14 items-center justify-center rounded-2xl bg-brand-soft text-brand">
                    <Languages aria-hidden="true" className="size-7" />
                </span>
                <h1 className="mt-5 text-2xl font-semibold text-ink">{t('learn.unavailable_title')}</h1>
                <p className="mt-3 text-ink-muted">{t('learn.unavailable_body')}</p>
                <ul className="mt-6 space-y-3">
                    {available.map((option) => (
                        <li key={option.locale}>
                            <a
                                href={option.url}
                                hrefLang={option.locale}
                                className="hover-lift block rounded-2xl border border-line bg-surface-raised p-4 shadow-card"
                            >
                                <span className="text-sm font-semibold text-brand">{t('learn.read_in', { language: option.name })}</span>
                                {option.title !== null ? (
                                    <span className="mt-1 block font-medium text-ink" lang={option.locale} dir={option.locale === 'ar' ? 'rtl' : 'ltr'}>
                                        {option.title}
                                    </span>
                                ) : null}
                            </a>
                        </li>
                    ))}
                </ul>
                <Link href={learnUrl} className="mt-6 inline-block text-sm font-medium text-brand hover:underline">
                    {t('learn.back_to_center')}
                </Link>
            </section>
        </PublicLayout>
    );
}
