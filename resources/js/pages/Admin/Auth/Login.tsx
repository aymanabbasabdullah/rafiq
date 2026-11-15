import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';

type LoginProps = {
    status: string | null;
    loginUrl: string;
    forgotPasswordUrl: string;
};

export default function Login({ status, loginUrl, forgotPasswordUrl }: LoginProps) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(loginUrl);
    };

    return (
        <AdminLayout>
            <section
                aria-labelledby="admin-login-title"
                className="mx-auto max-w-md rounded-xl border border-line bg-surface-raised p-6"
            >
                <h1 id="admin-login-title" className="text-2xl font-semibold text-ink">
                    {t('admin.auth.login_title')}
                </h1>
                <p className="mt-2 text-sm text-ink-muted">{t('admin.auth.login_subtitle')}</p>

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
                            autoComplete="username"
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
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            className="mt-1 w-full rounded-md border border-line bg-surface px-3 py-2 text-ink"
                        />
                        {errors.password ? <p className="mt-1 text-xs text-danger">{errors.password}</p> : null}
                    </div>

                    <div className="flex items-center justify-between pt-2">
                        <Link href={forgotPasswordUrl} className="text-sm text-brand hover:underline">
                            {t('admin.auth.forgot_link')}
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex min-h-10 items-center rounded-md bg-brand px-5 py-2 text-sm font-semibold text-brand-ink disabled:opacity-50"
                        >
                            {t('admin.auth.login_button')}
                        </button>
                    </div>
                </form>
            </section>
        </AdminLayout>
    );
}
