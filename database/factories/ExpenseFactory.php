<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Colocation;
use App\Models\User;
use App\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 10, 500),
            'expense_date' => fake()->date(),
            'user_id' => User::factory(),
            'colocation_id' => Colocation::factory(),
            'category_id' => Category::factory(),
        ];
    }
}
