<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 10, 500),
            'expense_date' => fake()->date(),
            'payer_id' => User::factory(),
            'colocation_id' => Colocation::factory(),
            'category_id' => Category::factory(),
        ];
    }
}
