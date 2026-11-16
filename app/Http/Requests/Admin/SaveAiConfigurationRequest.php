<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\AiConfiguration;
use App\Services\Ai\AiSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Safe fields of an AI configuration draft (M11D-FR-01/02). Admin text is shown to visitors as-is and is
 * never added to the model's instructions; it still may not try to widen the assistant's scope (GR-15).
 */
class SaveAiConfigurationRequest extends FormRequest
{
    /**
     * Wording that would turn the assistant into something it must not be.
     */
    private const FORBIDDEN = '/(diagnos|prescri|dosage|\bdose\b|ignore (all|previous|the above)|system prompt|act as|you are now|شخّص|شخص حال|تشخيص|وصف(ه|ة)? (دواء|علاج)|جرعه|جرعة|تجاهل|تصرف ك)/iu';

    public function authorize(): bool
    {
        return $this->user()?->can('manage', AiConfiguration::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $limits = (array) config('rafiq.ai.limits');
        $rules = [
            'enabled' => ['required', 'boolean'],
            'welcome' => ['array'],
            'welcome.ar' => ['nullable', 'string', 'max:400'],
            'welcome.en' => ['nullable', 'string', 'max:400'],
            'suggestions' => ['array'],
            'default_simplification' => ['required', 'integer', 'between:1,3'],
            'daily_budget_usd' => ['required', 'numeric', 'min:0', 'max:'.(float) config('rafiq.ai.daily_budget_usd')],
            'limits' => ['array'],
            'limits.per_session_per_minute' => ['nullable', 'integer', 'min:1', 'max:'.$limits['per_session_per_minute']],
            'limits.per_ip_per_hour' => ['nullable', 'integer', 'min:1', 'max:'.$limits['per_ip_per_hour']],
            'limits.grounding_chunks' => ['nullable', 'integer', 'min:1', 'max:'.$limits['grounding_chunks']],
        ];

        foreach (AiSettings::SUGGESTION_CONTEXTS as $context) {
            foreach (['ar', 'en'] as $locale) {
                $rules["suggestions.{$context}.{$locale}"] = ['nullable', 'array', 'max:3'];
                $rules["suggestions.{$context}.{$locale}.*"] = ['string', 'max:120'];
            }
        }

        return $rules;
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $texts = array_filter([
                    'welcome.ar' => $this->input('welcome.ar'),
                    'welcome.en' => $this->input('welcome.en'),
                ]);

                foreach ((array) $this->input('suggestions', []) as $context => $locales) {
                    foreach ((array) $locales as $locale => $items) {
                        foreach ((array) $items as $index => $item) {
                            $texts["suggestions.{$context}.{$locale}.{$index}"] = $item;
                        }
                    }
                }

                foreach ($texts as $field => $text) {
                    if (is_string($text) && preg_match(self::FORBIDDEN, $text) === 1) {
                        $validator->errors()->add($field, __('admin.ai.error.forbidden_wording'));
                    }
                }
            },
        ];
    }

    /**
     * The settings JSON to store, with empty suggestion lists removed.
     *
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $validated = $this->validated();
        $suggestions = [];

        foreach (AiSettings::SUGGESTION_CONTEXTS as $context) {
            foreach (['ar', 'en'] as $locale) {
                $items = array_values(array_filter((array) ($validated['suggestions'][$context][$locale] ?? []), fn (mixed $item): bool => is_string($item) && trim($item) !== ''));

                if ($items !== []) {
                    $suggestions[$context][$locale] = $items;
                }
            }
        }

        return [
            'enabled' => (bool) $validated['enabled'],
            'welcome' => ['ar' => $validated['welcome']['ar'] ?? null, 'en' => $validated['welcome']['en'] ?? null],
            'suggestions' => $suggestions,
            'default_simplification' => (int) $validated['default_simplification'],
            'daily_budget_usd' => (float) $validated['daily_budget_usd'],
            'limits' => array_filter((array) ($validated['limits'] ?? []), fn (mixed $value): bool => $value !== null),
        ];
    }
}
