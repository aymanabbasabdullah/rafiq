import { Link, usePage } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import { useT } from '@/lib/i18n';

/**
 * Switches to the same page in the other language (L10N-003). Shows the target language in its own script.
 */
export function LanguageSwitcher({ tone = 'light' }: { tone?: 'light' | 'dark' }) {
    const { locale } = usePage().props;
    const t = useT();
    const { alternate } = locale;

    const colours = tone === 'light' ? 'text-ink hover:bg-brand-soft hover:text-brand-strong' : 'text-white/85 hover:bg-white/10 hover:text-white';

    return (
        <Link
            href={alternate.url}
            hrefLang={alternate.code}
            lang={alternate.code}
            aria-label={t('language.switch_to', { language: alternate.name })}
            className={`inline-flex min-h-11 items-center gap-2 rounded-full px-3 text-sm font-medium transition-colors ${colours}`}
        >
            <Languages aria-hidden="true" className="size-4" />
            <span className="hidden sm:inline">{alternate.name}</span>
        </Link>
    );
}
