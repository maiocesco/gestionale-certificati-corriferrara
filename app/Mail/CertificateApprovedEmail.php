<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateApprovedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Certificate $certificate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Certificato medico approvato'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.certificates.approved',
            with: [
                'firstName' => $this->certificate->user->first_name,
                'typeDescription' => $this->certificate->typeDescription(),
                'expiresAt' => $this->certificate->expires_at->format('d/m/Y'),
                'profileUrl' => route('certificates.index'),
            ],
        );
    }
}
