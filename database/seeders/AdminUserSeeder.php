<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CadastralCode;
use App\Models\User;
use App\Services\TemporaryPasswordGenerator;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea il primo amministratore, se non esiste già.
     *
     * La password provvisoria viene generata al volo (o letta da
     * ADMIN_PASSWORD) e stampata a console: non viene mai scritta nel
     * codice né salvata in chiaro.
     *
     * Data/luogo di nascita e codice fiscale sono dati personali reali e
     * non vanno scritti nel codice sorgente (versionato pubblicamente):
     * si leggono da ADMIN_BIRTH_DATE, ADMIN_BIRTH_PLACE_BELFIORE e
     * ADMIN_FISCAL_CODE. Se mancanti, l'amministratore viene comunque
     * creato: i dati anagrafici si completano dal pannello dopo il primo
     * accesso.
     */
    public function run(TemporaryPasswordGenerator $passwordGenerator): void
    {
        $email = 'maio.cesco@gmail.com';

        if (User::where('email', $email)->exists()) {
            $this->command?->info("L'amministratore {$email} esiste già, seeder saltato.");

            return;
        }

        $password = env('ADMIN_PASSWORD') ?: $passwordGenerator->generate();

        $birthPlace = env('ADMIN_BIRTH_PLACE_BELFIORE')
            ? CadastralCode::where('belfiore_code', env('ADMIN_BIRTH_PLACE_BELFIORE'))->first()
            : null;

        User::create([
            'first_name' => 'Francesco',
            'last_name' => 'Maietti',
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
            'must_change_password' => true,
            'birth_date' => env('ADMIN_BIRTH_DATE'),
            'birth_cadastral_code_id' => $birthPlace?->id,
            'fiscal_code' => env('ADMIN_FISCAL_CODE'),
        ]);

        $this->command?->info('Primo amministratore creato.');
        $this->command?->warn("Email: {$email}");
        $this->command?->warn("Password provvisoria: {$password}");
        $this->command?->warn('Annotala ora: non verrà mostrata di nuovo.');

        if (! env('ADMIN_FISCAL_CODE')) {
            $this->command?->warn('Dati anagrafici (nascita, codice fiscale) non impostati: completali dal pannello dopo il primo accesso.');
        }
    }
}
