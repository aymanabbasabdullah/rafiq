<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['key'])]
class Role extends Model
{
    public static function forRole(AdminRole $role): self
    {
        return self::query()->firstOrCreate(['key' => $role->value]);
    }

    public static function ensureAllExist(): void
    {
        foreach (AdminRole::cases() as $role) {
            self::forRole($role);
        }
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withPivot(['granted_by', 'granted_at', 'credential_verified_at', 'credential_reference', 'revoked_at']);
    }
}
