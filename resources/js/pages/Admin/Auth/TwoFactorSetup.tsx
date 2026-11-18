import { useForm } from '@inertiajs/react';
import { Check, Copy, Smartphone } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { Button } from '@/components/ui/Button';
import { Field, TextInput } from '@/components/ui/Field';
import { AdminAuthLayout } from '@/layouts/AdminAuthLayout';
import { useT } from '@/lib/i18n';

type TwoFactorSetupProps = {
    secret: string;
    provisioningUri: string;
    submitUrl: string;
};

export default function TwoFactorSetup({ secret, provisioningUri, submitUrl }: TwoFactorSetupProps) {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({ code: '' });
    const [copied, setCopied] = useState(false);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(submitUrl);
    };

    const copySecret = async () => {
        try {
            await navigator.clipboard.writeText(secret);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    };

    return (
        <AdminAuthLayout title={t('admin.two_factor.setup_title')} subtitle={t('admin.two_factor.setup_body')} icon={Smartphone}>
            <div className="space-y-4 rounded-2xl bg-surface-sunken p-4">
                <div>
                    <p className="text-xs font-semibold text-ink-muted">{t('admin.two_factor.secret_label')}</p>
                    <div className="mt-2 flex items-center gap-2">
                        <code
                            className="flex-1 rounded-lg bg-surface-raised px-3 py-2 text-center font-mono text-sm tracking-wider break-all text-brand-strong"
                            dir="ltr"
                        >
                            {secret}
                        </code>
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={() => void copySecret()}
                            aria-label={t('admin.two_factor.copy')}
                        >
                            {copied ? (
                                <Check aria-hidden="true" className="size-4 text-success" />
                            ) : (
                                <Copy aria-hidden="true" className="size-4" />
                            )}
                        </Button>
                    </div>
                </div>
                <details className="text-xs">
                    <summary className="cursor-pointer font-semibold text-ink-muted">{t('admin.two_factor.uri_label')}</summary>
                    <p className="mt-2 font-mono break-all text-ink-subtle" dir="ltr">
                        {provisioningUri}
                    </p>
                </details>
            </div>

            <form onSubmit={submit} className="mt-6 space-y-5" noValidate>
                <Field id="totp-code" label={t('admin.two_factor.code_label')} error={errors.code}>
                    <TextInput
                        id="totp-code"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        required
                        dir="ltr"
                        maxLength={6}
                        placeholder="000000"
                        className="text-center font-mono text-lg tracking-[0.5em]"
                        value={data.code}
                        invalid={errors.code !== undefined}
                        onChange={(event) => setData('code', event.target.value.replace(/\D/g, ''))}
                    />
                </Field>
                <Button type="submit" disabled={processing} className="w-full">
                    {t('admin.two_factor.enable_button')}
                </Button>
            </form>
        </AdminAuthLayout>
    );
}
