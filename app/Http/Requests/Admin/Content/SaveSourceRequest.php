<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Content;

use App\Enums\EvidenceType;
use App\Models\Source;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $source = $this->route('source');

        return $source instanceof Source
            ? (bool) $this->user()?->can('update', $source)
            : (bool) $this->user()?->can('create', Source::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'publisher' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url:https', 'max:2048'],
            'publication_date' => ['nullable', 'string', 'max:32'],
            'accessed_at' => ['nullable', 'date'],
            'evidence_type' => ['required', Rule::in(EvidenceType::values())],
            'access_note' => ['nullable', 'string', 'max:255'],
            'ai_use_restricted' => ['boolean'],
        ];
    }
}
