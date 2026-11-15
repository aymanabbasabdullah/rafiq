import { useForm } from '@inertiajs/react';
import { LockKeyhole } from 'lucide-react';
import type { FormEvent } from 'react';
import { Button } from '@/components/ui/Button';
import { Field, TextInput } from '@/components/ui/Field';
import { AdminAuthLayout } from '@/layouts/AdminAuthLayout';
import { useT } from '@/lib/i18n';

export default function ConfirmPassword({ submitUrl }: { submitUrl: string }) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({ password: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    return (
        <AdminAuthLayout title={t('admin.auth.confirm_title')} subtitle={t('admin.auth.confirm_body')} icon={LockKeyhole}>
            <form onSubmit={submit} className="space-y-5" noValidate>
                <Field id="password" label={t('admin.auth.password')} error={errors.password}>
                    <TextInput
                        id="password"
                        type="password"
                        required
                        dir="ltr"
                        autoFocus
                        autoComplete="current-password"
                        value={data.password}
                        invalid={errors.password !== undefined}
                        onChange={(event) => setData('password', event.target.value)}
                    />
                </Field>
                <Button type="submit" disabled={processing} className="w-full">
                    {t('admin.auth.confirm_button')}
                </Button>
            </form>
        </AdminAuthLayout>
    );
}
