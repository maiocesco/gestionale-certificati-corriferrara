<?php

namespace App\Mail;

use App\Enums\EmailTemplateKey;
use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CertificateApprovedEmail extends TemplatedMailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Certificate $certificate,
    ) {}

    protected function templateKey(): EmailTemplateKey
    {
        return EmailTemplateKey::CertificateApproved;
    }

    protected function placeholders(): array
    {
        return [
            'nome' => $this->certificate->user->first_name,
            'tipo_certificato' => $this->certificate->typeDescription(),
            'data_scadenza' => $this->certificate->expires_at->format('d/m/Y'),
            'link_profilo' => route('certificates.index'),
        ];
    }
}
