<?php

namespace App\Enums;

enum SmtpEncryption: string
{
    case Ssl = 'ssl';
    case Tls = 'tls';
    case Starttls = 'starttls';

    public function label(): string
    {
        return match ($this) {
            self::Ssl => 'SSL',
            self::Tls => 'TLS',
            self::Starttls => 'STARTTLS',
        };
    }

    /**
     * Schema di connessione per il mailer SMTP di Laravel/Symfony. SSL e TLS
     * sono entrambi cifratura implicita fin dalla connessione (porta tipica
     * 465): nessuna distinzione reale lato Symfony, quindi condividono lo
     * schema "smtps". STARTTLS parte in chiaro e aggiorna la connessione
     * (porta tipica 587): schema predefinito (null), negoziato da Symfony.
     */
    public function scheme(): ?string
    {
        return match ($this) {
            self::Ssl, self::Tls => 'smtps',
            self::Starttls => null,
        };
    }
}
