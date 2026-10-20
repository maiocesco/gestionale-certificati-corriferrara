<?php

namespace App\Services\Mail;

use App\Enums\EmailAccountPurpose;
use App\Models\EmailAccount;
use Illuminate\Mail\Mailables\Address;

class MailerConfigResolver
{
    /**
     * Nome del mailer Laravel dinamico associato a questo scopo, registrato
     * a runtime (vedi prepare()) perché la configurazione vive nel database
     * e non in config/mail.php.
     */
    public function mailerName(EmailAccountPurpose $purpose): string
    {
        return 'dynamic_'.$purpose->value;
    }

    /**
     * Registra a runtime la configurazione del mailer per questo scopo
     * (dal database, con fallback sul mailer smtp di .env) e restituisce il
     * mittente da usare nella Envelope.
     */
    public function prepare(EmailAccountPurpose $purpose): Address
    {
        $account = EmailAccount::where('purpose', $purpose->value)->first();
        $mailerName = $this->mailerName($purpose);

        if ($account?->isConfigured()) {
            config(["mail.mailers.{$mailerName}" => [
                'transport' => 'smtp',
                'scheme' => $account->encryption?->scheme(),
                'host' => $account->host,
                'port' => $account->port,
                'username' => $account->username,
                'password' => $account->password,
            ]]);

            return new Address($account->from_address, $account->from_name ?: $account->from_address);
        }

        config(["mail.mailers.{$mailerName}" => config('mail.mailers.smtp')]);

        return new Address(config('mail.from.address'), config('mail.from.name'));
    }
}
