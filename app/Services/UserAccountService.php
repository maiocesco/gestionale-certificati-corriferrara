<?php

namespace App\Services;

use App\Mail\PasswordResetByAdminEmail;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class UserAccountService
{
    public function __construct(
        private readonly TemporaryPasswordGenerator $passwordGenerator,
    ) {}

    /**
     * Crea un nuovo utente con password provvisoria e invia l'email di
     * benvenuto.
     */
    public function createUser(array $data): User
    {
        $temporaryPassword = $this->passwordGenerator->generate();

        $user = User::create([
            ...$data,
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ]);

        Mail::to($user->email)->send(new WelcomeEmail($user, $temporaryPassword));

        return $user;
    }

    /**
     * Genera una nuova password provvisoria per l'utente, la invia via
     * email e obbliga il cambio al prossimo accesso.
     */
    public function resetPassword(User $user): void
    {
        $temporaryPassword = $this->passwordGenerator->generate();

        $user->forceFill([
            'password' => $temporaryPassword,
            'must_change_password' => true,
        ])->save();

        Mail::to($user->email)->send(new PasswordResetByAdminEmail($user, $temporaryPassword));
    }
}
