<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Clinical;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Medical approval and rejection always carry a comment (Clinical-Review-Process §3–§4).
 * Who may act is decided by the policies and the four-eyes gate.
 */
class ClinicalDecisionRequest extends FormRequest
{
    private const COMMENTED_ACTIONS = ['approve', 'reject'];

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
            'comment' => [in_array($this->route('action'), self::COMMENTED_ACTIONS, true) ? 'required' : 'nullable', 'string', 'max:2000'],
        ];
    }
}
