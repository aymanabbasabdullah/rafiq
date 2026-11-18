<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && $this->user()?->can('updateRoles', $target) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var list<string> $roles */
        $roles = is_array($this->input('roles')) ? $this->input('roles') : [];
        $includesMedicalReviewer = in_array(AdminRole::MedicalReviewer->value, $roles, true);

        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(AdminRole::values())],
            'credential_reference' => [
                $includesMedicalReviewer ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
