import { Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight } from 'lucide-react';
import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { useT } from '@/lib/i18n';

/**
 * Permanent warning-signs reminder for J1 (Learn) and J4 (Doctor Prep), linking to
 * emergency guidance and to the J2 safety screening journey (M02-FR-06, FR-004).
 */
export function RedFlagReminder({ concernUrl }: { concernUrl: string | null }) {
    const t = useT();

    return (
        <aside aria-labelledby="red-flag-reminder-title" className="mt-8 rounded-xl border border-danger/30 bg-danger/5 p-5">
            <div className="flex items-start gap-3">
                <AlertTriangle aria-hidden="true" className="mt-0.5 size-5 shrink-0 text-danger" />
                <div className="space-y-3">
                    <h2 id="red-flag-reminder-title" className="font-semibold text-ink">
                        {t('journey.red_flag.title')}
                    </h2>
                    <p className="text-sm text-ink">{t('journey.red_flag.body')}</p>
                    <EmergencyNotice />
                    {concernUrl !== null ? (
                        <div className="pt-1">
                            <Link
                                href={concernUrl}
                                className="inline-flex min-h-10 items-center gap-2 rounded-md border border-danger/40 bg-surface-raised px-4 py-2 text-sm font-semibold text-danger transition hover:bg-danger/10"
                            >
                                <span>{t('journey.red_flag.check_concern')}</span>
                                <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                            </Link>
                        </div>
                    ) : null}
                </div>
            </div>
        </aside>
    );
}
