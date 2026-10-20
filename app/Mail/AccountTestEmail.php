<?php

namespace App\Mail;

use App\Enums\EmailAccountPurpose;
use App\Services\Mail\MailerConfigResolver;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Niente ShouldQueue: l'amministratore che preme "Invia email di prova"
 * deve avere un esito (riuscito/fallito) subito, non al prossimo giro
 * dello scheduler della coda.
 */
class AccountTestEmail extends Mailable
{
    public function __construct(
        public EmailAccountPurpose $purpose,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: app(MailerConfigResolver::class)->prepare($this->purpose),
            subject: __('Email di prova – gestionale certificati Corriferrara'),
        );
    }

    public function content(): Content
    {
        $text = __('Questa è un\'email di prova per verificare la configurazione dell\'account :purpose.', [
            'purpose' => $this->purpose->label(),
        ]);

        return (new Content(text: 'emails.plain'))
            ->htmlString('<p>'.e($text).'</p>')
            ->with('text', $text);
    }
}
