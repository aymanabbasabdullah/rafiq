<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use App\Enums\Priority;

/**
 * Strict validation of ruleset files (M06-FR-01) without an external JSON Schema library:
 * structure, allowed operators and values, references between rules, questions and guidance texts,
 * and the forbidden-phrase check (M06-FR-08). Returns human-readable errors; empty means valid.
 */
final class RulesetSchemaValidator
{
    private const QUESTION_TYPES = ['boolean_unsure', 'single', 'multi', 'band'];

    private const RULE_SECTIONS = ['gate', 'safety', 'assessment'];

    private const SUPPORT = ['source', 'inference', 'review'];

    private const CONTEXT_KEYS = ['@journey', '@age_confirmed'];

    /** @var list<string> */
    private array $errors = [];

    /** @var array<string, array<string, mixed>> */
    private array $questions = [];

    public function __construct(private readonly ?ForbiddenPhraseChecker $phrases = null) {}

    /**
     * @param  list<array<string, mixed>>  $questions
     * @param  list<array<string, mixed>>  $rules
     * @param  array<string, array<string, string>>  $guidance
     * @return list<string>
     */
    public function validate(array $questions, array $rules, array $guidance): array
    {
        $this->errors = [];
        $this->questions = [];

        foreach ($questions as $index => $question) {
            $this->validateQuestion($question, "questions[{$index}]");
        }

        $codes = [];

        foreach ($rules as $index => $rule) {
            $path = 'rules['.(is_string($rule['code'] ?? null) ? $rule['code'] : $index).']';

            if (in_array($rule['code'] ?? null, $codes, true)) {
                $this->errors[] = "{$path}: duplicate code";
            }

            $codes[] = $rule['code'] ?? null;
            $this->validateRule($rule, $path, $guidance);
        }

        $this->validateGuidance($guidance);

        return $this->errors;
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function validateQuestion(array $question, string $path): void
    {
        $key = $question['key'] ?? null;

        if (! is_string($key) || preg_match('/^(ss|as)_[a-z0-9_]+$/', $key) !== 1) {
            $this->errors[] = "{$path}: key must match (ss|as)_snake_case";

            return;
        }

        $path = "questions[{$key}]";

        if (isset($this->questions[$key])) {
            $this->errors[] = "{$path}: duplicate key";
        }

        $section = $question['section'] ?? null;
        $this->check(in_array($section, ['safety', 'assessment'], true), "{$path}: section must be safety or assessment");
        $this->check(str_starts_with($key, $section === 'safety' ? 'ss_' : 'as_'), "{$path}: key prefix does not match section");
        $this->checkJourneys($question['journeys'] ?? null, $path);
        $this->check(in_array($question['type'] ?? null, self::QUESTION_TYPES, true), "{$path}: unknown type");

        $options = $question['options'] ?? null;
        $validOptions = is_array($options) && $options !== [] && array_is_list($options)
            && count(array_unique($options)) === count($options)
            && array_filter($options, fn (mixed $option): bool => ! is_string($option)) === [];
        $this->check($validOptions, "{$path}: options must be a non-empty list of unique strings");

        if (($question['type'] ?? null) === 'boolean_unsure') {
            $this->check($options === ['yes', 'no', 'unsure'], "{$path}: boolean_unsure options must be yes/no/unsure");
        }

        foreach (['exclusive', 'unknown_values'] as $subset) {
            if (isset($question[$subset])) {
                $this->check(is_array($question[$subset]) && $validOptions && array_diff($question[$subset], $options) === [], "{$path}: {$subset} must be a subset of options");
            }
        }

        $this->check(is_bool($question['required'] ?? null), "{$path}: required must be boolean");
        $this->check($section !== 'safety' || ($question['required'] ?? false) === true, "{$path}: safety questions must be required");
        $this->checkTranslations($question['prompt'] ?? null, "{$path}.prompt");

        $this->questions[$key] = $question;

        if (($question['show_if'] ?? null) !== null) {
            $this->checkCondition($question['show_if'], "{$path}.show_if");
        }
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, array<string, string>>  $guidance
     */
    private function validateRule(array $rule, string $path, array $guidance): void
    {
        $this->check(is_string($rule['code'] ?? null) && preg_match('/^(CR-\d{3}[a-z]?|SAFE-\d{3})$/', $rule['code']) === 1, "{$path}: code must look like CR-002 or SAFE-006");
        $this->check(is_int($rule['version'] ?? null) && $rule['version'] >= 1, "{$path}: version must be a positive integer");
        $this->check(is_string($rule['title'] ?? null) && $rule['title'] !== '', "{$path}: title is required");
        $this->check(in_array($rule['section'] ?? null, self::RULE_SECTIONS, true), "{$path}: unknown section");
        $this->check(in_array($rule['support'] ?? null, self::SUPPORT, true), "{$path}: support must be source, inference or review");
        $this->check(($rule['status'] ?? null) === 'draft', "{$path}: files may only contain draft rules; approval happens in the review workflow");
        $this->check(! isset($rule['enabled']) || is_bool($rule['enabled']), "{$path}: enabled must be boolean");
        $this->checkJourneys($rule['journeys'] ?? null, $path);
        $this->check(is_array($rule['evidence'] ?? null), "{$path}: evidence must be a list");

        $hasSingle = isset($rule['when']) || isset($rule['outcome']);
        $hasBranches = isset($rule['branches']);

        if ($hasSingle === $hasBranches) {
            $this->errors[] = "{$path}: use either when/outcome or branches";

            return;
        }

        $branches = $hasBranches ? $rule['branches'] : [['when' => $rule['when'] ?? null, 'outcome' => $rule['outcome'] ?? null]];

        if (! is_array($branches) || $branches === [] || ! array_is_list($branches)) {
            $this->errors[] = "{$path}: branches must be a non-empty list";

            return;
        }

        foreach ($branches as $index => $branch) {
            $branchPath = $hasBranches ? "{$path}.branches[{$index}]" : $path;
            $this->checkCondition($branch['when'] ?? null, "{$branchPath}.when");
            $this->checkOutcome($branch['outcome'] ?? null, (string) ($rule['section'] ?? ''), $branchPath);

            foreach (['guidance_keys', 'doctor_topic_keys'] as $list) {
                $keys = $branch[$list] ?? $rule[$list] ?? [];
                $this->check(is_array($keys), "{$branchPath}: {$list} must be a list");

                foreach ((array) $keys as $key) {
                    foreach (['ar', 'en'] as $locale) {
                        $this->check(is_string($key) && ($guidance[$locale][$key] ?? '') !== '', "{$branchPath}: {$list} \"".(is_string($key) ? $key : '?')."\" has no {$locale} text");
                    }
                }
            }
        }

        if (! $hasBranches) {
            $this->check(is_array($rule['guidance_keys'] ?? null), "{$path}: guidance_keys is required");
        }
    }

    private function checkOutcome(mixed $outcome, string $section, string $path): void
    {
        if (! is_array($outcome)) {
            $this->errors[] = "{$path}: outcome is required";

            return;
        }

        $priority = Priority::tryFrom((string) ($outcome['priority'] ?? ''));
        $this->check($priority !== null, "{$path}: unknown priority");
        $this->check(($priority === Priority::Gate) === ($section === 'gate'), "{$path}: GATE is only for the gate section");
        $this->check(! ($outcome['stop'] ?? false) || in_array($priority, [Priority::P0, Priority::Gate], true), "{$path}: only P0 or GATE may stop the flow");

        foreach ((array) ($outcome['priority_by_journey'] ?? []) as $journey => $override) {
            $this->check(in_array($journey, ClinicalRulesEngine::JOURNEYS, true) && Priority::tryFrom((string) $override) !== null, "{$path}: invalid priority_by_journey");
        }

        foreach ((array) ($outcome['flags'] ?? []) as $flag) {
            $this->check(is_string($flag) && preg_match('/^[a-z_]+$/', $flag) === 1, "{$path}: flags must be snake_case strings");
        }
    }

    private function checkCondition(mixed $condition, string $path): void
    {
        if (! is_array($condition) || $condition === []) {
            $this->errors[] = "{$path}: condition must be an object";

            return;
        }

        foreach (['all', 'any'] as $combinator) {
            if (array_key_exists($combinator, $condition)) {
                $children = $condition[$combinator];

                if (! is_array($children) || $children === [] || ! array_is_list($children) || count($condition) !== 1) {
                    $this->errors[] = "{$path}: {$combinator} needs a non-empty list and nothing else";

                    return;
                }

                foreach ($children as $index => $child) {
                    $this->checkCondition($child, "{$path}.{$combinator}[{$index}]");
                }

                return;
            }
        }

        if (array_key_exists('not', $condition)) {
            $this->check(count($condition) === 1, "{$path}: not takes a single condition");
            $this->checkCondition($condition['not'], "{$path}.not");

            return;
        }

        $key = $condition['q'] ?? null;
        $operator = $condition['op'] ?? null;
        $extra = array_diff(array_keys($condition), ['q', 'op', 'value']);

        if ($extra !== [] || ! is_string($key) || ! in_array($operator, ConditionEvaluator::OPERATORS, true)) {
            $this->errors[] = "{$path}: leaf needs q, a known op, and optional value only";

            return;
        }

        if (in_array($key, self::CONTEXT_KEYS, true)) {
            $this->check(in_array($operator, ['eq', 'neq', 'in', 'not_in'], true), "{$path}: {$key} only supports eq, neq, in, not_in");

            return;
        }

        $question = $this->questions[$key] ?? null;

        if ($question === null) {
            $this->errors[] = "{$path}: unknown question \"{$key}\"";

            return;
        }

        $options = (array) ($question['options'] ?? []);
        $value = $condition['value'] ?? null;

        $valid = match ($operator) {
            'eq', 'neq' => is_string($value) && in_array($value, $options, true),
            'in', 'not_in', 'contains_any' => is_array($value) && $value !== [] && array_diff($value, $options) === [],
            'contains' => $question['type'] === 'multi' && is_string($value) && in_array($value, $options, true),
            'is_unsure' => in_array('unsure', $options, true) && $value === null,
            'is_answered', 'is_unanswered' => $value === null,
            'band_gte', 'band_lte' => $question['type'] === 'band' && is_string($value) && in_array($value, $options, true),
            default => false,
        };

        $this->check($valid, "{$path}: value is not valid for {$operator} on \"{$key}\"");

        if (in_array($operator, ['contains', 'contains_any'], true)) {
            $this->check($question['type'] === 'multi', "{$path}: {$operator} only applies to multi questions");
        }
    }

    /**
     * @param  array<string, array<string, string>>  $guidance
     */
    private function validateGuidance(array $guidance): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->check(isset($guidance[$locale]) && $guidance[$locale] !== [], "guidance/{$locale}.json has no texts");
        }

        $this->check($this->sameKeys($guidance), 'guidance: ar and en must define the same keys');

        if ($this->phrases === null) {
            return;
        }

        foreach (['ar', 'en'] as $locale) {
            foreach ($guidance[$locale] ?? [] as $key => $text) {
                foreach ($this->phrases->find((string) $text, $locale) as $phrase) {
                    $this->errors[] = "guidance/{$locale}.json [{$key}]: forbidden phrase \"{$phrase}\" (SAFE-010)";
                }
            }

            foreach ($this->questions as $key => $question) {
                foreach ($this->phrases->find((string) ($question['prompt'][$locale] ?? ''), $locale) as $phrase) {
                    $this->errors[] = "questions[{$key}].prompt.{$locale}: forbidden phrase \"{$phrase}\" (SAFE-010)";
                }
            }
        }
    }

    /**
     * @param  array<string, array<string, string>>  $guidance
     */
    private function sameKeys(array $guidance): bool
    {
        $ar = array_keys($guidance['ar'] ?? []);
        $en = array_keys($guidance['en'] ?? []);
        sort($ar);
        sort($en);

        return $ar === $en;
    }

    private function checkJourneys(mixed $journeys, string $path): void
    {
        $this->check(
            is_array($journeys) && $journeys !== [] && array_diff($journeys, ClinicalRulesEngine::JOURNEYS) === [],
            "{$path}: journeys must be a non-empty subset of concern, conception",
        );
    }

    private function checkTranslations(mixed $value, string $path): void
    {
        foreach (['ar', 'en'] as $locale) {
            $this->check(is_array($value) && is_string($value[$locale] ?? null) && $value[$locale] !== '', "{$path}: {$locale} text is required");
        }
    }

    private function check(bool $condition, string $message): void
    {
        if (! $condition) {
            $this->errors[] = $message;
        }
    }
}
