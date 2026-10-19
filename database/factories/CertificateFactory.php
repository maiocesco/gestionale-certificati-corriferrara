<?php

namespace Database\Factories;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\Discipline;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'uploaded_by' => fn (array $attributes) => $attributes['user_id'],
            'type' => CertificateType::NonCompetitive,
            'discipline' => null,
            'expires_at' => now()->addMonths(6)->toDateString(),
            'file_path' => 'certificates/fake/'.fake()->uuid().'.pdf',
            'file_original_name' => 'certificato.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => 102400,
            'status' => CertificateStatus::Pending,
        ];
    }

    public function competitive(Discipline $discipline = Discipline::AtleticaLeggera): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CertificateType::Competitive,
            'discipline' => $discipline,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CertificateStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Documento illeggibile.'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CertificateStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay()->toDateString(),
        ]);
    }

    public function expiringInDays(int $days): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays($days)->toDateString(),
        ]);
    }
}
