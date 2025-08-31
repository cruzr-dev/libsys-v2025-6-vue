<?php

namespace Database\Factories;

use App\Models\DdcClassification;
use App\Models\LcClassification;
use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Periodical>
 */
class PeriodicalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'record_id' => Record::factory(),
            'authors' => json_encode([$this->faker->name(), $this->faker->name()]),
            'editors' => json_encode([$this->faker->name()]),
            'month' => $this->faker->monthName(),
            'publication_year' => $this->faker->year(),
            'publication_month' => $this->faker->monthName(),
            'publisher' => $this->faker->company(),
            'volume_number' => $this->faker->numberBetween(1, 50),
            'issue_number' => $this->faker->numberBetween(1, 12),
            'issn' => $this->faker->unique()->regexify('[0-9]{4}-[0-9]{4}'),
            'series_title' => $this->faker->sentence(3, true),
            'call_number' => $this->faker->regexify('[A-Z]{2}[0-9]{3}\.[0-9]{2}'),
            'cover_image' => $this->faker->imageUrl(200, 300, 'cover'),
            'source' => $this->faker->company(),
            'donated_by' => $this->faker->name(),
            'purchase_amount' => $this->faker->randomFloat(2, 5, 100),
            'lot_cost' => $this->faker->randomFloat(2, 50, 500),
            'supplier' => $this->faker->company(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
