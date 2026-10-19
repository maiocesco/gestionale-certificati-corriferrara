<?php

namespace App\Http\Requests\Admin\Concerns;

use App\Models\CadastralCode;
use App\Services\FiscalCodeService;
use Carbon\Carbon;
use Closure;

trait ValidatesFiscalCodeConsistency
{
    /**
     * Regola che verifica che il codice fiscale sia coerente con nome,
     * cognome, data e luogo di nascita indicati nello stesso form.
     */
    protected function fiscalCodeConsistencyRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || $value === '') {
                return;
            }

            $birthDate = $this->input('birth_date');
            $cadastralCodeId = $this->input('birth_cadastral_code_id');

            if (! $birthDate || ! $cadastralCodeId || ! $this->input('first_name') || ! $this->input('last_name')) {
                return;
            }

            $cadastralCode = CadastralCode::find($cadastralCodeId);

            if (! $cadastralCode) {
                return;
            }

            try {
                $consistent = app(FiscalCodeService::class)->isConsistent(
                    $this->input('first_name'),
                    $this->input('last_name'),
                    Carbon::parse($birthDate),
                    $cadastralCode,
                    $value,
                );
            } catch (\Throwable) {
                $consistent = false;
            }

            if (! $consistent) {
                $fail(__('Il codice fiscale non è coerente con nome, cognome, data e luogo di nascita inseriti.'));
            }
        };
    }
}
