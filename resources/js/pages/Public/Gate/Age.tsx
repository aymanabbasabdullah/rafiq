import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
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

    const choiceClass =
        'inline-flex min-h-14 w-full items-center justify-center rounded-lg border-2 border-brand px-5 py-3 text-lg font-semibold text-brand hover:bg-brand hover:text-brand-ink disabled:opacity-60';

    return (
        <PublicLayout>
            <section aria-labelledby="age-question" className="mx-auto max-w-xl py-8 text-center">
                <p className="text-sm font-semibold text-brand">{t('gate.age.title')}</p>
                <h1 id="age-question" className="mt-3 text-3xl font-semibold text-ink">
                    {t('gate.age.question')}
                </h1>
                <p className="mt-4 text-ink-muted">{t('gate.age.why')}</p>
                <div className="mt-8 grid gap-3 sm:grid-cols-2">
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
