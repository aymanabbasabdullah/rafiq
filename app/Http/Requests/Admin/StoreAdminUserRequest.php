<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AdminRole;
use App\Enums\Locale;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) === true;
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', Password::defaults()],
            'preferred_locale' => ['required', Rule::in(Locale::values())],
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
