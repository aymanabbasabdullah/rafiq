import { useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Field, TextInput } from '@/components/ui/Field';
import { AdminAuthLayout } from '@/layouts/AdminAuthLayout';
import { useT } from '@/lib/i18n';

export default function TwoFactorChallenge({ submitUrl }: { submitUrl: string }) {
    const t = useT();
    const [useRecovery, setUseRecovery] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ code: '', recovery_code: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    const toggleMode = () => {
        reset();
        setUseRecovery((current) => !current);
    };

    return (
        <AdminAuthLayout
            title={t('admin.two_factor.challenge_title')}
            subtitle={t('admin.two_factor.challenge_body')}
            icon={ShieldCheck}
        >
            <form onSubmit={submit} className="space-y-5" noValidate>
                {useRecovery ? (
                    <Field
                        id="recovery-code"
                        label={t('admin.two_factor.recovery_label')}
                        error={errors.recovery_code ?? errors.code}
                    >
                        <TextInput
                            id="recovery-code"
                            dir="ltr"
                            autoComplete="off"
                            autoFocus
                            className="text-center font-mono tracking-widest"
                            value={data.recovery_code}
                            onChange={(event) => setData('recovery_code', event.target.value)}
                        />
                    </Field>
                ) : (
                    <Field id="challenge-code" label={t('admin.two_factor.code_label')} error={errors.code}>
                        <TextInput
                            id="challenge-code"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            autoFocus
                            dir="ltr"
                            maxLength={6}
                            placeholder="000000"
                            className="text-center font-mono text-lg tracking-[0.5em]"
                            value={data.code}
                            invalid={errors.code !== undefined}
                            onChange={(event) => setData('code', event.target.value.replace(/\D/g, ''))}
                        />
                    </Field>
                )}

                <Button type="submit" disabled={processing} className="w-full">
                    {t('admin.two_factor.verify_button')}
                </Button>

                <p className="text-center text-sm">
                    <button
                        type="button"
                        onClick={toggleMode}
                        className="cursor-pointer font-medium text-brand underline-offset-4 hover:underline"
                    >
                        {useRecovery ? t('admin.two_factor.use_app_code') : t('admin.two_factor.use_recovery_code')}
                    </button>
                </p>
            </form>
        </AdminAuthLayout>
    );
}
