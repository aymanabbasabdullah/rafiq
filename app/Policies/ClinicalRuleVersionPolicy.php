<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClinicalRuleVersion;
use App\Models\User;

/**
 * Submitting a rule version for medical review: Super Admin or Content Editor (Clinical-Review-Process §3).
 * Approval and rejection use the `four-eyes` gate inside the workflow.
 */
class ClinicalRuleVersionPolicy
{
    public function submit(User $user, ClinicalRuleVersion $version): bool
    {
        return $user->isSuperAdmin() || $user->isContentEditor();
    }
}
