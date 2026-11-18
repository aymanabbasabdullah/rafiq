<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Aggregated analytics are visible to every active admin role (Security-Architecture §3).
 */
class DailyMetricPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->activeRoleKeys() !== [];
    }
}
