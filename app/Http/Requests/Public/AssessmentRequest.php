<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shape of a screening or evaluation request (SEC-006). The Clinical Rules Engine re-validates every key
 * and value against the active ruleset (unknown keys, forged values, hidden questions ⇒ 422).
 * Answers are never flashed, logged or stored (`answers` is a sensitive key — PRIV-009).
 */
class AssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'journey' => ['required', Rule::in(['concern', 'conception'])],
            'answers' => ['present', 'array', 'max:40'],
            'answers.*' => ['nullable'],
            // Seconds since the visitor started; only turned into a coarse time band counter (KPI "time to guidance").
            'elapsed_seconds' => ['sometimes', 'integer', 'min:0', 'max:86400'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_diff(array_keys($this->all()), ['journey', 'answers', 'elapsed_seconds']) as $key) {
                    $validator->errors()->add((string) $key, 'unexpected_field');
                }

                foreach ((array) $this->input('answers', []) as $key => $value) {
                    $valid = $value === null || is_string($value)
                        || (is_array($value) && array_is_list($value) && count($value) <= 10 && array_filter($value, fn (mixed $item): bool => ! is_string($item)) === []);

                    if (! is_string($key) || mb_strlen($key) > 64 || ! $valid) {
                        $validator->errors()->add('answers', 'invalid_answer_shape');

                        return;
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function answers(): array
    {
        return array_filter((array) $this->validated('answers'), fn (mixed $value): bool => $value !== null);
    }
}
