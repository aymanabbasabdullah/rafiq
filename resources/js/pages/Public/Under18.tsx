import { Link } from '@inertiajs/react';
import { HeartHandshake } from 'lucide-react';
import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';

/**
 * Safe page for visitors under 18 (FR-023, CR-001). No assessment or AI links.
 * Wording is a draft pending medical review (RQ-10).
 */
export default function Under18({ ageGateUrl }: { ageGateUrl: string }) {
    const t = useT();

    return (
        <PublicLayout>
            <section aria-labelledby="under18-title" className="mx-auto max-w-2xl py-6">
                <HeartHandshake aria-hidden="true" className="size-10 text-brand" />
                <h1 id="under18-title" className="mt-4 text-3xl font-semibold text-ink">
                    {t('under18.title')}
                </h1>
                <p className="mt-4 text-lg text-ink">{t('under18.body')}</p>
                <p className="mt-4 text-lg text-ink">{t('under18.talk')}</p>
                <div className="mt-8">
                    <EmergencyNotice />
                </div>
                <p className="mt-8 text-sm text-ink-muted">
                    {t('under18.mistake')}{' '}
                    <Link href={ageGateUrl} className="font-medium text-brand underline underline-offset-4">
                        {t('under18.change_answer')}
                    </Link>
                </p>
            </section>
        </PublicLayout>
    );
}
