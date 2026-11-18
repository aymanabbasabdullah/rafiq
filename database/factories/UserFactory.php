<?php

namespace Database\Factories;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => '[SYNTHETIC] '.fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('CorrectHorseBatteryStaple!1'),
            'two_factor_secret' => 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP',
            'two_factor_recovery_codes' => ['ABCD-1234', 'EFGH-5678'],
            'two_factor_confirmed_at' => now(),
            'preferred_locale' => 'ar',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->grantRole(AdminRole::SuperAdmin);
        });
    }

    public function contentEditor(): static
    {
        return $this->afterCreating(function (User $user): void {
            $user->grantRole(AdminRole::ContentEditor);
        });
    }

    public function medicalReviewer(bool $verified = true): static
    {
        return $this->afterCreating(function (User $user) use ($verified): void {
            $user->grantRole(
                AdminRole::MedicalReviewer,
                credentialReference: $verified ? '[SYNTHETIC] SCFHS-2026-001' : null,
                credentialVerifiedAt: $verified ? now() : null,
            );
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
