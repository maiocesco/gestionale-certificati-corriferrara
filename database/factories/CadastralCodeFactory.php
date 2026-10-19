<?php

namespace Database\Factories;

use App\Enums\CadastralCodeType;
use App\Models\CadastralCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CadastralCode>
 */
class CadastralCodeFactory extends Factory
{
    protected $model = CadastralCode::class;

    public function definition(): array
    {
        return [
            'type' => CadastralCodeType::Comune,
            'province' => strtoupper(fake()->lexify('??')),
            'name' => strtoupper(fake()->city()),
            'belfiore_code' => strtoupper(fake()->lexify('?').fake()->numerify('###')),
            'valid_from' => '1866-01-01',
            'valid_until' => null,
        ];
    }

    public function foreignState(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CadastralCodeType::ForeignState,
            'province' => null,
            'belfiore_code' => 'Z'.fake()->numerify('###'),
        ]);
    }
}
