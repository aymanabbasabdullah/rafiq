<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Audit log visibility: Super Admin sees all; verified Medical Reviewer sees approval actions (Module-11 §11, Security-Architecture §3).
 */
class AuditLogPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->hasVerifiedMedicalReviewerRole();
    }
}
