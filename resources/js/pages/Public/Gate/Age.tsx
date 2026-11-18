import { router, usePage } from '@inertiajs/react';
import { UserRoundCheck } from 'lucide-react';
import { useState } from 'react';
import { PageIntro } from '@/components/public/PageIntro';
import { PublicLayout } from '@/layouts/PublicLayout';
import { useT } from '@/lib/i18n';

/**
 * One-tap age confirmation (FR-020). Two equal choices — no date of birth, no dark pattern.
 */
export default function Age({ intended }: { intended: string | null }) {
    const { links } = usePage().props;
    const t = useT();
    const [submitting, setSubmitting] = useState(false);

    const answer = (isAdult: boolean) => {
        setSubmitting(true);
        router.post(links.ageGate, { is_adult: isAdult, intended }, { onFinish: () => setSubmitting(false) });
    };

    // Both answers share one style so neither is nudged (no dark pattern).
    const choiceClass =
        'inline-flex min-h-14 w-full cursor-pointer items-center justify-center rounded-full border-2 border-brand/30 bg-surface-raised px-6 text-lg font-semibold text-brand-strong transition-colors hover:border-brand hover:bg-brand-soft disabled:cursor-not-allowed disabled:opacity-50';

    return (
        <PublicLayout>
            <section aria-labelledby="age-question" className="mx-auto max-w-xl rounded-[var(--radius-panel)] bg-surface-raised p-8 shadow-card sm:p-12 md:my-6">
                <PageIntro id="age-question" eyebrow={t('gate.age.title')} title={t('gate.age.question')} lead={t('gate.age.why')} icon={UserRoundCheck} centered />
                <div className="mt-10 grid gap-3 sm:grid-cols-2">
                    <button type="button" disabled={submitting} onClick={() => answer(true)} className={choiceClass}>
                        {t('gate.age.yes')}
                    </button>
                    <button type="button" disabled={submitting} onClick={() => answer(false)} className={choiceClass}>
                        {t('gate.age.no')}
                    </button>
                </div>
            </section>
        </PublicLayout>
    );
}
