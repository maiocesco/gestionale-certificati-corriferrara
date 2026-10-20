<?php

namespace App\Mail;

use App\Enums\EmailTemplateKey;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\URL;

class PasswordResetEmail extends TemplatedMailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $token,
    ) {}

    protected function templateKey(): EmailTemplateKey
    {
        return EmailTemplateKey::PasswordReset;
    }

    protected function placeholders(): array
    {
        return [
            'nome' => $this->user->first_name,
            'link_reset' => URL::route('password.reset', [
                'token' => $this->token,
                'email' => $this->user->email,
            ]),
            'scadenza_link' => __(':minuti minuti', ['minuti' => config('auth.passwords.users.expire')]),
        ];
    }
}
