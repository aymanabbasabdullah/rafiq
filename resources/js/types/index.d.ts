export type LocaleCode = 'ar' | 'en';

export type TextDirection = 'rtl' | 'ltr';

/**
 * Props shared with every page by HandleInertiaRequests. They contain no personal data.
 */
export interface SharedProps {
    [key: string]: unknown;
    locale: {
        current: LocaleCode;
        direction: TextDirection;
        alternate: {
            code: LocaleCode;
            name: string;
            url: string;
        };
    };
    translations: Record<string, string>;
    demoMode: boolean;
    emergency: {
        number: string | null;
    };
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}
