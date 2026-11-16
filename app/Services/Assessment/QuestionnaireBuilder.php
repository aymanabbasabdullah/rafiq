<?php

declare(strict_types=1);

namespace App\Services\Assessment;

use App\Services\Clinical\LoadedRuleset;

/**
 * Turns the active ruleset's question definitions into what the J2/J3 screens show (M04-FR-01, M05-FR-01):
 * wording in the visitor's language, option labels, `show_if` (applied in the browser for display only —
 * the engine re-checks it) and `stop_on_yes` for questions whose "yes" alone means an emergency (M04-FR-03).
 */
final class QuestionnaireBuilder
{
    /**
     * @return list<array{key: string, section: string, type: string, required: bool, prompt: string, options: list<array{value: string, label: string}>, exclusive: list<string>, show_if: array<string, mixed>|null, stop_on_yes: bool}>
     */
    public function questions(LoadedRuleset $ruleset, string $journey, string $locale): array
    {
        $stopOnYes = $this->stopOnYesKeys($ruleset);
        $questions = [];

        foreach ($ruleset->questions as $key => $question) {
            if (! in_array($journey, $question['journeys'], true)) {
                continue;
            }

            $questions[] = [
                'key' => $key,
                'section' => $question['section'],
                'type' => $question['type'],
                'required' => (bool) $question['required'],
                'prompt' => (string) ($question['prompt'][$locale] ?? $question['prompt']['en'] ?? $key),
                'options' => array_map(fn (string $value): array => [
                    'value' => $value,
                    'label' => __("assessment.option.{$value}", [], $locale),
                ], $question['options']),
                'exclusive' => array_values($question['exclusive'] ?? []),
                'show_if' => $question['show_if'],
                'stop_on_yes' => in_array($key, $stopOnYes, true),
            ];
        }

        return $questions;
    }

    /**
     * Safety questions where a plain "yes" triggers a P0 rule with `stop` (e.g. CR-002, CR-003, CR-017).
     *
     * @return list<string>
     */
    private function stopOnYesKeys(LoadedRuleset $ruleset): array
    {
        $keys = [];

        foreach ($ruleset->rules as $rule) {
            if ($rule['section'] !== 'safety' || ($rule['enabled'] ?? true) === false || ! isset($rule['when'], $rule['outcome'])) {
                continue;
            }

            $when = $rule['when'];

            if (($rule['outcome']['stop'] ?? false) && ($when['op'] ?? null) === 'eq' && ($when['value'] ?? null) === 'yes' && is_string($when['q'] ?? null)) {
                $keys[] = $when['q'];
            }
        }

        return array_values(array_unique($keys));
    }
}
