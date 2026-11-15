import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

export type Replacements = Record<string, string | number>;

/**
 * Looks up an interface string and fills `:placeholder` values, Laravel style.
 * A missing key returns the key itself so it is visible during development (L10N-010).
 */
export function translate(translations: Record<string, string>, key: string, replacements: Replacements = {}): string {
    let line = translations[key] ?? key;

    for (const [name, value] of Object.entries(replacements)) {
        line = line.replaceAll(`:${name}`, String(value));
    }

    return line;
}

/**
 * Returns a `t()` function bound to the current page's translations.
 */
export function useT(): (key: string, replacements?: Replacements) => string {
    const { translations } = usePage().props;

    return useCallback((key: string, replacements?: Replacements) => translate(translations, key, replacements), [translations]);
}
