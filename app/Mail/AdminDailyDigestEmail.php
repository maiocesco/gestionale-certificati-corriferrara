<?php

namespace App\Mail;

use App\Enums\EmailAccountPurpose;
use App\Models\User;
use App\Services\Mail\MailerConfigResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Riepilogo giornaliero per gli amministratori che l'hanno attivato dal
 * proprio profilo. Non fa parte dei 10 testi configurabili della
 * specifica (non è un'email "verso l'atleta"), quindi non passa dal
 * sistema di template del database.
 */
class AdminDailyDigestEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $admin,
        public int $pendingCount,
        public int $expiringCount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: app(MailerConfigResolver::class)->prepare(EmailAccountPurpose::Classic),
            subject: __('Riepilogo giornaliero certificati'),
        );
    }

    public function content(): Content
    {
        $lines = [__('Ciao :nome,', ['nome' => $this->admin->first_name])];

        if ($this->pendingCount > 0) {
            $lines[] = trans_choice(
                ':count certificato in attesa di approvazione.|:count certificati in attesa di approvazione.',
                $this->pendingCount,
                ['count' => $this->pendingCount]
            );
        }

        if ($this->expiringCount > 0) {
            $lines[] = trans_choice(
                ':count certificato in scadenza nei prossimi 30 giorni.|:count certificati in scadenza nei prossimi 30 giorni.',
                $this->expiringCount,
                ['count' => $this->expiringCount]
            );
        }

        $lines[] = __('Vai alla bacheca: :url', ['url' => route('dashboard')]);

        $text = implode("\n\n", $lines);
        $html = implode('', array_map(fn (string $line) => '<p>'.e($line).'</p>', $lines));

        return (new Content(text: 'emails.plain'))
            ->htmlString($html)
            ->with('text', $text);
    }
}
