import { Link, usePage } from '@inertiajs/react';
import { BrandMark } from '@/components/public/BrandMark';
import { LanguageSwitcher } from '@/components/public/LanguageSwitcher';
import { QuickExitButton } from '@/components/public/QuickExitButton';
import { useT } from '@/lib/i18n';

export function SiteHeader() {
    const { links } = usePage().props;
    const t = useT();

    return (
        <header className="border-b border-line bg-surface-raised">
            <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                <Link href={links.home} className="flex items-center gap-2 text-brand" aria-label={t('nav.home')}>
                    <BrandMark />
                    <span className="text-xl font-semibold">{t('app.name')}</span>
                </Link>
                <div className="flex items-center gap-2">
                    <LanguageSwitcher />
                    <QuickExitButton />
                </div>
            </div>
        </header>
    );
}
