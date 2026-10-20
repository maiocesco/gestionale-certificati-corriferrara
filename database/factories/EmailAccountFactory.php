<?php

namespace Database\Factories;

use App\Enums\EmailAccountPurpose;
use App\Enums\SmtpEncryption;
use App\Models\EmailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailAccount>
 */
class EmailAccountFactory extends Factory
{
    protected $model = EmailAccount::class;

    public function definition(): array
    {
        return [
            'purpose' => fake()->randomElement(EmailAccountPurpose::cases())->value,
            'from_address' => fake()->safeEmail(),
            'from_name' => 'A.S.D. Corriferrara',
            'host' => 'smtp.example.test',
            'port' => 587,
            'encryption' => SmtpEncryption::Starttls->value,
            'username' => fake()->userName(),
            'password' => 'secret',
        ];
    }

    public function unconfigured(): static
    {
        return $this->state(fn () => [
            'from_address' => null,
            'from_name' => null,
            'host' => null,
            'port' => null,
            'encryption' => null,
            'username' => null,
            'password' => null,
        ]);
    }
}
