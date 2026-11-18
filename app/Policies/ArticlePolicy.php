<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

/**
 * Content permissions (Security-Architecture §3, Module-11 §11).
 *
 * - Write/submit drafts: Super Admin, Content Editor, Medical Reviewer.
 * - Publish/hide/archive approved content: Super Admin, Content Editor.
 * - Medical approval is not here: it uses the `four-eyes` gate (verified reviewer, not author/last editor).
 */
class ArticlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isContentEditor() || $user->isMedicalReviewer();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Article $article): bool
    {
        return $this->viewAny($user);
    }

    public function publish(User $user, ?Article $article = null): bool
    {
        return $user->isSuperAdmin() || $user->isContentEditor();
    }
}
