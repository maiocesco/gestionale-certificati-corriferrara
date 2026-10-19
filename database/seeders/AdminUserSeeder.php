<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\TemporaryPasswordGenerator;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Crea il primo amministratore, se non esiste già.
     *
     * La password provvisoria viene generata al volo (o letta da
     * ADMIN_PASSWORD, utile per ambienti automatizzati) e stampata a
     * console: non viene mai scritta nel codice né salvata in chiaro.
     */
    public function run(TemporaryPasswordGenerator $passwordGenerator): void
    {
        $email = 'maio.cesco@gmail.com';

        if (User::where('email', $email)->exists()) {
            $this->command?->info("L'amministratore {$email} esiste già, seeder saltato.");

            return;
        }

        $password = env('ADMIN_PASSWORD') ?: $passwordGenerator->generate();

        User::create([
            'first_name' => 'Francesco',
            'last_name' => 'Maietti',
            'email' => $email,
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $this->command?->info('Primo amministratore creato.');
        $this->command?->warn("Email: {$email}");
        $this->command?->warn("Password provvisoria: {$password}");
        $this->command?->warn('Annotala ora: non verrà mostrata di nuovo.');
    }
}
