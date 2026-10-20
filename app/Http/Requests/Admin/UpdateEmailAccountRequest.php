<?php

namespace App\Http\Requests\Admin;

use App\Enums\SmtpEncryption;
use App\Models\EmailAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', EmailAccount::class);
    }

    public function rules(): array
    {
        return [
            'from_address' => ['nullable', 'email', 'max:255', 'required_with:host'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'host' => ['nullable', 'string', 'max:255', 'required_with:from_address'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535', 'required_with:host'],
            'encryption' => ['nullable', Rule::enum(SmtpEncryption::class), 'required_with:host'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
