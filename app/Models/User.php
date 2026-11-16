<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdminRole;
use Database\Factories\UserFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'two_factor_secret',
    'two_factor_recovery_codes',
    'two_factor_confirmed_at',
    'preferred_locale',
    'is_active',
    'last_login_at',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot(['granted_by', 'granted_at', 'credential_verified_at', 'credential_reference', 'revoked_at']);
    }

    /**
     * Active (non-revoked) role assignments.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function activeRoles(): BelongsToMany
    {
        return $this->roles()->wherePivotNull('revoked_at');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return is_string($this->two_factor_secret)
            && $this->two_factor_secret !== ''
            && $this->two_factor_confirmed_at !== null;
    }

    public function hasRole(AdminRole $role): bool
    {
        $roles = $this->relationLoaded('activeRoles') ? $this->activeRoles : $this->activeRoles()->get();

        return $roles->contains(fn (Role $assigned): bool => $assigned->key === $role->value);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(AdminRole::SuperAdmin);
    }

    public function isContentEditor(): bool
    {
        return $this->hasRole(AdminRole::ContentEditor);
    }

    public function isMedicalReviewer(): bool
    {
        return $this->hasRole(AdminRole::MedicalReviewer);
    }

    /**
     * Medical approval requires both the MedicalReviewer role and recorded credential verification (ADM-003, BR-11-5).
     */
    public function hasVerifiedMedicalReviewerRole(): bool
    {
        $roles = $this->relationLoaded('activeRoles') ? $this->activeRoles : $this->activeRoles()->get();

        return $roles->contains(function (Role $assigned): bool {
            if ($assigned->key !== AdminRole::MedicalReviewer->value) {
                return false;
            }

            $verifiedAt = $assigned->pivot?->getAttribute('credential_verified_at');
            $reference = (string) ($assigned->pivot?->getAttribute('credential_reference') ?? '');

            return $verifiedAt !== null && trim($reference) !== '';
        });
    }

    /**
     * Assign or update a role on this user (upsert on role_user).
     */
    public function grantRole(
        AdminRole $role,
        ?self $grantedBy = null,
        ?string $credentialReference = null,
        ?DateTimeInterface $credentialVerifiedAt = null,
    ): void {
        $roleModel = Role::forRole($role);

        $pivot = [
            'granted_by' => $grantedBy?->id,
            'granted_at' => now(),
            'credential_verified_at' => $role === AdminRole::MedicalReviewer ? $credentialVerifiedAt : null,
            'credential_reference' => $role === AdminRole::MedicalReviewer ? $credentialReference : null,
            'revoked_at' => null,
        ];

        if ($this->roles()->where('roles.id', $roleModel->id)->exists()) {
            $this->roles()->updateExistingPivot($roleModel->id, $pivot);
        } else {
            $this->roles()->attach($roleModel->id, $pivot);
        }

        $this->unsetRelation('roles');
        $this->unsetRelation('activeRoles');
    }

    public function revokeRole(AdminRole $role): void
    {
        $roleModel = Role::forRole($role);

        if ($this->roles()->where('roles.id', $roleModel->id)->exists()) {
            $this->roles()->updateExistingPivot($roleModel->id, ['revoked_at' => now()]);
        }

        $this->unsetRelation('roles');
        $this->unsetRelation('activeRoles');
    }

    /**
     * @return list<string>
     */
    public function activeRoleKeys(): array
    {
        $roles = $this->relationLoaded('activeRoles') ? $this->activeRoles : $this->activeRoles()->get();

        /** @var list<string> $keys */
        $keys = $roles->pluck('key')->values()->all();

        return $keys;
    }
}
