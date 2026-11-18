import { usePage } from '@inertiajs/react';
import { CalendarClock, FlaskConical, ShieldCheck } from 'lucide-react';
import { useT } from '@/lib/i18n';

/**
 * Shows when the article was medically reviewed and when it is next due (FR-071).
 * In demo mode it says plainly that the review is a demonstration, not a real medical approval (BR-01-2).
 */
export function ReviewStamp({
    approvedAt,
    reviewDueAt,
    version,
}: {
    approvedAt: string;
    reviewDueAt: string | null;
    version: number;
}) {
    const t = useT();
    const { demoMode } = usePage().props;

    return (
        <div
            className={`flex flex-wrap items-center gap-x-5 gap-y-2 rounded-2xl border px-4 py-3 text-sm ${demoMode ? 'border-warning/30 bg-warning-soft/50' : 'border-success/25 bg-success-soft/50'}`}
        >
            <span className={`inline-flex items-center gap-2 font-semibold ${demoMode ? 'text-warning' : 'text-success'}`}>
                {demoMode ? (
                    <FlaskConical aria-hidden="true" className="size-4" />
                ) : (
                    <ShieldCheck aria-hidden="true" className="size-4" />
                )}
                {demoMode ? t('learn.review_demo') : t('learn.review_done')}
            </span>
            <span className="text-ink-muted">
                {t('learn.reviewed_on')}{' '}
                <bdi dir="ltr">
                    <time dateTime={approvedAt}>{approvedAt}</time>
                </bdi>
            </span>
            {reviewDueAt !== null ? (
                <span className="inline-flex items-center gap-1.5 text-ink-muted">
                    <CalendarClock aria-hidden="true" className="size-4" />
                    {t('learn.next_review')}{' '}
                    <bdi dir="ltr">
                        <time dateTime={reviewDueAt}>{reviewDueAt}</time>
                    </bdi>
                </span>
            ) : null}
            <span className="text-ink-subtle">{t('learn.version', { version })}</span>
        </div>
    );
}
