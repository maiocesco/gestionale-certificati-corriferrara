<?php

namespace App\Enums;

enum EmailTemplateKey: string
{
    case Welcome = 'welcome';
    case PasswordResetByAdmin = 'password_reset_by_admin';
    case PasswordReset = 'password_reset';
    case CertificateApproved = 'certificate_approved';
    case CertificateRejected = 'certificate_rejected';
    case Reminder30 = 'reminder_30';
    case Reminder10 = 'reminder_10';
    case Reminder3 = 'reminder_3';
    case ExpiryNotice = 'expiry_notice';
    case PartialSuspension = 'partial_suspension';
    case Signature = 'signature';

    public function label(): string
    {
        return match ($this) {
            self::Welcome => __('Benvenuto'),
            self::PasswordResetByAdmin => __('Password reimpostata dall\'amministratore'),
            self::PasswordReset => __('Recupero password'),
            self::CertificateApproved => __('Certificato approvato'),
            self::CertificateRejected => __('Certificato rifiutato'),
            self::Reminder30 => __('Promemoria scadenza – 30 giorni'),
            self::Reminder10 => __('Promemoria scadenza – 10 giorni'),
            self::Reminder3 => __('Promemoria scadenza – 3 giorni'),
            self::ExpiryNotice => __('Diffida – giorno della scadenza'),
            self::PartialSuspension => __('Sospensione parziale – giorno della scadenza'),
            self::Signature => __('Firma comune'),
        };
    }

    /**
     * Nessuna per la firma: è un ritaglio di testo comune, non un'email a
     * se stante, quindi non ha un account di invio proprio.
     */
    public function mailerPurpose(): ?EmailAccountPurpose
    {
        return match ($this) {
            self::Welcome, self::PasswordResetByAdmin, self::PasswordReset,
            self::CertificateApproved, self::CertificateRejected => EmailAccountPurpose::Classic,
            self::Reminder30, self::Reminder10, self::Reminder3,
            self::ExpiryNotice, self::PartialSuspension => EmailAccountPurpose::Pec,
            self::Signature => null,
        };
    }

    public function hasSubject(): bool
    {
        return $this !== self::Signature;
    }

    /**
     * Segnaposto disponibili per questa email: nome => obbligatorio.
     */
    public function placeholders(): array
    {
        return match ($this) {
            self::Welcome => [
                'nome' => false,
                'email' => false,
                'password_provvisoria' => true,
                'link_accesso' => false,
            ],
            self::PasswordResetByAdmin => [
                'nome' => false,
                'password_provvisoria' => true,
                'link_accesso' => false,
            ],
            self::PasswordReset => [
                'nome' => false,
                'link_reset' => true,
                'scadenza_link' => false,
            ],
            self::CertificateApproved => [
                'nome' => false,
                'tipo_certificato' => false,
                'data_scadenza' => true,
                'link_profilo' => false,
            ],
            self::CertificateRejected => [
                'nome' => false,
                'motivazione' => true,
                'link_profilo' => false,
            ],
            self::Reminder30, self::Reminder10, self::Reminder3 => [
                'nome' => false,
                'tipo_certificato' => false,
                'data_scadenza' => true,
                'giorni_alla_scadenza' => false,
                'link_profilo' => false,
            ],
            self::ExpiryNotice => [
                'nome' => false,
                'cognome' => false,
                'tipo_certificato' => false,
                'data_scadenza' => true,
                'link_profilo' => false,
            ],
            self::PartialSuspension => [
                'nome' => false,
                'cognome' => false,
                'tipo_certificato' => true,
                'data_scadenza' => true,
                'attivita_consentite' => true,
                'link_profilo' => false,
            ],
            self::Signature => [],
        };
    }

    public function requiredPlaceholders(): array
    {
        return array_keys(array_filter($this->placeholders()));
    }

    /**
     * Segnaposto già formattati con le doppie graffe (es. "{{nome}}"), per
     * mostrarli nell'editor. Costruiti qui e non nella vista Blade, perché
     * le graffe letterali in un file .blade.php confondono il compilatore.
     */
    public function placeholderTokens(): array
    {
        $tokens = [];

        foreach ($this->placeholders() as $name => $required) {
            $tokens['{{'.$name.'}}'] = $required;
        }

        return $tokens;
    }

    public function defaultSubject(): ?string
    {
        return match ($this) {
            self::Welcome => 'Benvenuto nel gestionale certificati di Corriferrara',
            self::PasswordResetByAdmin => 'La tua password è stata reimpostata',
            self::PasswordReset => 'Reimposta la tua password',
            self::CertificateApproved => 'Certificato medico approvato',
            self::CertificateRejected => 'Certificato medico non approvato',
            self::Reminder30 => 'Il tuo certificato medico scade tra 30 giorni',
            self::Reminder10 => 'Il tuo certificato medico scade tra 10 giorni',
            self::Reminder3 => 'Ultimo avviso: il tuo certificato medico scade tra 3 giorni',
            self::ExpiryNotice => 'Certificato medico scaduto – sospensione dalle attività sportive',
            self::PartialSuspension => 'Certificato medico scaduto – sospensione parziale dalle attività',
            self::Signature => null,
        };
    }

    public function defaultBody(): string
    {
        return match ($this) {
            self::Welcome => <<<'MD'
                Ciao {{nome}},

                lo staff di A.S.D. Corriferrara ha creato il tuo account sul gestionale dei certificati medici.
                Da qui potrai controllare la scadenza del tuo certificato e caricare quello nuovo quando serve.

                Per iniziare:
                1. accedi da {{link_accesso}} con questi dati:
                   - email: {{email}}
                   - password provvisoria: {{password_provvisoria}}
                2. al primo accesso ti verrà chiesto di scegliere una nuova password.

                Se non ti aspettavi questa email, scrivici rispondendo a questo messaggio.
                MD,
            self::PasswordResetByAdmin => <<<'MD'
                Ciao {{nome}},

                lo staff di A.S.D. Corriferrara ha reimpostato la password del tuo account.

                Accedi da {{link_accesso}} con la password provvisoria: {{password_provvisoria}}
                Al primo accesso dovrai sceglierne una nuova.

                Se non hai chiesto tu il reset, contattaci rispondendo a questa email.
                MD,
            self::PasswordReset => <<<'MD'
                Ciao {{nome}},

                abbiamo ricevuto una richiesta di reimpostazione della password del tuo account.
                Puoi sceglierne una nuova da questo link: {{link_reset}}

                Il link è valido per {{scadenza_link}}. Se non hai fatto tu la richiesta, ignora questa email: la tua password resterà invariata.
                MD,
            self::CertificateApproved => <<<'MD'
                Ciao {{nome}},

                il tuo certificato medico {{tipo_certificato}} è stato verificato e approvato.
                È valido fino al {{data_scadenza}}.

                Ti avviseremo noi quando si avvicinerà la scadenza. Puoi controllarlo in qualunque momento dal tuo profilo: {{link_profilo}}
                MD,
            self::CertificateRejected => <<<'MD'
                Ciao {{nome}},

                purtroppo il certificato medico che hai caricato non è stato approvato, per questo motivo:

                {{motivazione}}

                Ti chiediamo di caricarne uno corretto dal tuo profilo: {{link_profilo}}
                Per qualsiasi dubbio rispondi pure a questa email.
                MD,
            self::Reminder30 => <<<'MD'
                Ciao {{nome}},

                ti ricordiamo che il tuo certificato medico {{tipo_certificato}} scadrà il {{data_scadenza}}, tra {{giorni_alla_scadenza}} giorni.

                Hai tutto il tempo per prenotare la visita medico-sportiva. Quando avrai il nuovo certificato, caricalo dal tuo profilo: {{link_profilo}}
                MD,
            self::Reminder10 => <<<'MD'
                Ciao {{nome}},

                il tuo certificato medico {{tipo_certificato}} scadrà il {{data_scadenza}}, tra {{giorni_alla_scadenza}} giorni.

                Se non l'hai ancora fatto, prenota al più presto la visita medico-sportiva e carica il nuovo certificato dal tuo profilo: {{link_profilo}}
                MD,
            self::Reminder3 => <<<'MD'
                Ciao {{nome}},

                il tuo certificato medico {{tipo_certificato}} scadrà il {{data_scadenza}}, tra soli {{giorni_alla_scadenza}} giorni.

                Ti ricordiamo che senza un certificato valido non potrai partecipare agli allenamenti e alle attività di Corriferrara.
                Carica il nuovo certificato dal tuo profilo: {{link_profilo}}
                MD,
            self::ExpiryNotice => <<<'MD'
                Gentile {{nome}} {{cognome}},

                le comunichiamo che in data odierna, {{data_scadenza}}, è scaduto il Suo certificato medico {{tipo_certificato}}.

                Con la presente A.S.D. Corriferrara La diffida dal partecipare a qualsiasi allenamento o attività sportiva organizzata dalla società, fino alla presentazione di un nuovo certificato medico in corso di validità e alla sua approvazione da parte della segreteria.

                Può caricare il nuovo certificato dal Suo profilo: {{link_profilo}}

                Distinti saluti,
                la Segreteria di A.S.D. Corriferrara
                MD,
            self::PartialSuspension => <<<'MD'
                Gentile {{nome}} {{cognome}},

                le comunichiamo che in data odierna, {{data_scadenza}}, è scaduto il Suo certificato medico {{tipo_certificato}}.

                Con la presente A.S.D. Corriferrara La diffida dallo svolgere le attività coperte da tale certificato fino alla presentazione di un nuovo certificato in corso di validità e alla sua approvazione da parte della segreteria.

                Grazie agli altri certificati validi in Suo possesso, può continuare a svolgere: {{attivita_consentite}}.

                Può caricare il nuovo certificato dal Suo profilo: {{link_profilo}}

                Distinti saluti,
                la Segreteria di A.S.D. Corriferrara
                MD,
            self::Signature => <<<'MD'
                —
                A.S.D. Corriferrara
                Questa email è stata inviata automaticamente dal gestionale certificati medici.
                MD,
        };
    }
}
