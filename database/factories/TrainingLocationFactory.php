<?php

namespace Database\Factories;

use App\Models\TrainingLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingLocation>
 */
class TrainingLocationFactory extends Factory
{
    protected $model = TrainingLocation::class;

    public function definition(): array
    {
        return [
            'name' => 'Runner\'s School '.fake()->city(),
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
        ];
    }
}
