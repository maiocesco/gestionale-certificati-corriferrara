<?php

namespace App\Mail;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CertificateRejectedEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Certificate $certificate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Certificato medico non approvato'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.certificates.rejected',
            with: [
                'firstName' => $this->certificate->user->first_name,
                'motivazione' => $this->certificate->rejection_reason,
                'profileUrl' => route('certificates.index'),
            ],
        );
    }
}
