<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Colocation>
 */
class ColocationFactory extends Factory
{
    /**
     * Define the model default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name() . "'s Colocation",
            'owner_id' => User::factory(),
            'status' => 'active',
        ];
    }
}
