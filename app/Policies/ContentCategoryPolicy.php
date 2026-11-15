<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContentCategory;
use App\Models\User;

/**
 * Topics are managed by Super Admins and Content Editors (M11B-FR-01).
 */
class ContentCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isContentEditor() || $user->isMedicalReviewer();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isContentEditor();
    }

    public function update(User $user, ContentCategory $category): bool
    {
        return $this->create($user);
    }
}
