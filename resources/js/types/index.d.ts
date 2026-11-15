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
    quickExitUrl: string;
    links: {
        home: string;
        journeys: string;
        privacy: string;
        terms: string;
        limits: string;
        sources: string;
        ageGate: string;
        aiNotice: string;
        clearSession: string;
    };
    gates: {
        ageConfirmed: boolean;
        assessmentConsent: boolean;
        aiNoticeAcknowledged: boolean;
    };
    admin: {
        user: {
            id: number;
            name: string;
            email: string;
            preferredLocale: LocaleCode;
            roles: AdminRoleKey[];
            canManageUsers: boolean;
            canViewAuditLog: boolean;
        } | null;
        links: {
            dashboard: string;
            users: string;
            auditLog: string;
            logout: string;
            locale: string;
        };
    } | null;
}

export type AdminRoleKey = 'super_admin' | 'content_editor' | 'medical_reviewer';

export type JourneyKey = 'learn' | 'concern' | 'conception' | 'doctor_prep';

export type LegalPageKey = 'privacy' | 'terms' | 'limits' | 'sources';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: SharedProps;
    }
}
