import { Head, router, useForm } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
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
    updateRolesUrl: string;
    toggleActiveUrl: string;
    resetTwoFactorUrl: string;
}

interface UsersIndexProps {
    users: AdminUserRow[];
    availableRoles: AdminRoleKey[];
    createUrl: string;
}

function UserRowCard({ user, availableRoles }: { user: AdminUserRow; availableRoles: AdminRoleKey[] }) {
    const t = useT();
    const [selectedRoles, setSelectedRoles] = useState<AdminRoleKey[]>(user.roles);
    const [credentialRef, setCredentialRef] = useState<string>(user.credentialReference ?? '');

    const toggleRole = (role: AdminRoleKey) => {
        setSelectedRoles((current) => (current.includes(role) ? current.filter((item) => item !== role) : [...current, role]));
    };

    const submitRoles = (event: FormEvent) => {
        event.preventDefault();
        router.put(
            user.updateRolesUrl,
            {
                roles: selectedRoles,
                credential_reference: selectedRoles.includes('medical_reviewer') ? credentialRef : null,
            },
            { preserveScroll: true },
        );
    };

    return (
        <article className="rounded-xl border border-line bg-surface-raised p-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 className="text-base font-bold text-ink">{user.name}</h3>
                    <p className="text-sm text-ink-muted" dir="ltr">
                        {user.email}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-2">
                    <span
                        className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${
                            user.isActive ? 'bg-brand/10 text-brand' : 'bg-danger/10 text-danger'
                        }`}
                    >
                        {user.isActive ? t('admin.users.status_active') : t('admin.users.status_inactive')}
                    </span>
                    <span className="rounded-full border border-line bg-surface px-2.5 py-0.5 text-xs font-medium text-ink-muted">
                        {user.twoFactorEnabled ? t('admin.users.two_factor_on') : t('admin.users.two_factor_off')}
                    </span>
                </div>
            </div>

            {user.canUpdateRoles ? (
                <form onSubmit={submitRoles} className="mt-4 space-y-3 border-t border-line pt-4">
                    <fieldset>
                        <legend className="text-xs font-semibold text-ink-muted">{t('admin.users.roles')}</legend>
                        <div className="mt-2 flex flex-wrap gap-3">
                            {availableRoles.map((role) => (
                                <label key={role} className="inline-flex items-center gap-2 text-sm text-ink">
                                    <input
                                        type="checkbox"
                                        checked={selectedRoles.includes(role)}
                                        onChange={() => toggleRole(role)}
                                        className="size-4 rounded-xs border-line text-brand focus:ring-brand"
                                    />
                                    <span>{t(`admin.role.${role}`)}</span>
                                </label>
                            ))}
                        </div>
                    </fieldset>

                    {selectedRoles.includes('medical_reviewer') ? (
                        <div>
                            <label htmlFor={`credential-${user.id}`} className="block text-xs font-medium text-ink">
                                {t('admin.users.credential_ref')}
                            </label>
                            <input
                                id={`credential-${user.id}`}
                                type="text"
                                value={credentialRef}
                                onChange={(e) => setCredentialRef(e.target.value)}
                                className="mt-1 block w-full rounded-lg border border-line bg-surface px-3 py-1.5 text-sm text-ink focus:border-brand focus:outline-hidden"
                            />
                            <p className="mt-1 text-xs text-ink-muted">{t('admin.users.credential_help')}</p>
                        </div>
                    ) : null}

                    <div className="flex flex-wrap items-center gap-2 pt-1">
                        <button
                            type="submit"
                            className="inline-flex min-h-9 items-center rounded-lg bg-brand px-3.5 py-1.5 text-xs font-semibold text-white hover:bg-brand-strong"
                        >
                            {t('admin.users.save_roles')}
                        </button>

                        {user.canToggleActive ? (
                            <button
                                type="button"
                                onClick={() => router.patch(user.toggleActiveUrl, {}, { preserveScroll: true })}
                                className="inline-flex min-h-9 items-center rounded-lg border border-line bg-surface px-3.5 py-1.5 text-xs font-medium text-ink hover:border-brand"
                            >
                                {user.isActive ? t('admin.users.deactivate') : t('admin.users.activate')}
                            </button>
                        ) : null}

                        {user.canResetTwoFactor ? (
                            <button
                                type="button"
                                onClick={() => router.post(user.resetTwoFactorUrl, {}, { preserveScroll: true })}
                                className="inline-flex min-h-9 items-center rounded-lg border border-line bg-surface px-3.5 py-1.5 text-xs font-medium text-ink hover:border-danger hover:text-danger"
                            >
                                {t('admin.users.reset_2fa')}
                            </button>
                        ) : null}
                    </div>
                </form>
            ) : (
                <div className="mt-4 flex flex-wrap gap-1.5 border-t border-line pt-3">
                    {user.roles.map((role) => (
                        <span key={role} className="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-medium text-brand">
                            {t(`admin.role.${role}`)}
                        </span>
                    ))}
                </div>
            )}
        </article>
    );
}

/**
 * Super Admin user & role management page (Module-11 §14.2, M11A-FR-03, M11A-FR-04).
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
    }>({
        name: '',
        email: '',
        preferred_locale: 'ar',
        password: '',
        roles: ['content_editor'],
        credential_reference: '',
    });

    const toggleNewRole = (role: AdminRoleKey) => {
        setData('roles', data.roles.includes(role) ? data.roles.filter((item) => item !== role) : [...data.roles, role]);
    };

    const submitCreate = (event: FormEvent) => {
        event.preventDefault();
        post(createUrl, {
            onSuccess: () => reset('name', 'email', 'password', 'credential_reference'),
        });
    };

    return (
        <AdminLayout>
            <Head title={t('admin.users.title')} />

            <div className="space-y-8">
                <div>
                    <h1 className="text-2xl font-bold text-ink">{t('admin.users.title')}</h1>
                    <p className="mt-1 text-sm text-ink-muted">{t('admin.users.subtitle')}</p>
                </div>

                <section aria-labelledby="create-user-title" className="rounded-xl border border-line bg-surface-raised p-6">
                    <h2 id="create-user-title" className="text-lg font-bold text-ink">
                        {t('admin.users.create_title')}
                    </h2>

                    <form onSubmit={submitCreate} className="mt-4 space-y-4" noValidate>
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label htmlFor="new-name" className="block text-sm font-medium text-ink">
                                    {t('admin.users.name')}
                                </label>
                                <input
                                    id="new-name"
                                    type="text"
                                    required
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1.5 block w-full rounded-lg border border-line bg-surface px-3.5 py-2 text-sm text-ink focus:border-brand focus:outline-hidden"
                                />
                                {errors.name ? (
                                    <p role="alert" className="mt-1 text-xs text-danger">
                                        {errors.name}
                                    </p>
                                ) : null}
                            </div>

                            <div>
                                <label htmlFor="new-email" className="block text-sm font-medium text-ink">
                                    {t('admin.users.email')}
                                </label>
                                <input
                                    id="new-email"
                                    type="email"
                                    required
                                    dir="ltr"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    className="mt-1.5 block w-full rounded-lg border border-line bg-surface px-3.5 py-2 text-sm text-ink focus:border-brand focus:outline-hidden"
                                />
                                {errors.email ? (
                                    <p role="alert" className="mt-1 text-xs text-danger">
                                        {errors.email}
                                    </p>
                                ) : null}
                            </div>

                            <div>
                                <label htmlFor="new-locale" className="block text-sm font-medium text-ink">
                                    {t('admin.users.preferred_locale')}
                                </label>
                                <select
                                    id="new-locale"
                                    value={data.preferred_locale}
                                    onChange={(e) => setData('preferred_locale', e.target.value as LocaleCode)}
                                    className="mt-1.5 block w-full rounded-lg border border-line bg-surface px-3.5 py-2 text-sm text-ink focus:border-brand focus:outline-hidden"
                                >
                                    <option value="ar">{t('locale.ar')}</option>
                                    <option value="en">{t('locale.en')}</option>
                                </select>
                            </div>

                            <div>
                                <label htmlFor="new-password" className="block text-sm font-medium text-ink">
                                    {t('admin.users.password_optional')}
                                </label>
                                <input
                                    id="new-password"
                                    type="password"
                                    dir="ltr"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    className="mt-1.5 block w-full rounded-lg border border-line bg-surface px-3.5 py-2 text-sm text-ink focus:border-brand focus:outline-hidden"
                                />
                                {errors.password ? (
                                    <p role="alert" className="mt-1 text-xs text-danger">
                                        {errors.password}
                                    </p>
                                ) : null}
                            </div>
                        </div>

                        <fieldset>
                            <legend className="text-sm font-medium text-ink">{t('admin.users.roles')}</legend>
                            <div className="mt-2 flex flex-wrap gap-4">
                                {availableRoles.map((role) => (
                                    <label key={role} className="inline-flex items-center gap-2 text-sm text-ink">
                                        <input
                                            type="checkbox"
                                            checked={data.roles.includes(role)}
                                            onChange={() => toggleNewRole(role)}
                                            className="size-4 rounded-xs border-line text-brand focus:ring-brand"
                                        />
                                        <span>{t(`admin.role.${role}`)}</span>
                                    </label>
                                ))}
                            </div>
                            {errors.roles ? (
                                <p role="alert" className="mt-1 text-xs text-danger">
                                    {errors.roles}
                                </p>
                            ) : null}
                        </fieldset>

                        {data.roles.includes('medical_reviewer') ? (
                            <div>
                                <label htmlFor="new-credential" className="block text-sm font-medium text-ink">
                                    {t('admin.users.credential_ref')}
                                </label>
                                <input
                                    id="new-credential"
                                    type="text"
                                    value={data.credential_reference}
                                    onChange={(e) => setData('credential_reference', e.target.value)}
                                    className="mt-1.5 block w-full rounded-lg border border-line bg-surface px-3.5 py-2 text-sm text-ink focus:border-brand focus:outline-hidden"
                                />
                                <p className="mt-1 text-xs text-ink-muted">{t('admin.users.credential_help')}</p>
                                {errors.credential_reference ? (
                                    <p role="alert" className="mt-1 text-xs text-danger">
                                        {errors.credential_reference}
                                    </p>
                                ) : null}
                            </div>
                        ) : null}

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex min-h-10 items-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong disabled:opacity-50"
                        >
                            {t('admin.users.create_submit')}
                        </button>
                    </form>
                </section>

                <section aria-labelledby="existing-users-title" className="space-y-4">
                    <h2 id="existing-users-title" className="text-lg font-bold text-ink">
                        {t('admin.users.list_title')}
                    </h2>
                    <div className="grid gap-4">
                        {users.map((user) => (
                            <UserRowCard key={user.id} user={user} availableRoles={availableRoles} />
                        ))}
                    </div>
                </section>
            </div>
        </AdminLayout>
    );
}
