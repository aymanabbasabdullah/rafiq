import { Link, useForm } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Field, TextInput } from '@/components/ui/Field';
import { AdminAuthLayout } from '@/layouts/AdminAuthLayout';
import { useT } from '@/lib/i18n';

type ForgotPasswordProps = {
    status: string | null;
    submitUrl: string;
    loginUrl: string;
};

export default function ForgotPassword({ status, submitUrl, loginUrl }: ForgotPasswordProps) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    return (
        <AdminAuthLayout title={t('admin.auth.forgot_title')} subtitle={t('admin.auth.forgot_body')} icon={Mail}>
            {status ? <p className="mb-5 rounded-xl bg-brand-soft px-4 py-3 text-sm text-brand-strong">{status}</p> : null}
            <form onSubmit={submit} className="space-y-5" noValidate>
                <Field id="email" label={t('admin.auth.email')} error={errors.email}>
                    <TextInput
                        id="email"
                        type="email"
                        required
                        dir="ltr"
                        autoComplete="username"
                        value={data.email}
                        invalid={errors.email !== undefined}
                        onChange={(event) => setData('email', event.target.value)}
                    />
                </Field>
                <Button type="submit" disabled={processing} className="w-full">
                    {t('admin.auth.send_reset_link')}
                </Button>
                <p className="text-center text-sm">
                    <Link href={loginUrl} className="font-medium text-brand underline-offset-4 hover:underline">
                        {t('admin.auth.back_to_login')}
                    </Link>
                </p>
            </form>
        </AdminAuthLayout>
    );
}
