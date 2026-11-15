<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Enums\Journey;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsentRequest extends FormRequest
{
    /**
     * Consent is given by public visitors who passed the age gate (enforced by middleware).
     */
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
            'journey' => ['required', Rule::in([Journey::Concern->value, Journey::Conception->value])],
            'accepted' => ['accepted'],
        ];
    }
}
