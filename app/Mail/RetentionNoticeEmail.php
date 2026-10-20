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
 * Avviso agli amministratori quando compaiono nuovi certificati oltre il
 * periodo di conservazione, dal comando mensile. Non fa parte dei 10
 * testi configurabili (non è un'email verso l'atleta).
 */
class RetentionNoticeEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $admin,
        public int $count,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: app(MailerConfigResolver::class)->prepare(EmailAccountPurpose::Classic),
            subject: __('Nuovi certificati oltre il periodo di conservazione'),
        );
    }

    public function content(): Content
    {
        $lines = [
            __('Ciao :nome,', ['nome' => $this->admin->first_name]),
            trans_choice(
                ':count certificato ha superato il periodo di conservazione e può essere eliminato.|:count certificati hanno superato il periodo di conservazione e possono essere eliminati.',
                $this->count,
                ['count' => $this->count]
            ),
            __('Vai all\'elenco: :url', ['url' => route('admin.certificate-deletion.index')]),
        ];

        $text = implode("\n\n", $lines);
        $html = implode('', array_map(fn (string $line) => '<p>'.e($line).'</p>', $lines));

        return (new Content(text: 'emails.plain'))
            ->htmlString($html)
            ->with('text', $text);
    }
}
