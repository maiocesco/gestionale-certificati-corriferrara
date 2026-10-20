<?php

namespace Database\Seeders;

use App\Enums\EmailAccountPurpose;
use App\Models\EmailAccount;
use Illuminate\Database\Seeder;

class EmailAccountSeeder extends Seeder
{
    /**
     * Crea le due righe fisse (PEC e classica), se non esistono già.
     * Restano vuote: l'amministratore le configura dal pannello, con
     * fallback su .env fino a quel momento (vedi MailerConfigResolver).
     */
    public function run(): void
    {
        foreach (EmailAccountPurpose::cases() as $purpose) {
            EmailAccount::firstOrCreate(['purpose' => $purpose->value]);
        }
    }
}
