<?php

namespace Database\Factories;

use App\Models\Scheme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Scheme>
 */
class SchemeFactory extends Factory
{
    public function definition(): array
    {
        $allocated = fake()->numberBetween(50, 2000) * 1000000;
        $target = fake()->numberBetween(50000, 5000000);

        return [
            'name' => Str::title(fake()->words(3, true)).' Scheme',
            'code' => strtoupper(fake()->bothify('???-####')),
            'department' => fake()->randomElement(['Health', 'Education', 'Agriculture', 'Rural Development', 'Finance']),
            'category' => fake()->randomElement(Scheme::CATEGORIES),
            'status' => fake()->randomElement(Scheme::STATUSES),
            'start_date' => fake()->dateTimeBetween('-3 years', '-2 months'),
            'end_date' => fake()->dateTimeBetween('+2 months', '+3 years'),
            'budget_allocated' => $allocated,
            'budget_disbursed' => (int) ($allocated * fake()->randomFloat(2, 0.05, 0.98)),
            'target_beneficiaries' => $target,
            'enrolled_beneficiaries' => (int) ($target * fake()->randomFloat(2, 0.1, 0.99)),
            'description' => fake()->sentence(12),
        ];
    }
}
