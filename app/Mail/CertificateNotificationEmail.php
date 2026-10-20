<?php

namespace App\Mail;

use App\Enums\EmailTemplateKey;
use App\Models\Certificate;

/**
 * Promemoria di scadenza, diffida o sospensione parziale per un singolo
 * certificato. Niente ShouldQueue: viene inviata sincronamente da dentro
 * il job App\Jobs\SendCertificateNotification, che gestisce già coda e
 * tentativi ripetuti — in coda anche questa, finirebbe solo "accodata
 * dentro un job già accodato" senza che il job sappia se l'invio è andato
 * a buon fine.
 */
class CertificateNotificationEmail extends TemplatedMailable
{
    public function __construct(
        public Certificate $certificate,
        private readonly EmailTemplateKey $key,
        private readonly array $extraPlaceholders = [],
    ) {}

    protected function templateKey(): EmailTemplateKey
    {
        return $this->key;
    }

    protected function placeholders(): array
    {
        $user = $this->certificate->user;
        $daysRemaining = (int) now()->startOfDay()->diffInDays($this->certificate->expires_at, false);

        return array_merge([
            'nome' => $user->first_name,
            'cognome' => $user->last_name,
            'tipo_certificato' => $this->certificate->typeDescription(),
            'data_scadenza' => $this->certificate->expires_at->format('d/m/Y'),
            'giorni_alla_scadenza' => (string) max(0, $daysRemaining),
            'link_profilo' => route('certificates.index'),
        ], $this->extraPlaceholders);
    }
}
