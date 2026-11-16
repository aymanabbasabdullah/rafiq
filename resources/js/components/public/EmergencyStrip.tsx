import { usePage } from '@inertiajs/react';
import { Phone, TriangleAlert } from 'lucide-react';
import { useT } from '@/lib/i18n';

/**
 * Red-flag strip shown above the fold (FR-004). Quiet enough not to alarm, clear enough to find.
 *
 * Uses the general approved safety wording until Module-07 provides medically approved texts.
 */
export function EmergencyStrip() {
    const { emergency } = usePage().props;
    const t = useT();

    return (
        <aside className="border-b border-danger/15 bg-danger-soft" aria-label={t('footer.emergency_title')}>
            <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-2.5 text-sm sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p className="flex items-start gap-2.5 text-ink">
                    <TriangleAlert aria-hidden="true" className="mt-0.5 size-4 shrink-0 text-danger" />
                    <span>{t('landing.emergency_strip')}</span>
                </p>
                {emergency.number !== null ? (
                    <a
                        href={`tel:${emergency.number}`}
                        className="inline-flex min-h-11 shrink-0 items-center gap-2 self-start rounded-full bg-danger px-4 font-semibold text-danger-ink sm:self-auto"
                    >
                        <Phone aria-hidden="true" className="size-4" />
                        <bdi dir="ltr">{emergency.number}</bdi>
                    </a>
                ) : (
                    <p className="shrink-0 ps-6.5 font-semibold text-danger sm:ps-0">{t('emergency.call_local')}</p>
                )}
            </div>
        </aside>
    );
}
