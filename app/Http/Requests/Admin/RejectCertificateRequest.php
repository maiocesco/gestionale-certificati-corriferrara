<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('certificate'));
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
