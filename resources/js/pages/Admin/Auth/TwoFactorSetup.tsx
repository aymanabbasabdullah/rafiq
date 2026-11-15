import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type TwoFactorSetupProps = {
    secret: string;
    provisioningUri: string;
    submitUrl: string;
};

export default function TwoFactorSetup({ secret, provisioningUri, submitUrl }: TwoFactorSetupProps) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({
        code: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    return (
        <AdminLayout>
            <section
                aria-labelledby="two-factor-setup-title"
                className="mx-auto max-w-lg rounded-xl border border-line bg-surface-raised p-6"
            >
                <h1 id="two-factor-setup-title" className="text-2xl font-semibold text-ink">
                    {t('admin.two_factor.setup_title')}
                </h1>
                <p className="mt-2 text-sm text-ink-muted">{t('admin.two_factor.setup_body')}</p>

                <div className="mt-5 space-y-3 rounded-lg border border-line bg-surface p-4 text-sm">
                    <div>
                        <p className="font-medium text-ink">{t('admin.two_factor.secret_label')}</p>
                        <p className="mt-1 font-mono text-base font-semibold text-brand">
                            <bdi dir="ltr">{secret}</bdi>
                        </p>
                    </div>
                    <div>
                        <p className="font-medium text-ink">{t('admin.two_factor.uri_label')}</p>
                        <p className="mt-1 break-all font-mono text-xs text-ink-muted">
                            <bdi dir="ltr">{provisioningUri}</bdi>
                        </p>
                    </div>
                </div>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div>
                        <label htmlFor="totp-code" className="block text-sm font-medium text-ink">
                            {t('admin.two_factor.code_label')}
                        </label>
                        <input
                            id="totp-code"
                            type="text"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            required
                            value={data.code}
                            onChange={(event) => setData('code', event.target.value)}
                            className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 font-mono text-ink"
                        />
                        {errors.code ? <p className="mt-1 text-xs text-danger">{errors.code}</p> : null}
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex min-h-10 w-full items-center justify-center rounded-md bg-brand px-5 py-2 text-sm font-semibold text-brand-ink disabled:opacity-50"
                    >
                        {t('admin.two_factor.enable_button')}
                    </button>
                </form>
            </section>
        </AdminLayout>
    );
}
