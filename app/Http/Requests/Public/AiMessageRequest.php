<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\AiMode;
use App\Services\Ai\AiSettings;
use App\Services\Ai\GroundingRetriever;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * One AI companion message (M09-FR-02). Only these keys are accepted: assessment answers, reports
 * or any other field are rejected outright (GR-05, SEC-006).
 */
class AiMessageRequest extends FormRequest
{
    private const ALLOWED_KEYS = ['mode', 'message', 'article_id', 'term_id', 'preset', 'history', 'simplification'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $limits = app(AiSettings::class)->limits();

        return [
            'mode' => ['required', Rule::in(AiMode::values())],
            'message' => ['required', 'string', 'min:2', 'max:'.$limits['message_max_chars']],
            'article_id' => ['nullable', 'integer', Rule::requiredIf($this->input('mode') === AiMode::ExplainArticle->value)],
            'term_id' => ['nullable', 'integer', Rule::requiredIf($this->input('mode') === AiMode::ExplainTerm->value)],
            'preset' => ['nullable', Rule::in(['summarize', 'simplify'])],
            'history' => ['nullable', 'array', 'max:'.$limits['history_max_items']],
            'history.*.role' => ['required', Rule::in(['user', 'assistant'])],
            'history.*.text' => ['required', 'string', 'max:'.max($limits['message_max_chars'], $limits['answer_max_chars'])],
            'simplification' => ['nullable', 'integer', 'between:1,3'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_diff(array_keys($this->all()), self::ALLOWED_KEYS) as $key) {
                    $validator->errors()->add((string) $key, 'unexpected_field');
                }

                $retriever = app(GroundingRetriever::class);
                $locale = App::getLocale();

                if ($this->filled('article_id') && ! $retriever->articleIsAvailable($locale, (int) $this->input('article_id'))) {
                    $validator->errors()->add('article_id', 'not_available');
                }

                if ($this->filled('term_id') && ! $retriever->termIsAvailable($locale, (int) $this->input('term_id'))) {
                    $validator->errors()->add('term_id', 'not_available');
                }
            },
        ];
    }
}
