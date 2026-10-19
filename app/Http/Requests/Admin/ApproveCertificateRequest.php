<?php

namespace App\Http\Requests\Admin;

use App\Enums\CertificateType;
use App\Enums\Discipline;
use App\Models\Certificate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('certificate'));
    }

    /**
     * In fase di approvazione l'amministratore può correggere data di
     * scadenza, tipo e disciplina se l'atleta li ha inseriti in modo
     * errato.
     */
    public function rules(): array
    {
        return [
            'expires_at' => ['required', 'date', 'after:today'],
            'type' => [
                'required',
                Rule::enum(CertificateType::class),
                function ($attribute, $value, $fail) {
                    /** @var Certificate $certificate */
                    $certificate = $this->route('certificate');

                    if ($value === CertificateType::NonCompetitive->value && $certificate->user->hasFidalMembership()) {
                        $fail(__('Questo atleta ha un tesseramento FIDAL: non è possibile approvare il certificato come non agonistico.'));
                    }
                },
            ],
            'discipline' => [
                Rule::requiredIf(fn () => $this->input('type') === CertificateType::Competitive->value),
                'nullable',
                Rule::enum(Discipline::class),
            ],
        ];
    }
}
