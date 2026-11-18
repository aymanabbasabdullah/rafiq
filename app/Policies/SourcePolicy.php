<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Source;
use App\Models\User;

/**
 * Reference sources are managed by Super Admins and Content Editors (ADM-011); reviewers can read them.
 */
class SourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isContentEditor() || $user->isMedicalReviewer();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isContentEditor();
    }

    public function update(User $user, Source $source): bool
    {
        return $this->create($user);
    }
}
