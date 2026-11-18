<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Support\Facades\Route;

/**
 * The four user journeys J1–J4 (FR-010).
 */
enum Journey: string
{
    case Learn = 'learn';
    case Concern = 'concern';
    case Conception = 'conception';
    case DoctorPrep = 'doctor_prep';

    /**
     * Resolve a journey from its named route (e.g. `journeys.concern`).
     */
    public static function fromRouteName(?string $routeName): ?self
    {
        if ($routeName === null || ! str_starts_with($routeName, 'journeys.')) {
            return null;
        }

        return self::tryFrom(substr($routeName, strlen('journeys.')));
    }

    /**
     * Journeys currently enabled in configuration (`rafiq.journeys.enabled`).
     *
     * @return list<self>
     */
    public static function enabledCases(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $journey): bool => $journey->isEnabled(),
        ));
    }

    /**
     * Card descriptors for enabled journeys, optionally excluding the active journey.
     *
     * @return list<array{key: string, url: string|null}>
     */
    public static function cards(?self $exclude = null): array
    {
        $journeys = array_values(array_filter(
            self::enabledCases(),
            fn (self $journey): bool => $journey !== $exclude,
        ));

        return array_map(fn (self $journey): array => [
            'key' => $journey->value,
            'url' => $journey->url(),
        ], $journeys);
    }

    public function routeName(): string
    {
        return "journeys.{$this->value}";
    }

    public function isEnabled(): bool
    {
        $enabled = config('rafiq.journeys.enabled', []);

        if (! is_array($enabled)) {
            return false;
        }

        if (array_is_list($enabled)) {
            return in_array($this->value, $enabled, true);
        }

        return (bool) ($enabled[$this->value] ?? false);
    }

    public function url(): ?string
    {
        if (! $this->isEnabled() || ! Route::has($this->routeName())) {
            return null;
        }

        return route($this->routeName());
    }

    /**
     * Journeys that process temporary health answers require explicit consent (FR-021).
     */
    public function requiresAssessmentConsent(): bool
    {
        return match ($this) {
            self::Concern, self::Conception => true,
            self::Learn, self::DoctorPrep => false,
        };
    }

    /**
     * J1 (Learn) and J4 (Doctor Prep) have no safety screening step, so they display
     * a permanent warning-signs reminder with a link to emergency care and J2 (M02-FR-06, FR-004).
     */
    public function showsRedFlagReminder(): bool
    {
        return match ($this) {
            self::Learn, self::DoctorPrep => true,
            self::Concern, self::Conception => false,
        };
    }

    /**
     * J4 (Doctor Prep) keeps personal questions in browser memory only and shows a
     * contextual local-only notice on entry (M03-FR-08, FR-092).
     */
    public function showsLocalOnlyNotice(): bool
    {
        return $this === self::DoctorPrep;
    }
}
