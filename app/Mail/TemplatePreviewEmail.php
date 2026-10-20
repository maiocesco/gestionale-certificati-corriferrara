<?php

namespace App\Mail;

use App\Enums\EmailTemplateKey;
use App\Services\Mail\EmailTemplateRenderer;

/**
 * Invia il testo attualmente salvato di un'email configurabile, con dati
 * di esempio al posto dei segnaposto: usata dal pulsante "Invia di prova
 * a me stesso" dell'editor, per qualunque email incl. quelle (promemoria,
 * diffida) il cui invio automatico arriva solo in Fase 6.
 */
class TemplatePreviewEmail extends TemplatedMailable
{
    public function __construct(
        private readonly EmailTemplateKey $key,
    ) {}

    protected function templateKey(): EmailTemplateKey
    {
        return $this->key;
    }

    protected function placeholders(): array
    {
        return EmailTemplateRenderer::samplePlaceholders();
    }
}
