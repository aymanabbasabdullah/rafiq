import { Link, usePage } from '@inertiajs/react';
import { useT } from '@/lib/i18n';

/**
 * Switches to the same page in the other language (L10N-003).
 */
export function LanguageSwitcher() {
    const { locale } = usePage().props;
    const t = useT();
    const { alternate } = locale;

    return (
        <Link
            href={alternate.url}
            hrefLang={alternate.code}
            lang={alternate.code}
            aria-label={t('language.switch_to', { language: alternate.name })}
            className="rounded-md border border-line px-3 py-1.5 text-sm font-medium text-ink hover:bg-surface"
        >
            {alternate.name}
        </Link>
    );
}
