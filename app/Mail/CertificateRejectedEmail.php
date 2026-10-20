<?php

namespace App\Mail;

use App\Enums\EmailTemplateKey;
use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CertificateRejectedEmail extends TemplatedMailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Certificate $certificate,
    ) {}

    protected function templateKey(): EmailTemplateKey
    {
        return EmailTemplateKey::CertificateRejected;
    }

    protected function placeholders(): array
    {
        return [
            'nome' => $this->certificate->user->first_name,
            'motivazione' => $this->certificate->rejection_reason,
            'link_profilo' => route('certificates.index'),
        ];
    }
}
