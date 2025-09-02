<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Record>
 */
class RecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    private static int $accessionCounter = 100000;

    public function definition(): array
    {

        return [
            'accession_number' => fake()->numberBetween(100000, 999999),
            'title' => $this->faker->sentence(3, true),
            'date_received' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'status' => $this->faker->randomElement(['available', 'damaged', 'missing', 'borrowed', 'discarded']),
            'subject' => $this->faker->sentence(5, true),
            'added_by' => User::factory(),
            'updated_by' => User::factory(),
            'imported_by' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
