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
        <aside aria-labelledby="red-flag-reminder-title" className="rounded-[var(--radius-card)] border border-danger/20 bg-surface-raised p-6 sm:p-7">
            <div className="grid gap-6 md:grid-cols-2 md:items-start">
                <div className="flex items-start gap-4">
                    <span className="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-danger-soft text-danger">
                        <AlertTriangle aria-hidden="true" className="size-5" />
                    </span>
                    <div>
                        <h2 id="red-flag-reminder-title" className="font-display text-lg font-semibold text-ink">
                            {t('journey.red_flag.title')}
                        </h2>
                        <p className="mt-1.5 text-ink-muted">{t('journey.red_flag.body')}</p>
                        {concernUrl !== null ? (
                            <Link
                                href={concernUrl}
                                className="mt-4 inline-flex min-h-11 items-center gap-2 font-semibold text-danger underline-offset-4 hover:underline"
                            >
                                <span>{t('journey.red_flag.check_concern')}</span>
                                <ArrowRight aria-hidden="true" className="size-4 rtl:-scale-x-100" />
                            </Link>
                        ) : null}
                    </div>
                </div>
                <EmergencyNotice />
            </div>
        </aside>
    );
}
