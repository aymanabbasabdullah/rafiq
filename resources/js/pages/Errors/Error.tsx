import { Link } from '@inertiajs/react';
import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';

type ErrorStatus = 403 | 404 | 419 | 429 | 500 | 503;

/**
 * Bilingual error page (FR-101). The 503 page carries the general safety message (SAFE-009).
 */
export default function Error({ status }: { status: ErrorStatus }) {
    const t = useT();
    const showsSafetyMessage = status === 503 || status === 500;

    return (
        <PublicLayout>
            <section className="space-y-4">
                <p className="text-sm font-semibold text-ink-muted">
                    <bdi dir="ltr">{status}</bdi>
                </p>
                <h1 className="text-2xl font-semibold text-ink">{t(`errors.${status}.title`)}</h1>
                <p className="max-w-prose text-ink-muted">{t(`errors.${status}.body`)}</p>
                <div className="flex flex-wrap gap-3">
                    {status === 419 ? (
                        <button
                            type="button"
                            onClick={() => window.location.reload()}
                            className="rounded-md bg-brand px-4 py-2 font-medium text-brand-ink"
                        >
                            {t('errors.reload')}
                        </button>
                    ) : null}
                    <Link href="/" className="rounded-md border border-line px-4 py-2 font-medium text-ink">
                        {t('errors.back_home')}
                    </Link>
                </div>
            </section>
            {showsSafetyMessage ? (
                <div className="mt-8">
                    <EmergencyNotice />
                </div>
            ) : null}
        </PublicLayout>
    );
}
