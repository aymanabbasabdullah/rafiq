<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\AiConfiguration;

/**
 * Effective AI settings: the active admin configuration over code defaults. The configuration can
 * switch the AI off and lower limits or the budget, but never raise them above config (BR-09-5).
 */
class AiSettings
{
    public const SUGGESTION_CONTEXTS = ['home', 'learn', 'article', 'glossary'];

    private ?AiConfiguration $active = null;

    private bool $loaded = false;

    public function active(): ?AiConfiguration
    {
        if (! $this->loaded) {
            $this->active = AiConfiguration::query()->where('status', AiConfiguration::STATUS_ACTIVE)->latest('version')->first();
            $this->loaded = true;
        }

        return $this->active;
    }

    public function enabled(): bool
    {
        return (bool) config('rafiq.ai.enabled') && ($this->active()?->settings['enabled'] ?? true) !== false;
    }

    public function version(): ?int
    {
        return $this->active()?->version;
    }

    /**
     * @return array<string, int>
     */
    public function limits(): array
    {
        /** @var array<string, int> $ceiling */
        $ceiling = config('rafiq.ai.limits');
        $configured = (array) ($this->active()?->settings['limits'] ?? []);

        foreach ($ceiling as $key => $max) {
            if (isset($configured[$key]) && is_numeric($configured[$key])) {
                $ceiling[$key] = max(1, min($max, (int) $configured[$key]));
            }
        }

        return $ceiling;
    }

    public function dailyBudgetUsd(): float
    {
        $ceiling = (float) config('rafiq.ai.daily_budget_usd');
        $configured = $this->active()?->settings['daily_budget_usd'] ?? null;

        return is_numeric($configured) ? min($ceiling, max(0.0, (float) $configured)) : $ceiling;
    }

    public function defaultSimplification(): int
    {
        $level = (int) ($this->active()?->settings['default_simplification'] ?? 2);

        return in_array($level, [1, 2, 3], true) ? $level : 2;
    }

    public function welcome(string $locale): string
    {
        $text = $this->active()?->settings['welcome'][$locale] ?? null;

        return is_string($text) && $text !== '' ? $text : __('ai.welcome', [], $locale);
    }

    /**
     * @return list<string>
     */
    public function suggestions(string $context, string $locale): array
    {
        $configured = $this->active()?->settings['suggestions'][$context][$locale] ?? null;

        if (is_array($configured) && $configured !== []) {
            return array_values(array_filter($configured, 'is_string'));
        }

        return array_map(fn (int $index): string => __("ai.suggest.{$context}.{$index}", [], $locale), [1, 2, 3]);
    }
}
