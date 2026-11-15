<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Languages supported by Rafiq (L10N-001).
 */
enum Locale: string
{
    case Arabic = 'ar';
    case English = 'en';

    /**
     * The text direction used by this language.
     */
    public function direction(): string
    {
        return match ($this) {
            self::Arabic => 'rtl',
            self::English => 'ltr',
        };
    }

    /**
     * The language's own name, as shown in the language switcher.
     */
    public function nativeName(): string
    {
        return match ($this) {
            self::Arabic => 'العربية',
            self::English => 'English',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $locale): string => $locale->value, self::cases());
    }

    /**
     * The default language configured for the platform.
     */
    public static function default(): self
    {
        // Not `app.locale`: App::setLocale() rewrites that value for the current request.
        return self::tryFrom((string) config('rafiq.default_locale')) ?? self::Arabic;
    }

    /**
     * The other supported language, used by the language switcher.
     */
    public function alternate(): self
    {
        return $this === self::Arabic ? self::English : self::Arabic;
    }
}
