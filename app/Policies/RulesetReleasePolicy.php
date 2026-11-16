<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RulesetRelease;
use App\Models\User;

/**
 * Clinical rules permissions (Security-Architecture §3, Module-11-C).
 *
 * - View rules and releases: Super Admin, Content Editor, Medical Reviewer.
 * - Run the clinical test cases: Super Admin, Medical Reviewer.
 * - Submit a release for medical review and publish it: Super Admin.
 * - Medical approval is not here: it uses the `four-eyes` gate (verified reviewer, not the submitter).
 */
class RulesetReleasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isContentEditor() || $user->isMedicalReviewer();
    }

    public function runTests(User $user, ?RulesetRelease $release = null): bool
    {
        return $user->isSuperAdmin() || $user->isMedicalReviewer();
    }

    public function submit(User $user, ?RulesetRelease $release = null): bool
    {
        return $user->isSuperAdmin();
    }

    public function publish(User $user, ?RulesetRelease $release = null): bool
    {
        return $user->isSuperAdmin();
    }
}
