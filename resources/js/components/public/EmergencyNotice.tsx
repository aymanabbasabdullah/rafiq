import { usePage } from '@inertiajs/react';
import { useT } from '@/lib/i18n';

/**
 * The general safety message (SAFE-009) with the configured emergency number, if any (OQ-008).
 */
export function EmergencyNotice() {
    const { emergency } = usePage().props;
    const t = useT();

    return (
        <div className="rounded-lg border border-danger/30 bg-surface-raised p-4 text-sm text-ink">
            <p>{t('safety.general')}</p>
            <p className="mt-2 font-semibold">
                {emergency.number !== null ? (
                    <a href={`tel:${emergency.number}`} className="text-danger underline">
                        {t('emergency.call_number')} <bdi dir="ltr">{emergency.number}</bdi>
                    </a>
                ) : (
                    t('emergency.call_local')
                )}
            </p>
        </div>
    );
}
