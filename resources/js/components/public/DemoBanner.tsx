import { usePage } from '@inertiajs/react';
import { useT } from '@/lib/i18n';

/**
 * Permanent warning shown on every page while demo mode runs unapproved rules (FR-053).
 */
export function DemoBanner() {
    const { demoMode } = usePage().props;
    const t = useT();

    if (!demoMode) {
        return null;
    }

    return (
        <div role="note" className="bg-notice px-4 py-2 text-center text-sm font-medium text-notice-ink">
            {t('demo.banner')}
        </div>
    );
}
