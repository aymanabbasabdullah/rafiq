<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Knowledge Center search. Sent as POST so the query never appears in URLs or browser history (PRIV-003).
 */
class SearchKnowledgeRequest extends FormRequest
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
            'query' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }
}
