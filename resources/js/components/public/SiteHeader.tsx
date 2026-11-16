import { Link, router, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { BrandLockup } from '@/components/public/BrandMark';
import { LanguageSwitcher } from '@/components/public/LanguageSwitcher';
import { QuickExitButton } from '@/components/public/QuickExitButton';
import { buttonClasses } from '@/components/ui/Button';
import { useT } from '@/lib/i18n';

type NavItem = { key: string; label: string; href: string; anchor: boolean };

/**
 * Public header: identity, primary navigation, language switch, Quick exit (always visible — FR-005)
 * and the main call to action. Below `lg` the navigation folds into a disclosure panel.
 */
export function SiteHeader() {
    const { links } = usePage().props;
    const t = useT();
    const [menuOpen, setMenuOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    const items: NavItem[] = [
        { key: 'home', label: t('nav.home'), href: links.home, anchor: false },
        { key: 'how', label: t('nav.how'), href: `${links.home}#how`, anchor: true },
        ...(links.learn !== null ? [{ key: 'learn', label: t('nav.learn'), href: links.learn, anchor: false }] : []),
        { key: 'about', label: t('nav.about'), href: `${links.home}#about`, anchor: true },
    ];

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 8);

        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => router.on('navigate', () => setMenuOpen(false)), []);

    useEffect(() => {
        if (!menuOpen) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setMenuOpen(false);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [menuOpen]);

    const linkClass = 'rounded-full px-3.5 py-2 text-sm font-medium text-ink-muted transition-colors hover:bg-brand-soft hover:text-brand-strong';

    const renderLink = (item: NavItem, className: string) =>
        item.anchor ? (
            <a key={item.key} href={item.href} className={className} onClick={() => setMenuOpen(false)}>
                {item.label}
            </a>
        ) : (
            <Link key={item.key} href={item.href} className={className}>
                {item.label}
            </Link>
        );

    return (
        <header
            className={`sticky top-0 z-40 border-b bg-surface/85 backdrop-blur-md transition-colors duration-300 ${
                scrolled || menuOpen ? 'border-line' : 'border-transparent'
            }`}
        >
            <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6 md:h-18 lg:px-8">
                <Link href={links.home} aria-label={t('nav.home')} className="shrink-0 rounded-lg">
                    <BrandLockup name={t('app.name')} />
                </Link>

                <nav aria-label={t('nav.main_label')} className="hidden lg:block">
                    <ul className="flex items-center gap-1">
                        {items.map((item) => (
                            <li key={item.key}>{renderLink(item, linkClass)}</li>
                        ))}
                    </ul>
                </nav>

                <div className="flex items-center gap-2">
                    <LanguageSwitcher />
                    <QuickExitButton />
                    <div className="hidden md:block">
                        <Link href={links.journeys} className={buttonClasses('primary')}>
                            {t('nav.start')}
                        </Link>
                    </div>
                    <button
                        type="button"
                        onClick={() => setMenuOpen((open) => !open)}
                        aria-expanded={menuOpen}
                        aria-controls="site-menu"
                        aria-label={menuOpen ? t('nav.menu_close') : t('nav.menu_open')}
                        className="inline-flex size-11 cursor-pointer items-center justify-center rounded-full text-ink transition-colors hover:bg-brand-soft lg:hidden"
                    >
                        {menuOpen ? <X aria-hidden="true" className="size-5" /> : <Menu aria-hidden="true" className="size-5" />}
                    </button>
                </div>
            </div>

            <div id="site-menu" hidden={!menuOpen} className="border-t border-line bg-surface lg:hidden">
                <nav aria-label={t('nav.main_label')} className="mx-auto max-w-6xl px-4 pt-3 pb-6 sm:px-6">
                    <ul className="divide-y divide-line">
                        {items.map((item) => (
                            <li key={item.key}>{renderLink(item, 'flex min-h-13 items-center text-lg font-medium text-ink')}</li>
                        ))}
                    </ul>
                    <Link href={links.journeys} className={buttonClasses('primary', 'lg', 'mt-4 w-full')}>
                        {t('nav.start')}
                    </Link>
                </nav>
            </div>
        </header>
    );
}
