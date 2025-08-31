<?php

namespace Database\Factories;

use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DigitalResource>
 */
class DigitalResourceFactory extends Factory
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
            'publication_year' => $this->faker->year(),
            'copyright_year' => $this->faker->year(),
            'producer' => $this->faker->company(),
            'language' => $this->faker->randomElement(['English', 'Spanish', 'French', 'German']),
            'collection_type' => $this->faker->randomElement(['cd', 'duplicate_copy', 'cassette', 'vhs', 'cdr']),
            'duration' => $this->faker->time('H:i:s', '02:00:00'),
            'cover_image' => $this->faker->imageUrl(200, 300, 'cover'),
            'source' => $this->faker->company(),
            'donated_by' => $this->faker->name(),
            'purchase_amount' => $this->faker->randomFloat(2, 5, 100),
            'lot_cost' => $this->faker->randomFloat(2, 50, 500),
            'supplier' => $this->faker->company(),
            'overview' => $this->faker->paragraph(3),
            'created_at' => $this->faker->dateTimeThisYear(),
            'updated_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
