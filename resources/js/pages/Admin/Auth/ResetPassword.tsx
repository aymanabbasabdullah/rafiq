import { useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Field, TextInput } from '@/components/ui/Field';
import { AdminAuthLayout } from '@/layouts/AdminAuthLayout';
import { useT } from '@/lib/i18n';

type ResetPasswordProps = {
    token: string;
    email: string;
    submitUrl: string;
};

export default function ResetPassword({ token, email, submitUrl }: ResetPasswordProps) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({ token, email, password: '', password_confirmation: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    return (
        <AdminAuthLayout title={t('admin.auth.reset_title')} icon={KeyRound}>
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
                <Field
                    id="password"
                    label={t('admin.auth.password')}
                    help={t('admin.auth.password_rules')}
                    error={errors.password}
                >
                    <TextInput
                        id="password"
                        type="password"
                        required
                        dir="ltr"
                        autoComplete="new-password"
                        value={data.password}
                        invalid={errors.password !== undefined}
                        onChange={(event) => setData('password', event.target.value)}
                    />
                </Field>
                <Field id="password_confirmation" label={t('admin.auth.password_confirm')}>
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        required
                        dir="ltr"
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(event) => setData('password_confirmation', event.target.value)}
                    />
                </Field>
                <Button type="submit" disabled={processing} className="w-full">
                    {t('admin.auth.reset_button')}
                </Button>
            </form>
        </AdminAuthLayout>
    );
}
