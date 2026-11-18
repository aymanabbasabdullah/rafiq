import { usePage } from '@inertiajs/react';
import { Phone } from 'lucide-react';
import { useT } from '@/lib/i18n';

/**
 * The general safety message (SAFE-009) with the configured emergency number, if any (OQ-008).
 */
export function EmergencyNotice() {
    const { emergency } = usePage().props;
    const t = useT();

    return (
        <div className="rounded-2xl border-s-4 border-danger bg-danger-soft p-4 text-sm text-ink">
            <p>{t('safety.general')}</p>
            <p className="mt-3 font-semibold text-danger">
                {emergency.number !== null ? (
                    <a href={`tel:${emergency.number}`} className="inline-flex items-center gap-2 underline underline-offset-4">
                        <Phone aria-hidden="true" className="size-4" />
                        {t('emergency.call_number')} <bdi dir="ltr">{emergency.number}</bdi>
                    </a>
                ) : (
                    t('emergency.call_local')
                )}
            </p>
        </div>
    );
}
