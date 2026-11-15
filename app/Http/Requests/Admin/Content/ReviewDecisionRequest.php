<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Optional note for approval; required comment for rejection (Clinical-Review-Process §3).
 * Who may act is decided by the workflow (four-eyes gate) and the article policy.
 */
class ReviewDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'comment' => [$this->route('action') === 'reject' ? 'required' : 'nullable', 'string', 'max:2000'],
        ];
    }
}
