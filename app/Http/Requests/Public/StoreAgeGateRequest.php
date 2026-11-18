<?php

declare(strict_types=1);

namespace App\Http\Requests\Public;

use App\Support\GateRedirects;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgeGateRequest extends FormRequest
{
    /**
     * The age gate is public.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only a yes/no answer — never a birth date or numeric age (BR-03-1).
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_adult' => ['required', 'boolean'],
            'intended' => ['nullable', 'string', Rule::in(GateRedirects::INTENDED_ROUTES)],
        ];
    }
}
