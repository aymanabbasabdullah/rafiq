import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type ForgotPasswordProps = {
    status: string | null;
    submitUrl: string;
    loginUrl: string;
};

export default function ForgotPassword({ status, submitUrl, loginUrl }: ForgotPasswordProps) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    return (
        <AdminLayout>
            <section
                aria-labelledby="forgot-password-title"
                className="mx-auto max-w-md rounded-xl border border-line bg-surface-raised p-6"
            >
                <h1 id="forgot-password-title" className="text-2xl font-semibold text-ink">
                    {t('admin.auth.forgot_title')}
                </h1>
                <p className="mt-2 text-sm text-ink-muted">{t('admin.auth.forgot_body')}</p>

                {status ? <p className="mt-4 rounded-md bg-brand/10 px-3 py-2 text-sm text-brand">{status}</p> : null}

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div>
                        <label htmlFor="email" className="block text-sm font-medium text-ink">
                            {t('admin.auth.email')}
                        </label>
                        <input
                            id="email"
                            type="email"
                            required
                            value={data.email}
                            onChange={(event) => setData('email', event.target.value)}
                            className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-ink"
                        />
                        {errors.email ? <p className="mt-1 text-xs text-danger">{errors.email}</p> : null}
                    </div>

                    <div className="flex items-center justify-between pt-2">
                        <Link href={loginUrl} className="text-sm text-brand hover:underline">
                            {t('admin.auth.back_to_login')}
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex min-h-10 items-center rounded-md bg-brand px-5 py-2 text-sm font-semibold text-brand-ink disabled:opacity-50"
                        >
                            {t('admin.auth.send_reset_link')}
                        </button>
                    </div>
                </form>
            </section>
        </AdminLayout>
    );
}
