<?php

namespace Tests\Unit;

use App\Services\Clinical\ConditionEvaluator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Every operator and combinator of the rule language (M06-FR-04), including unanswered and hidden questions.
 */
class ConditionEvaluatorTest extends TestCase
{
    private function evaluator(): ConditionEvaluator
    {
        return new ConditionEvaluator(['as_trying_duration' => ['not_trying', 'lt_6m', '6_12m', 'gt_12m']]);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: bool}>
     */
    public static function leafCases(): array
    {
        return [
            'eq matches' => [['q' => 'a', 'op' => 'eq', 'value' => 'yes'], ['a' => 'yes'], true],
            'eq does not match another value' => [['q' => 'a', 'op' => 'eq', 'value' => 'yes'], ['a' => 'no'], false],
            'eq never matches unanswered' => [['q' => 'a', 'op' => 'eq', 'value' => 'yes'], [], false],
            'eq never matches not_applicable' => [['q' => 'a', 'op' => 'eq', 'value' => 'not_applicable'], ['a' => 'not_applicable'], false],
            'neq on another value' => [['q' => 'a', 'op' => 'neq', 'value' => 'yes'], ['a' => 'no'], true],
            'neq on missing value' => [['q' => '@age_confirmed', 'op' => 'neq', 'value' => true], [], true],
            'neq on equal boolean' => [['q' => '@age_confirmed', 'op' => 'neq', 'value' => true], ['@age_confirmed' => true], false],
            'in matches' => [['q' => 'a', 'op' => 'in', 'value' => ['current', 'past']], ['a' => 'past'], true],
            'in misses' => [['q' => 'a', 'op' => 'in', 'value' => ['current', 'past']], ['a' => 'never'], false],
            'in ignores unanswered' => [['q' => 'a', 'op' => 'in', 'value' => ['current']], ['a' => null], false],
            'not_in matches' => [['q' => 'a', 'op' => 'not_in', 'value' => ['current']], ['a' => 'never'], true],
            'not_in ignores unanswered' => [['q' => 'a', 'op' => 'not_in', 'value' => ['current']], [], false],
            'contains matches multi' => [['q' => 'm', 'op' => 'contains', 'value' => 'x'], ['m' => ['x', 'y']], true],
            'contains on scalar is false' => [['q' => 'm', 'op' => 'contains', 'value' => 'x'], ['m' => 'x'], false],
            'contains_any matches' => [['q' => 'm', 'op' => 'contains_any', 'value' => ['x', 'z']], ['m' => ['z']], true],
            'contains_any misses' => [['q' => 'm', 'op' => 'contains_any', 'value' => ['x']], ['m' => ['none']], false],
            'is_unsure matches' => [['q' => 'a', 'op' => 'is_unsure'], ['a' => 'unsure'], true],
            'is_unsure misses' => [['q' => 'a', 'op' => 'is_unsure'], ['a' => 'yes'], false],
            'is_answered' => [['q' => 'a', 'op' => 'is_answered'], ['a' => 'no'], true],
            'empty list is unanswered' => [['q' => 'm', 'op' => 'is_answered'], ['m' => []], false],
            'is_unanswered on not_applicable' => [['q' => 'a', 'op' => 'is_unanswered'], ['a' => 'not_applicable'], true],
            'band_gte equal' => [['q' => 'as_trying_duration', 'op' => 'band_gte', 'value' => '6_12m'], ['as_trying_duration' => '6_12m'], true],
            'band_gte higher' => [['q' => 'as_trying_duration', 'op' => 'band_gte', 'value' => '6_12m'], ['as_trying_duration' => 'gt_12m'], true],
            'band_gte lower' => [['q' => 'as_trying_duration', 'op' => 'band_gte', 'value' => '6_12m'], ['as_trying_duration' => 'lt_6m'], false],
            'band_gte unanswered' => [['q' => 'as_trying_duration', 'op' => 'band_gte', 'value' => 'not_trying'], [], false],
            'band_lte lower' => [['q' => 'as_trying_duration', 'op' => 'band_lte', 'value' => '6_12m'], ['as_trying_duration' => 'lt_6m'], true],
            'band_lte unanswered' => [['q' => 'as_trying_duration', 'op' => 'band_lte', 'value' => 'gt_12m'], [], false],
            'band ignores unordered values' => [['q' => 'as_trying_duration', 'op' => 'band_gte', 'value' => 'lt_6m'], ['as_trying_duration' => 'prefer_not'], false],
        ];
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  array<string, mixed>  $answers
     */
    #[DataProvider('leafCases')]
    public function test_operators(array $condition, array $answers, bool $expected): void
    {
        $this->assertSame($expected, $this->evaluator()->matches($condition, $answers));
    }

    public function test_combinators(): void
    {
        $yes = ['q' => 'a', 'op' => 'eq', 'value' => 'yes'];
        $no = ['q' => 'b', 'op' => 'eq', 'value' => 'yes'];
        $answers = ['a' => 'yes', 'b' => 'no'];
        $evaluator = $this->evaluator();

        $this->assertTrue($evaluator->matches(['all' => [$yes]], $answers));
        $this->assertFalse($evaluator->matches(['all' => [$yes, $no]], $answers));
        $this->assertTrue($evaluator->matches(['any' => [$no, $yes]], $answers));
        $this->assertFalse($evaluator->matches(['any' => [$no]], $answers));
        $this->assertTrue($evaluator->matches(['not' => $no], $answers));
        $this->assertTrue($evaluator->matches(['all' => [$yes, ['not' => ['any' => [$no]]]]], $answers));
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function malformedConditions(): array
    {
        return [
            'unknown operator' => [['q' => 'a', 'op' => 'regex', 'value' => '.*']],
            'missing op' => [['q' => 'a']],
            'empty all' => [['all' => []]],
            'non-list any' => [['any' => ['x' => ['q' => 'a', 'op' => 'is_answered']]]],
            'not without condition' => [['not' => 'a']],
            'in without list' => [['q' => 'a', 'op' => 'in', 'value' => 'yes']],
            'band on a non-band question' => [['q' => 'a', 'op' => 'band_gte', 'value' => 'x']],
            'unknown band value' => [['q' => 'as_trying_duration', 'op' => 'band_gte', 'value' => 'forever']],
        ];
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    #[DataProvider('malformedConditions')]
    public function test_malformed_conditions_throw_instead_of_guessing(array $condition): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->evaluator()->matches($condition, ['a' => 'yes']);
    }
}
