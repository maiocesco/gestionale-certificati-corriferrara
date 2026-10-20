<?php

namespace App\Http\Requests\Admin;

use App\Models\RetentionSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRetentionSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', RetentionSetting::class);
    }

    public function rules(): array
    {
        return [
            'years' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
