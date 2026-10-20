<?php

namespace App\Services\Mail;

use App\Enums\EmailTemplateKey;
use App\Models\EmailTemplate;
use Illuminate\Support\Str;

class EmailTemplateRenderer
{
    /**
     * Segnaposto di esempio usati per l'anteprima e l'invio di prova, così
     * ogni testo (incl. quelli non ancora collegati a un invio reale, come
     * i promemoria) si può visualizzare e testare da subito. Metodo (non
     * costante di classe) perché il valore della scadenza è calcolato.
     */
    public static function samplePlaceholders(): array
    {
        return [
            'nome' => 'Mario',
            'cognome' => 'Rossi',
            'email' => 'mario.rossi@example.com',
            'password_provvisoria' => 'Ab3$fgh2',
            'link_accesso' => 'https://esempio.test/login',
            'link_reset' => 'https://esempio.test/reset-password/token',
            'scadenza_link' => '60 minuti',
            'tipo_certificato' => 'agonistico per l\'atletica leggera',
            'attivita_consentite' => 'attività non agonistica in tutte le discipline',
            'data_scadenza' => now()->addDays(3)->format('d/m/Y'),
            'giorni_alla_scadenza' => '3',
            'motivazione' => 'il certificato allegato non è leggibile.',
            'link_profilo' => 'https://esempio.test/certificates',
        ];
    }

    public function render(EmailTemplateKey $key, array $placeholders): RenderedTemplate
    {
        $template = EmailTemplate::where('key', $key->value)->firstOrFail();

        return $this->renderTemplate($template->subject, $template->body_markdown, $placeholders);
    }

    public function renderTemplate(?string $subject, string $bodyMarkdown, array $placeholders): RenderedTemplate
    {
        $signature = EmailTemplate::where('key', EmailTemplateKey::Signature->value)->first();
        $fullMarkdown = trim($bodyMarkdown).PHP_EOL.PHP_EOL.trim($signature?->body_markdown ?? '');

        return new RenderedTemplate(
            subject: $subject ? $this->substitute($subject, $placeholders, escapeHtml: false) : null,
            html: Str::markdown($this->substitute($fullMarkdown, $placeholders, escapeHtml: true)),
            text: $this->substitute($fullMarkdown, $placeholders, escapeHtml: false),
        );
    }

    private function substitute(string $text, array $placeholders, bool $escapeHtml): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function (array $matches) use ($placeholders, $escapeHtml) {
            $value = (string) ($placeholders[$matches[1]] ?? '');

            return $escapeHtml ? e($value) : strip_tags($value);
        }, $text);
    }
}
