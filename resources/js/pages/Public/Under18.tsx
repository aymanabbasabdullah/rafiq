import { Link } from '@inertiajs/react';
import { HeartHandshake } from 'lucide-react';
import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { PageIntro } from '@/components/public/PageIntro';
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
            <section aria-labelledby="under18-title" className="mx-auto max-w-2xl">
                <PageIntro id="under18-title" title={t('under18.title')} icon={HeartHandshake} />
                <div className="mt-6 space-y-4 text-lg text-ink">
                    <p>{t('under18.body')}</p>
                    <p>{t('under18.talk')}</p>
                </div>
                <div className="mt-10">
                    <EmergencyNotice />
                </div>
                <p className="mt-10 border-t border-line pt-6 text-sm text-ink-muted">
                    {t('under18.mistake')}{' '}
                    <Link href={ageGateUrl} className="font-medium text-brand underline underline-offset-4">
                        {t('under18.change_answer')}
                    </Link>
                </p>
            </section>
        </PublicLayout>
    );
}
