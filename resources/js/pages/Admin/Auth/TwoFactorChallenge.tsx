import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

export default function TwoFactorChallenge({ submitUrl }: { submitUrl: string }) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({
        code: '',
        recovery_code: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    return (
        <AdminLayout>
            <section
                aria-labelledby="two-factor-challenge-title"
                className="mx-auto max-w-md rounded-xl border border-line bg-surface-raised p-6"
            >
                <h1 id="two-factor-challenge-title" className="text-2xl font-semibold text-ink">
                    {t('admin.two_factor.challenge_title')}
                </h1>
                <p className="mt-2 text-sm text-ink-muted">{t('admin.two_factor.challenge_body')}</p>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div>
                        <label htmlFor="challenge-code" className="block text-sm font-medium text-ink">
                            {t('admin.two_factor.code_label')}
                        </label>
                        <input
                            id="challenge-code"
                            type="text"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            value={data.code}
                            onChange={(event) => setData('code', event.target.value)}
                            className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 font-mono text-ink"
                        />
                        {errors.code ? <p className="mt-1 text-xs text-danger">{errors.code}</p> : null}
                    </div>

                    <div>
                        <label htmlFor="recovery-code" className="block text-sm font-medium text-ink">
                            {t('admin.two_factor.recovery_label')}
                        </label>
                        <input
                            id="recovery-code"
                            type="text"
                            value={data.recovery_code}
                            onChange={(event) => setData('recovery_code', event.target.value)}
                            className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 font-mono text-ink"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex min-h-10 w-full items-center justify-center rounded-md bg-brand px-5 py-2 text-sm font-semibold text-brand-ink disabled:opacity-50"
                    >
                        {t('admin.two_factor.verify_button')}
                    </button>
                </form>
            </section>
        </AdminLayout>
    );
}
