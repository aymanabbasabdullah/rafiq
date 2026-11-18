import { Link, useForm } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Field, TextInput } from '@/components/ui/Field';
import { AdminAuthLayout } from '@/layouts/AdminAuthLayout';
import { useT } from '@/lib/i18n';

type LoginProps = {
    status: string | null;
    loginUrl: string;
    forgotPasswordUrl: string;
};

export default function Login({ status, loginUrl, forgotPasswordUrl }: LoginProps) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({ email: '', password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(loginUrl);
    };

    return (
        <AdminAuthLayout title={t('admin.auth.login_title')} subtitle={t('admin.auth.login_subtitle')} icon={LogIn}>
            {status ? <p className="mb-5 rounded-xl bg-brand-soft px-4 py-3 text-sm text-brand-strong">{status}</p> : null}

            <form onSubmit={submit} className="space-y-5" noValidate>
                <Field id="email" label={t('admin.auth.email')} error={errors.email}>
                    <TextInput
                        id="email"
                        type="email"
                        required
                        dir="ltr"
                        autoComplete="username"
                        autoFocus
                        value={data.email}
                        invalid={errors.email !== undefined}
                        onChange={(event) => setData('email', event.target.value)}
                    />
                </Field>

                <Field id="password" label={t('admin.auth.password')} error={errors.password}>
                    <TextInput
                        id="password"
                        type="password"
                        required
                        dir="ltr"
                        autoComplete="current-password"
                        value={data.password}
                        invalid={errors.password !== undefined}
                        onChange={(event) => setData('password', event.target.value)}
                    />
                </Field>

                <Button type="submit" disabled={processing} className="w-full">
                    {t('admin.auth.login_button')}
                </Button>

                <p className="text-center text-sm">
                    <Link href={forgotPasswordUrl} className="font-medium text-brand underline-offset-4 hover:underline">
                        {t('admin.auth.forgot_link')}
                    </Link>
                </p>
            </form>
        </AdminAuthLayout>
    );
}
