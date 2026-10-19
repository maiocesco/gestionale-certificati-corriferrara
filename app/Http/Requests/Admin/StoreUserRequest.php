<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Http\Requests\Admin\Concerns\ValidatesFiscalCodeConsistency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use ValidatesFiscalCodeConsistency;

    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'birth_date' => ['required', 'date', 'before:today'],
            'birth_cadastral_code_id' => ['required', 'exists:cadastral_codes,id'],
            'fiscal_code' => [
                'required', 'string', 'size:16', 'unique:users,fiscal_code',
                $this->fiscalCodeConsistencyRule(),
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
            'training_location_id' => ['nullable', 'exists:training_locations,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fiscal_code' => $this->fiscal_code ? strtoupper(trim($this->fiscal_code)) : null,
        ]);
    }
}
