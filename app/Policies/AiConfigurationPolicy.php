<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * AI configuration is Super Admin only (Security-Architecture §3, Module-11-D).
 */
class AiConfigurationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function manage(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
