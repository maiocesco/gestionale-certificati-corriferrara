<?php

namespace App\Http\Requests;

use App\Enums\CertificateType;
use App\Enums\Discipline;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('uploadCertificateFor', $this->targetUser());
    }

    public function rules(): array
    {
        return [
            'expires_at' => ['required', 'date', 'after:today'],
            'type' => [
                'required',
                Rule::enum(CertificateType::class),
                function ($attribute, $value, $fail) {
                    if ($value === CertificateType::NonCompetitive->value && $this->targetUser()->hasFidalMembership()) {
                        $fail(__('Questo atleta ha un tesseramento FIDAL: serve un certificato agonistico per l\'atletica leggera, non è possibile caricarne uno non agonistico.'));
                    }
                },
            ],
            'discipline' => [
                Rule::requiredIf(fn () => $this->input('type') === CertificateType::Competitive->value),
                'nullable',
                Rule::enum(Discipline::class),
            ],
            'file' => [
                'required',
                'file',
                'mimes:'.implode(',', config('certificates.allowed_extensions')),
                'max:'.(config('certificates.max_size_mb') * 1024),
            ],
        ];
    }

    public function targetUser(): User
    {
        return $this->route('user') ?? $this->user();
    }
}
