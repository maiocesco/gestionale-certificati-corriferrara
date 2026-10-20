<?php

namespace App\Mail;

use App\Enums\EmailTemplateKey;
use App\Services\Mail\EmailTemplateRenderer;
use App\Services\Mail\MailerConfigResolver;
use App\Services\Mail\RenderedTemplate;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Niente ShouldQueue qui: alcune sottoclassi (gli invii transazionali veri)
 * la aggiungono da sole, altre (anteprime/invii di prova dal pannello, che
 * devono dare un esito immediato all'amministratore) restano sincrone.
 */
abstract class TemplatedMailable extends Mailable
{
    use SerializesModels;

    private ?RenderedTemplate $rendered = null;

    abstract protected function templateKey(): EmailTemplateKey;

    /**
     * @return array<string, string>
     */
    abstract protected function placeholders(): array;

    /**
     * Niente iniezione di dipendenze nella firma: Mailable::hasTo() e simili
     * chiamano envelope() direttamente, senza passare dal container.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: app(MailerConfigResolver::class)->prepare($this->templateKey()->mailerPurpose()),
            subject: $this->rendered()->subject,
        );
    }

    public function content(): Content
    {
        return (new Content(text: 'emails.plain'))
            ->htmlString($this->rendered()->html)
            ->with('text', $this->rendered()->text);
    }

    private function rendered(): RenderedTemplate
    {
        return $this->rendered ??= app(EmailTemplateRenderer::class)->render($this->templateKey(), $this->placeholders());
    }
}
