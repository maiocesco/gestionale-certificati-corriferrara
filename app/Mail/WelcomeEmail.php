<?php

namespace App\Mail;

use App\Enums\EmailTemplateKey;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class WelcomeEmail extends TemplatedMailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {}

    protected function templateKey(): EmailTemplateKey
    {
        return EmailTemplateKey::Welcome;
    }

    protected function placeholders(): array
    {
        return [
            'nome' => $this->user->first_name,
            'email' => $this->user->email,
            'password_provvisoria' => $this->temporaryPassword,
            'link_accesso' => route('login'),
        ];
    }
}
