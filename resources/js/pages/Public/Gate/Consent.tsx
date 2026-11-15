import { Link, router, usePage } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { useState } from 'react';
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
            <section aria-labelledby="consent-title" className="mx-auto max-w-2xl py-6" lang={locale.current}>
                <h1 id="consent-title" className="text-3xl font-semibold text-ink">
                    {t(`gate.consent.title.${journey}`)}
                </h1>
                <p className="mt-4 text-ink-muted">{t('gate.consent.intro')}</p>
                <ul className="mt-6 space-y-3 rounded-xl border border-line bg-surface-raised p-5">
                    {POINTS.map((point) => (
                        <li key={point} className="flex items-start gap-3 text-ink">
                            <ShieldCheck aria-hidden="true" className="mt-1 size-4 shrink-0 text-brand" />
                            <span>{t(`gate.consent.${point}`)}</span>
                        </li>
                    ))}
                </ul>
                <p className="mt-3 text-sm">
                    <Link href={links.privacy} className="text-brand underline underline-offset-4">
                        {t('gate.consent.read_more')}
                    </Link>
                </p>

                <label className="mt-6 flex cursor-pointer items-start gap-3 rounded-lg border border-line bg-surface-raised p-4 text-ink">
                    <input
                        type="checkbox"
                        checked={accepted}
                        onChange={(event) => setAccepted(event.target.checked)}
                        className="mt-1 size-5 shrink-0 accent-brand"
                    />
                    <span>{t('gate.consent.checkbox')}</span>
                </label>

                <div className="mt-6 flex flex-wrap gap-3">
                    <button
                        type="button"
                        onClick={start}
                        disabled={!accepted || submitting}
                        className="inline-flex min-h-11 items-center rounded-md bg-brand px-6 py-3 font-semibold text-brand-ink disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {t('gate.consent.start')}
                    </button>
                    <Link
                        href={declineUrl}
                        className="inline-flex min-h-11 items-center rounded-md border border-line bg-surface-raised px-6 py-3 font-medium text-ink"
                    >
                        {t('gate.consent.decline')}
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
