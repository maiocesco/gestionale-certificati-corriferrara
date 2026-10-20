<?php

namespace App\Mail;

use App\Enums\EmailTemplateKey;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class PasswordResetByAdminEmail extends TemplatedMailable implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $temporaryPassword,
    ) {}

    protected function templateKey(): EmailTemplateKey
    {
        return EmailTemplateKey::PasswordResetByAdmin;
    }

    protected function placeholders(): array
    {
        return [
            'nome' => $this->user->first_name,
            'password_provvisoria' => $this->temporaryPassword,
            'link_accesso' => route('login'),
        ];
    }
}
