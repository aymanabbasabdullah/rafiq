import { usePage } from '@inertiajs/react';
import { useEffect, type ReactNode } from 'react';
import type { LocaleCode, TextDirection } from '@/types';

/**
 * Keeps `<html lang dir>` in sync with the current page after client-side visits (L10N-004).
 * The server sets both attributes on the first load.
 */
export function DirectionProvider({ children }: { children: ReactNode }) {
    const { current, direction } = usePage().props.locale;

    useEffect(() => {
        document.documentElement.lang = current;
        document.documentElement.dir = direction;
    }, [current, direction]);

    return <>{children}</>;
}

export function useDirection(): { locale: LocaleCode; dir: TextDirection; isRtl: boolean } {
    const { locale } = usePage().props;

    return { locale: locale.current, dir: locale.direction, isRtl: locale.direction === 'rtl' };
}
