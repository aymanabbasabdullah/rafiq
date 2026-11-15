import { EmergencyNotice } from '@/components/public/EmergencyNotice';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';

/**
 * Placeholder landing page; replaced by the full landing page in Module-01.
 */
export default function Landing() {
    const t = useT();

    return (
        <PublicLayout>
            <section className="space-y-4">
                <h1 className="text-3xl font-semibold text-ink">{t('landing.placeholder.title')}</h1>
                <p className="max-w-prose text-ink-muted">{t('landing.placeholder.body')}</p>
            </section>
            <div className="mt-8">
                <EmergencyNotice />
            </div>
        </PublicLayout>
    );
}
