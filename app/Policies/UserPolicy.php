<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AdminRole;
use App\Models\User;

/**
 * Authorization rules for managing Rafiq Admin accounts and roles (ADM-040, BR-11-4, SEC-009).
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isSuperAdmin();
    }

    /**
     * An admin may never grant or revoke their own roles (BR-11-4).
     */
    public function updateRoles(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $actor->id !== $target->id;
    }

    /**
     * An admin may never deactivate themselves or the last active Super Admin (BR-11-4).
     */
    public function toggleActive(User $actor, User $target): bool
    {
        if (! $actor->isSuperAdmin() || $actor->id === $target->id) {
            return false;
        }

        if ($target->is_active && $target->isSuperAdmin()) {
            $activeSuperAdmins = User::query()
                ->where('is_active', true)
                ->whereHas('activeRoles', fn ($query) => $query->where('key', AdminRole::SuperAdmin->value))
                ->count();

            if ($activeSuperAdmins <= 1) {
                return false;
            }
        }

        return true;
    }

    public function resetTwoFactor(User $actor, User $target): bool
    {
        return $actor->isSuperAdmin() && $actor->id !== $target->id;
    }
}
