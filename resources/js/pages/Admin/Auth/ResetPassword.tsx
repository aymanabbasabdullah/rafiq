import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type ResetPasswordProps = {
    token: string;
    email: string;
    submitUrl: string;
};

export default function ResetPassword({ token, email, submitUrl }: ResetPasswordProps) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    return (
        <AdminLayout>
            <section
                aria-labelledby="reset-password-title"
                className="mx-auto max-w-md rounded-xl border border-line bg-surface-raised p-6"
            >
                <h1 id="reset-password-title" className="text-2xl font-semibold text-ink">
                    {t('admin.auth.reset_title')}
                </h1>

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

                    <div>
                        <label htmlFor="password" className="block text-sm font-medium text-ink">
                            {t('admin.auth.password')}
                        </label>
                        <input
                            id="password"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-ink"
                        />
                        {errors.password ? <p className="mt-1 text-xs text-danger">{errors.password}</p> : null}
                    </div>

                    <div>
                        <label htmlFor="password_confirmation" className="block text-sm font-medium text-ink">
                            {t('admin.auth.password_confirm')}
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            required
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-ink"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex min-h-10 w-full items-center justify-center rounded-md bg-brand px-5 py-2 text-sm font-semibold text-brand-ink disabled:opacity-50"
                    >
                        {t('admin.auth.reset_button')}
                    </button>
                </form>
            </section>
        </AdminLayout>
    );
}
