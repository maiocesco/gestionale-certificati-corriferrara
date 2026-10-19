<?php

namespace Database\Factories;

use App\Enums\ClubType;
use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'entity' => MembershipEntity::Uisp,
            'discipline' => Discipline::AtleticaLeggera,
            'club_type' => ClubType::Corriferrara,
            'external_club_id' => null,
            'card_number' => fake()->unique()->numerify('######'),
            'is_competitive' => false,
        ];
    }

    public function fidal(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity' => MembershipEntity::Fidal,
            'discipline' => Discipline::AtleticaLeggera,
            'is_competitive' => true,
        ]);
    }

    public function competitive(): static
    {
        return $this->state(fn (array $attributes) => ['is_competitive' => true]);
    }
}
