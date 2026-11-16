<?php

declare(strict_types=1);

namespace App\Services\Clinical;

/**
 * Writes a rule condition as a plain sentence for medical reviewers (M11C-FR-02), in Arabic or English,
 * using the question wording of the release. Reviewers also see the raw JSON next to it.
 */
final class ReadableCondition
{
    /**
     * @param  array<string, array<string, mixed>>  $questions  Question definitions keyed by key (with `prompt`).
     */
    public function __construct(private readonly array $questions) {}

    /**
     * @param  array<string, mixed>  $condition
     */
    public function describe(array $condition, string $locale): string
    {
        foreach (['all' => 'and', 'any' => 'or'] as $combinator => $joiner) {
            if (isset($condition[$combinator])) {
                $parts = array_map(fn (array $child): string => $this->describe($child, $locale), $condition[$combinator]);

                return count($parts) === 1 ? $parts[0] : '('.implode(' '.__("admin.clinical.join.{$joiner}", [], $locale).' ', $parts).')';
            }
        }

        if (isset($condition['not'])) {
            return __('admin.clinical.join.not', ['condition' => $this->describe($condition['not'], $locale)], $locale);
        }

        $key = (string) $condition['q'];
        $subject = match ($key) {
            '@journey' => __('admin.clinical.subject.journey', [], $locale),
            '@age_confirmed' => __('admin.clinical.subject.age_confirmed', [], $locale),
            default => '«'.($this->questions[$key]['prompt'][$locale] ?? $key).'»',
        };

        return __("admin.clinical.op.{$condition['op']}", [
            'subject' => $subject,
            'value' => $this->value($condition['value'] ?? null, $locale),
        ], $locale);
    }

    private function value(mixed $value, string $locale): string
    {
        if (is_array($value)) {
            return implode(__('admin.clinical.join.list', [], $locale), array_map(fn (mixed $item): string => $this->value($item, $locale), $value));
        }

        if (is_bool($value)) {
            return __($value ? 'admin.clinical.value.yes' : 'admin.clinical.value.no', [], $locale);
        }

        $string = (string) $value;

        return in_array($string, ['yes', 'no', 'unsure'], true) ? __("admin.clinical.value.{$string}", [], $locale) : $string;
    }
}
