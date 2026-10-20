<?php

namespace Database\Seeders;

use App\Enums\EmailTemplateKey;
use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Precarica i testi di default, se non esistono già: non sovrascrive
     * le modifiche fatte dagli amministratori dal pannello.
     */
    public function run(): void
    {
        foreach (EmailTemplateKey::cases() as $key) {
            EmailTemplate::firstOrCreate(
                ['key' => $key->value],
                [
                    'subject' => $key->defaultSubject(),
                    'body_markdown' => $key->defaultBody(),
                ]
            );
        }
    }
}
