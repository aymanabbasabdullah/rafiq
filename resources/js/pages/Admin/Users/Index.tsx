import { Head, router, useForm } from '@inertiajs/react';
import { KeyRound, Power, Save, ShieldCheck, ShieldOff, Trash2, UserPlus, Users } from 'lucide-react';
import { type FormEvent, useState } from 'react';
import { ConfirmDialog } from '@/components/public/ConfirmDialog';
import { Badge } from '@/components/ui/Badge';
import { Button } from '@/components/ui/Button';
import { Card } from '@/components/ui/Card';
import { EmptyState } from '@/components/ui/EmptyState';
import { Field, PillCheckbox, SelectInput, TextInput } from '@/components/ui/Field';
import { PageHeader } from '@/components/ui/PageHeader';
import { AdminLayout } from '@/layouts/AdminLayout';
import { useT } from '@/lib/i18n';
import type { AdminRoleKey, LocaleCode } from '@/types';

interface AdminUserRow {
    id: number;
    name: string;
    email: string;
    preferredLocale: LocaleCode;
    isActive: boolean;
    twoFactorEnabled: boolean;
    lastLoginAt: string | null;
    roles: AdminRoleKey[];
    credentialReference: string | null;
    credentialVerifiedAt: string | null;
    canToggleActive: boolean;
    canUpdateRoles: boolean;
    canResetTwoFactor: boolean;
    canDelete: boolean;
    deactivatedAt: string | null;
    deleteUrl: string;
    updateRolesUrl: string;
    toggleActiveUrl: string;
    resetTwoFactorUrl: string;
}

interface UsersIndexProps {
    users: AdminUserRow[];
    availableRoles: AdminRoleKey[];
    createUrl: string;
}

function initialOf(name: string): string {
    return name.replace('[SYNTHETIC]', '').trim().charAt(0).toUpperCase();
}

function UserCard({ user, availableRoles }: { user: AdminUserRow; availableRoles: AdminRoleKey[] }) {
    const t = useT();
    const [selectedRoles, setSelectedRoles] = useState<AdminRoleKey[]>(user.roles);
    const [credentialRef, setCredentialRef] = useState<string>(user.credentialReference ?? '');
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const changed =
        selectedRoles.slice().sort().join() !== user.roles.slice().sort().join() ||
        credentialRef !== (user.credentialReference ?? '');

    const toggleRole = (role: AdminRoleKey) => {
        setSelectedRoles((current) => (current.includes(role) ? current.filter((item) => item !== role) : [...current, role]));
    };

    const submitRoles = (event: FormEvent) => {
        event.preventDefault();
        router.put(
            user.updateRolesUrl,
            { roles: selectedRoles, credential_reference: selectedRoles.includes('medical_reviewer') ? credentialRef : null },
            { preserveScroll: true },
        );
    };

    return (
        <Card as="article" className={`hover-lift p-5 ${user.isActive ? '' : 'opacity-75'}`}>
            <div className="flex flex-wrap items-start gap-4">
                <span
                    className={`inline-flex size-11 shrink-0 items-center justify-center rounded-full text-base font-semibold ${
                        user.isActive ? 'bg-brand-soft text-brand' : 'bg-surface-sunken text-ink-subtle'
                    }`}
                >
                    {initialOf(user.name)}
                </span>
                <div className="min-w-0 flex-1">
                    <h3 className="truncate font-semibold text-ink">{user.name}</h3>
                    <p className="truncate text-sm text-ink-muted" dir="ltr">
                        {user.email}
                    </p>
                </div>
                <div className="flex flex-wrap gap-1.5">
                    <Badge tone={user.isActive ? 'success' : 'danger'} dot>
                        {user.isActive ? t('admin.users.status_active') : t('admin.users.status_inactive')}
                    </Badge>
                    <Badge tone={user.twoFactorEnabled ? 'brand' : 'warning'}>
                        {user.twoFactorEnabled ? t('admin.users.two_factor_on') : t('admin.users.two_factor_off')}
                    </Badge>
                </div>
            </div>

            {user.credentialVerifiedAt !== null && user.credentialReference !== null ? (
                <p className="mt-3 flex items-center gap-1.5 text-xs text-ink-muted">
                    <ShieldCheck aria-hidden="true" className="size-3.5 text-success" />
                    {t('admin.users.verified_badge')} <bdi className="font-mono">{user.credentialReference}</bdi>
                </p>
            ) : null}

            {user.canUpdateRoles ? (
                <form onSubmit={submitRoles} className="mt-5 space-y-4 border-t border-line pt-4">
                    <fieldset>
                        <legend className="text-xs font-semibold text-ink-subtle">{t('admin.users.roles_label')}</legend>
                        <div className="mt-2 flex flex-wrap gap-2">
                            {availableRoles.map((role) => (
                                <PillCheckbox
                                    key={role}
                                    checked={selectedRoles.includes(role)}
                                    onChange={() => toggleRole(role)}
                                    label={t(`admin.role.${role}`)}
                                />
                            ))}
                        </div>
                    </fieldset>

                    {selectedRoles.includes('medical_reviewer') ? (
                        <Field
                            id={`credential-${user.id}`}
                            label={t('admin.users.credential_reference')}
                            help={t('admin.users.credential_help')}
                        >
                            <TextInput
                                id={`credential-${user.id}`}
                                value={credentialRef}
                                onChange={(e) => setCredentialRef(e.target.value)}
                            />
                        </Field>
                    ) : null}

                    <div className="flex flex-wrap items-center gap-2">
                        <Button type="submit" size="sm" disabled={!changed}>
                            <Save aria-hidden="true" className="size-4" />
                            {t('admin.users.save_roles')}
                        </Button>
                        {user.canToggleActive ? (
                            <Button
                                size="sm"
                                variant={user.isActive ? 'danger' : 'secondary'}
                                onClick={() => router.patch(user.toggleActiveUrl, {}, { preserveScroll: true })}
                            >
                                <Power aria-hidden="true" className="size-4" />
                                {user.isActive ? t('admin.users.deactivate') : t('admin.users.activate')}
                            </Button>
                        ) : null}
                        {user.canResetTwoFactor ? (
                            <Button
                                size="sm"
                                variant="ghost"
                                onClick={() => router.post(user.resetTwoFactorUrl, {}, { preserveScroll: true })}
                            >
                                <ShieldOff aria-hidden="true" className="size-4" />
                                {t('admin.users.reset_2fa')}
                            </Button>
                        ) : null}
                        {user.canDelete ? (
                            <Button size="sm" variant="danger" onClick={() => setConfirmingDelete(true)}>
                                <Trash2 aria-hidden="true" className="size-4" />
                                {t('admin.users.delete')}
                            </Button>
                        ) : null}
                        <ConfirmDialog
                            open={confirmingDelete}
                            title={t('admin.users.delete_title')}
                            confirmLabel={t('admin.users.delete')}
                            cancelLabel={t('clear.cancel')}
                            onCancel={() => setConfirmingDelete(false)}
                            onConfirm={() =>
                                router.delete(user.deleteUrl, {
                                    preserveScroll: true,
                                    onFinish: () => setConfirmingDelete(false),
                                })
                            }
                        >
                            <p>{t('admin.users.delete_body')}</p>
                        </ConfirmDialog>
                    </div>
                </form>
            ) : (
                <div className="mt-4 flex flex-wrap gap-1.5 border-t border-line pt-4">
                    {user.roles.map((role) => (
                        <Badge key={role} tone="brand">
                            {t(`admin.role.${role}`)}
                        </Badge>
                    ))}
                </div>
            )}
        </Card>
    );
}

/**
 * Super Admin account and role management (M11A-FR-03, M11A-FR-04).
 */
export default function UsersIndex({ users, availableRoles, createUrl }: UsersIndexProps) {
    const t = useT();
    const { data, setData, post, processing, errors, reset } = useForm<{
        name: string;
        email: string;
        preferred_locale: LocaleCode;
        password: string;
        roles: AdminRoleKey[];
        credential_reference: string;
    }>({ name: '', email: '', preferred_locale: 'ar', password: '', roles: ['content_editor'], credential_reference: '' });

    const toggleNewRole = (role: AdminRoleKey) => {
        setData('roles', data.roles.includes(role) ? data.roles.filter((item) => item !== role) : [...data.roles, role]);
    };

    const submitCreate = (event: FormEvent) => {
        event.preventDefault();
        post(createUrl, { preserveScroll: true, onSuccess: () => reset('name', 'email', 'password', 'credential_reference') });
    };

    const activeCount = users.filter((user) => user.isActive).length;

    return (
        <AdminLayout>
            <Head title={t('admin.users.title')} />

            <div className="space-y-8">
                <div className="animate-fade-up">
                    <PageHeader
                        title={t('admin.users.title')}
                        description={t('admin.users.subtitle')}
                        actions={
                            <div className="flex gap-2">
                                <Badge tone="brand">{t('admin.users.count_total', { count: users.length })}</Badge>
                                <Badge tone="success">{t('admin.users.count_active', { count: activeCount })}</Badge>
                            </div>
                        }
                    />
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
                    <section aria-labelledby="existing-users-title" className="order-2 space-y-4 xl:order-1">
                        <h2 id="existing-users-title" className="flex items-center gap-2 text-base font-semibold text-ink">
                            <Users aria-hidden="true" className="size-5 text-brand" />
                            {t('admin.users.list_title')}
                        </h2>
                        {users.length === 0 ? (
                            <EmptyState icon={Users} title={t('admin.users.empty')} />
                        ) : (
                            <div className="stagger grid gap-4 2xl:grid-cols-2">
                                {users.map((user) => (
                                    <UserCard key={user.id} user={user} availableRoles={availableRoles} />
                                ))}
                            </div>
                        )}
                    </section>

                    <Card as="section" className="order-1 animate-fade-up p-5 xl:sticky xl:top-24 xl:order-2">
                        <h2 className="flex items-center gap-2 text-base font-semibold text-ink">
                            <span className="inline-flex size-9 items-center justify-center rounded-xl bg-brand-soft text-brand">
                                <UserPlus aria-hidden="true" className="size-[18px]" />
                            </span>
                            {t('admin.users.create_title')}
                        </h2>

                        <form onSubmit={submitCreate} className="mt-5 space-y-4" noValidate>
                            <Field id="new-name" label={t('admin.users.name')} error={errors.name}>
                                <TextInput
                                    id="new-name"
                                    required
                                    value={data.name}
                                    invalid={errors.name !== undefined}
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                            </Field>
                            <Field id="new-email" label={t('admin.auth.email')} error={errors.email}>
                                <TextInput
                                    id="new-email"
                                    type="email"
                                    required
                                    dir="ltr"
                                    autoComplete="off"
                                    value={data.email}
                                    invalid={errors.email !== undefined}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                            </Field>
                            <Field id="new-locale" label={t('admin.users.preferred_locale')}>
                                <SelectInput
                                    id="new-locale"
                                    value={data.preferred_locale}
                                    onChange={(e) => setData('preferred_locale', e.target.value as LocaleCode)}
                                >
                                    <option value="ar">{t('admin.locale.ar')}</option>
                                    <option value="en">{t('admin.locale.en')}</option>
                                </SelectInput>
                            </Field>
                            <Field id="new-password" label={t('admin.users.initial_password')} error={errors.password}>
                                <TextInput
                                    id="new-password"
                                    type="password"
                                    dir="ltr"
                                    autoComplete="new-password"
                                    value={data.password}
                                    invalid={errors.password !== undefined}
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                            </Field>

                            <fieldset>
                                <legend className="text-sm font-medium text-ink">{t('admin.users.roles_label')}</legend>
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {availableRoles.map((role) => (
                                        <PillCheckbox
                                            key={role}
                                            checked={data.roles.includes(role)}
                                            onChange={() => toggleNewRole(role)}
                                            label={t(`admin.role.${role}`)}
                                        />
                                    ))}
                                </div>
                                {errors.roles !== undefined ? (
                                    <p role="alert" className="mt-1.5 text-xs font-medium text-danger">
                                        {errors.roles}
                                    </p>
                                ) : null}
                            </fieldset>

                            {data.roles.includes('medical_reviewer') ? (
                                <Field
                                    id="new-credential"
                                    label={t('admin.users.credential_reference')}
                                    help={t('admin.users.credential_help')}
                                    error={errors.credential_reference}
                                >
                                    <TextInput
                                        id="new-credential"
                                        value={data.credential_reference}
                                        invalid={errors.credential_reference !== undefined}
                                        onChange={(e) => setData('credential_reference', e.target.value)}
                                    />
                                </Field>
                            ) : null}

                            <Button type="submit" disabled={processing} className="w-full">
                                <KeyRound aria-hidden="true" className="size-4" />
                                {t('admin.users.create_button')}
                            </Button>
                        </form>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
