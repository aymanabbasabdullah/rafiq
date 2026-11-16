<?php

declare(strict_types=1);

namespace App\Services\Clinical;

use InvalidArgumentException;

/**
 * Evaluates a rule condition (Clinical-Safety-Framework §4) against a set of answers.
 *
 * Pure and deterministic: no facades, clock, randomness or locale (BR-06-7). Unanswered questions are
 * `null`; questions hidden by `show_if` are `not_applicable`. Neither ever satisfies a positive operator.
 */
final class ConditionEvaluator
{
    public const NOT_APPLICABLE = 'not_applicable';

    public const OPERATORS = ['eq', 'neq', 'in', 'not_in', 'contains', 'contains_any', 'is_unsure', 'is_answered', 'is_unanswered', 'band_gte', 'band_lte'];

    public const COMBINATORS = ['all', 'any', 'not'];

    /**
     * @param  array<string, list<string>>  $bandOrders  Ordered options of `band` questions, keyed by question.
     */
    public function __construct(private readonly array $bandOrders = []) {}

    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, mixed>  $answers
     */
    public function matches(array $condition, array $answers): bool
    {
        if (array_key_exists('all', $condition)) {
            foreach ($this->children($condition['all']) as $child) {
                if (! $this->matches($child, $answers)) {
                    return false;
                }
            }

            return true;
        }

        if (array_key_exists('any', $condition)) {
            foreach ($this->children($condition['any']) as $child) {
                if ($this->matches($child, $answers)) {
                    return true;
                }
            }

            return false;
        }

        if (array_key_exists('not', $condition)) {
            if (! is_array($condition['not'])) {
                throw new InvalidArgumentException('"not" expects a condition.');
            }

            return ! $this->matches($condition['not'], $answers);
        }

        return $this->leaf($condition, $answers);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function children(mixed $children): array
    {
        if (! is_array($children) || $children === [] || ! array_is_list($children)) {
            throw new InvalidArgumentException('Combinators expect a non-empty list of conditions.');
        }

        return $children;
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, mixed>  $answers
     */
    private function leaf(array $condition, array $answers): bool
    {
        $key = $condition['q'] ?? null;
        $operator = $condition['op'] ?? null;

        if (! is_string($key) || ! is_string($operator)) {
            throw new InvalidArgumentException('A condition needs "q" and "op".');
        }

        $answer = $answers[$key] ?? null;
        $expected = $condition['value'] ?? null;
        $answered = self::isAnswered($answer);

        return match ($operator) {
            'eq' => $answered && $answer === $expected,
            'neq' => $answer !== $expected,
            'in' => $answered && is_scalar($answer) && in_array($answer, $this->list($expected), true),
            'not_in' => $answered && is_scalar($answer) && ! in_array($answer, $this->list($expected), true),
            'contains' => is_array($answer) && in_array($expected, $answer, true),
            'contains_any' => is_array($answer) && array_intersect($this->list($expected), $answer) !== [],
            'is_unsure' => $answer === 'unsure',
            'is_answered' => $answered,
            'is_unanswered' => ! $answered,
            'band_gte' => $this->compareBand($key, $answer, $expected) >= 0,
            'band_lte' => $answered && $this->compareBand($key, $answer, $expected) <= 0,
            default => throw new InvalidArgumentException("Unknown operator \"{$operator}\"."),
        };
    }

    public static function isAnswered(mixed $answer): bool
    {
        if ($answer === null || $answer === self::NOT_APPLICABLE) {
            return false;
        }

        return ! is_array($answer) || $answer !== [];
    }

    /**
     * @return list<mixed>
     */
    private function list(mixed $expected): array
    {
        if (! is_array($expected) || ! array_is_list($expected)) {
            throw new InvalidArgumentException('This operator expects a list value.');
        }

        return $expected;
    }

    /**
     * Position of the answer relative to the expected band; -1 when the answer is missing or not a band.
     */
    private function compareBand(string $key, mixed $answer, mixed $expected): int
    {
        $order = $this->bandOrders[$key] ?? null;

        if ($order === null) {
            throw new InvalidArgumentException("\"{$key}\" is not a band question.");
        }

        $expectedPosition = array_search($expected, $order, true);

        if ($expectedPosition === false) {
            throw new InvalidArgumentException("\"{$key}\" has no band \"".(is_scalar($expected) ? (string) $expected : '?').'".');
        }

        $answerPosition = is_string($answer) ? array_search($answer, $order, true) : false;

        if ($answerPosition === false) {
            return -1;
        }

        return $answerPosition <=> $expectedPosition;
    }
}
