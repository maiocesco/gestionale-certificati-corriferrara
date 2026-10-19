<?php

namespace Database\Factories;

use App\Models\ExternalClub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalClub>
 */
class ExternalClubFactory extends Factory
{
    protected $model = ExternalClub::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
        ];
    }
}
