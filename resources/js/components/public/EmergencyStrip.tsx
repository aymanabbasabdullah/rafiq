import { usePage } from '@inertiajs/react';
import { Phone, TriangleAlert } from 'lucide-react';
import { useT } from '@/lib/i18n';

/**
 * Red-flag strip shown above the fold on the landing page (FR-004).
 *
 * Uses the general approved safety wording until Module-07 provides medically approved texts.
 */
export function EmergencyStrip() {
    const { emergency } = usePage().props;
    const t = useT();

    return (
        <aside className="border-b border-danger/20 bg-danger/5" aria-label={t('footer.emergency_title')}>
            <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                <p className="flex items-start gap-2 font-medium text-ink">
                    <TriangleAlert aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-danger" />
                    <span>{t('landing.emergency_strip')}</span>
                </p>
                {emergency.number !== null ? (
                    <a
                        href={`tel:${emergency.number}`}
                        className="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-md bg-danger px-3 py-2 font-semibold text-danger-ink"
                    >
                        <Phone aria-hidden="true" className="size-4" />
                        <bdi dir="ltr">{emergency.number}</bdi>
                    </a>
                ) : (
                    <p className="shrink-0 font-semibold text-danger">{t('emergency.call_local')}</p>
                )}
            </div>
        </aside>
    );
}
