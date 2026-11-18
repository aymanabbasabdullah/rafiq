<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateUserRolesRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Super Admin management of admin accounts, roles, credential verification and 2FA resets (M11A-FR-03, M11A-FR-04, ADM-040).
 */
class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        /** @var User $actor */
        $actor = $request->user();

        $users = User::query()
            ->with('activeRoles')
            ->orderBy('id')
            ->get()
            ->map(function (User $user) use ($actor): array {
                $medicalRole = $user->activeRoles->firstWhere('key', AdminRole::MedicalReviewer->value);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'preferredLocale' => $user->preferred_locale,
                    'isActive' => $user->is_active,
                    'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
                    'lastLoginAt' => $user->last_login_at?->toIso8601String(),
                    'roles' => $user->activeRoleKeys(),
                    'credentialReference' => $medicalRole?->pivot?->getAttribute('credential_reference'),
                    'credentialVerifiedAt' => $medicalRole?->pivot?->getAttribute('credential_verified_at'),
                    'canToggleActive' => $actor->can('toggleActive', $user),
                    'canUpdateRoles' => $actor->can('updateRoles', $user),
                    'canResetTwoFactor' => $actor->can('resetTwoFactor', $user),
                    'canDelete' => $actor->can('delete', $user),
                    'deactivatedAt' => $user->deactivated_at?->toIso8601String(),
                    'deleteUrl' => route('admin.users.destroy', ['user' => $user->id]),
                    'updateRolesUrl' => route('admin.users.roles', ['user' => $user->id]),
                    'toggleActiveUrl' => route('admin.users.toggle-active', ['user' => $user->id]),
                    'resetTwoFactorUrl' => route('admin.users.reset-two-factor', ['user' => $user->id]),
                ];
            });

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'availableRoles' => AdminRole::values(),
            'createUrl' => route('admin.users.store'),
        ]);
    }

    public function store(StoreAdminUserRequest $request, AuditLogger $audit): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        $email = strtolower(trim((string) $validated['email']));
        $providedPassword = $validated['password'] ?? null;
        $rawPassword = is_string($providedPassword) && $providedPassword !== ''
            ? $providedPassword
            : Str::password(20);

        /** @var list<string> $roleKeys */
        $roleKeys = $validated['roles'];
        $credentialRef = isset($validated['credential_reference']) ? trim((string) $validated['credential_reference']) : null;

        $created = DB::transaction(function () use ($validated, $email, $rawPassword, $roleKeys, $credentialRef, $actor, $audit): User {
            Role::ensureAllExist();

            $user = User::query()->create([
                'name' => (string) $validated['name'],
                'email' => $email,
                'password' => $rawPassword,
                'preferred_locale' => (string) $validated['preferred_locale'],
                'is_active' => true,
            ]);

            $audit->record('user.created', $user, [
                'email' => $user->email,
                'preferred_locale' => $user->preferred_locale,
            ], $actor);

            foreach ($roleKeys as $key) {
                $role = AdminRole::from($key);
                $isMedical = $role === AdminRole::MedicalReviewer;

                $user->grantRole(
                    role: $role,
                    grantedBy: $actor,
                    credentialReference: $isMedical ? $credentialRef : null,
                    credentialVerifiedAt: $isMedical ? now() : null,
                );

                $audit->record('role.granted', $user, [
                    'role' => $role->value,
                    'credential_reference' => $isMedical ? $credentialRef : null,
                ], $actor);
            }

            return $user;
        });

        if (! is_string($providedPassword) || $providedPassword === '') {
            PasswordBroker::broker()->sendResetLink(['email' => $created->email]);
        }

        return redirect()->route('admin.users.index');
    }

    public function updateRoles(UpdateUserRolesRequest $request, User $user, AuditLogger $audit): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $validated = $request->validated();

        /** @var list<string> $desiredKeys */
        $desiredKeys = array_values(array_unique($validated['roles']));
        $credentialRef = isset($validated['credential_reference']) ? trim((string) $validated['credential_reference']) : null;

        if ($user->is_active && $user->isSuperAdmin() && ! in_array(AdminRole::SuperAdmin->value, $desiredKeys, true)) {
            $otherActiveSuperAdmins = User::query()
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->whereHas('activeRoles', fn ($query) => $query->where('key', AdminRole::SuperAdmin->value))
                ->count();

            if ($otherActiveSuperAdmins === 0) {
                throw ValidationException::withMessages([
                    'roles' => [__('admin.users.last_super_admin')],
                ]);
            }
        }

        DB::transaction(function () use ($user, $desiredKeys, $credentialRef, $actor, $audit): void {
            Role::ensureAllExist();
            $currentKeys = $user->activeRoleKeys();

            foreach ($desiredKeys as $key) {
                $role = AdminRole::from($key);
                $isMedical = $role === AdminRole::MedicalReviewer;

                $user->grantRole(
                    role: $role,
                    grantedBy: $actor,
                    credentialReference: $isMedical ? $credentialRef : null,
                    credentialVerifiedAt: $isMedical ? now() : null,
                );

                if (! in_array($key, $currentKeys, true) || $isMedical) {
                    $audit->record('role.granted', $user, [
                        'role' => $role->value,
                        'credential_reference' => $isMedical ? $credentialRef : null,
                    ], $actor);
                }
            }

            foreach (array_diff($currentKeys, $desiredKeys) as $revokedKey) {
                $role = AdminRole::from($revokedKey);
                $user->revokeRole($role);

                $audit->record('role.revoked', $user, [
                    'role' => $role->value,
                ], $actor);
            }
        });

        return redirect()->route('admin.users.index');
    }

    public function toggleActive(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('toggleActive', $user);

        /** @var User $actor */
        $actor = $request->user();

        $user->forceFill([
            'is_active' => ! $user->is_active,
            'deactivated_at' => $user->is_active ? now() : null,
            'remember_token' => Str::random(60),
        ])->save();

        $audit->record(
            $user->is_active ? 'user.activated' : 'user.deactivated',
            $user,
            ['email' => $user->email],
            $actor,
        );

        return redirect()->route('admin.users.index');
    }

    /**
     * Deletes an account deactivated for the full retention period. The audit entry records only the old id.
     */
    public function destroy(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('delete', $user);

        /** @var User $actor */
        $actor = $request->user();

        DB::transaction(function () use ($user, $actor, $audit): void {
            $audit->record('user.deleted', summary: ['deleted_user_id' => $user->id], actor: $actor);
            $user->delete();
        });

        return redirect()->route('admin.users.index')->with('success', __('admin.users.deleted'));
    }

    public function resetTwoFactor(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('resetTwoFactor', $user);

        /** @var User $actor */
        $actor = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'remember_token' => Str::random(60),
        ])->save();

        $audit->record('user.two_factor_reset', $user, ['email' => $user->email], $actor);

        return redirect()->route('admin.users.index');
    }
}
