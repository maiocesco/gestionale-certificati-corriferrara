<?php

namespace Database\Factories;

use App\Enums\EmailTemplateKey;
use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        $key = fake()->randomElement(EmailTemplateKey::cases());

        return [
            'key' => $key->value,
            'subject' => $key->defaultSubject(),
            'body_markdown' => $key->defaultBody(),
        ];
    }
}
