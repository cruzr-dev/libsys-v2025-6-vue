<?php

namespace Database\Factories;

use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Thesis>
 */
class ThesisFactory extends Factory
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
            'researchers' => json_encode([$this->faker->name(), $this->faker->name()]),
            'adviser' => $this->faker->name(),
            'year' => $this->faker->year(),
            'month' => $this->faker->monthName(),
            'institution' => $this->faker->company() . ' University',
            'college' => $this->faker->randomElement(['College of Arts and Sciences', 'College of Engineering', 'College of Education', 'College of Business']),
            'degree_program' => $this->faker->randomElement(['Computer Science', 'Biology', 'Mechanical Engineering', 'Business Administration']),
            'degree_level' => $this->faker->randomElement(['Bachelor', 'Master', 'Doctoral']),
            'ddc_class_id' => null, // Nullable, assuming no DdcClassification creation
            'lc_class_id' => null, // Nullable, assuming no LcClassification creation
            'call_number' => $this->faker->regexify('[A-Z]{2}[0-9]{4}'),
            'physical_location_id' => null, // Nullable, assuming no PhysicalLocation creation
            'abstract' => $this->faker->paragraph(5),
            'created_at' => $this->faker->dateTimeThisYear(),
            'updated_at' => $this->faker->dateTimeThisYear(),
        ];
    }
}
