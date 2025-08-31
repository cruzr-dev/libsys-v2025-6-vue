<?php

namespace Database\Seeders;

use App\Models\Periodical;
use App\Models\Record;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeriodicalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 records with associated periodicals
        Record::factory()
            ->count(50)
            ->create()
            ->each(function ($record) {
                Periodical::factory()
                    ->count(rand(1, 3))
                    ->create([
                        'record_id' => $record->id,
                    ]);
            });
    }
}
