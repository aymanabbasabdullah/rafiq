import { Link, router, usePage } from '@inertiajs/react';
import { ArrowRight, LockKeyhole, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { PageIntro } from '@/components/public/PageIntro';
import { buttonClasses } from '@/components/ui/Button';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';

type ConsentProps = {
    journey: 'concern' | 'conception';
    submitUrl: string;
    declineUrl: string;
};

const POINTS = ['point1', 'point2', 'point3', 'point4', 'point5', 'point6'];

/**
 * Privacy notice and explicit consent before health questions (FR-021, PRIV-012, PRIV-013).
 * The checkbox starts unticked and Start stays disabled until it is ticked.
 */
export default function Consent({ journey, submitUrl, declineUrl }: ConsentProps) {
    const { links, locale } = usePage().props;
    const t = useT();
    const [accepted, setAccepted] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const start = () => {
        setSubmitting(true);
        router.post(submitUrl, { journey, accepted }, { onFinish: () => setSubmitting(false) });
    };

    return (
        <PublicLayout>
            <section aria-labelledby="consent-title" className="mx-auto max-w-3xl" lang={locale.current}>
                <PageIntro id="consent-title" title={t(`gate.consent.title.${journey}`)} lead={t('gate.consent.intro')} icon={LockKeyhole} />

                <ul className="mt-10 grid gap-x-8 gap-y-5 rounded-[var(--radius-panel)] bg-surface-raised p-7 shadow-card sm:grid-cols-2 sm:p-9">
                    {POINTS.map((point) => (
                        <li key={point} className="flex items-start gap-3 text-ink">
                            <ShieldCheck aria-hidden="true" className="mt-1 size-5 shrink-0 text-brand" />
                            <span>{t(`gate.consent.${point}`)}</span>
                        </li>
                    ))}
                </ul>
                <p className="mt-4 text-sm">
                    <Link href={links.privacy} className="font-medium text-brand underline underline-offset-4 hover:text-brand-strong">
                        {t('gate.consent.read_more')}
                    </Link>
                </p>

                <label className="mt-8 flex cursor-pointer items-start gap-4 rounded-2xl border-2 border-line bg-surface-raised p-5 text-ink transition-colors has-[:checked]:border-brand has-[:checked]:bg-brand-mist has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand">
                    <input
                        type="checkbox"
                        checked={accepted}
                        onChange={(event) => setAccepted(event.target.checked)}
                        className="mt-0.5 size-5 shrink-0 cursor-pointer accent-brand"
                    />
                    <span className="font-medium">{t('gate.consent.checkbox')}</span>
                </label>

                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                    <button type="button" onClick={start} disabled={!accepted || submitting} className={buttonClasses('primary', 'lg')}>
                        {t('gate.consent.start')}
                        <ArrowRight aria-hidden="true" className="size-5 rtl:-scale-x-100" />
                    </button>
                    <Link href={declineUrl} className={buttonClasses('secondary', 'lg')}>
                        {t('gate.consent.decline')}
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
