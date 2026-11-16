import { Link } from '@inertiajs/react';
import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { buttonClasses } from '@/components/ui/Button';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';

type ErrorStatus = 403 | 404 | 419 | 429 | 500 | 503;

/**
 * Bilingual error page (FR-101). The 500/503 pages carry the general safety message (SAFE-009).
 */
export default function Error({ status }: { status: ErrorStatus }) {
    const t = useT();
    const showsSafetyMessage = status === 503 || status === 500;

    return (
        <PublicLayout>
            <section aria-labelledby="error-title" className="mx-auto max-w-2xl py-6 text-center md:py-12">
                <p aria-hidden="true" className="font-display text-7xl font-semibold text-brand/20 md:text-8xl">
                    <bdi dir="ltr">{status}</bdi>
                </p>
                <h1 id="error-title" className="mt-4 text-3xl text-ink md:text-4xl">
                    {t(`errors.${status}.title`)}
                </h1>
                <p className="mx-auto mt-4 max-w-prose text-lg text-ink-muted">{t(`errors.${status}.body`)}</p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    {status === 419 ? (
                        <button type="button" onClick={() => window.location.reload()} className={buttonClasses('primary')}>
                            {t('errors.reload')}
                        </button>
                    ) : null}
                    <Link href="/" className={buttonClasses(status === 419 ? 'secondary' : 'primary')}>
                        {t('errors.back_home')}
                    </Link>
                </div>
                {showsSafetyMessage ? (
                    <div className="mt-12 text-start">
                        <EmergencyNotice />
                    </div>
                ) : null}
            </section>
        </PublicLayout>
    );
}
