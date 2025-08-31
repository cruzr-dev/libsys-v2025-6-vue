<?php

namespace Database\Seeders;

use App\Models\Record;
use App\Models\Thesis;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ThesisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Record::factory()
            ->count(50)
            ->create()
            ->each(function ($record) {
                Thesis::factory()
                    ->count(rand(1, 3))
                    ->create([
                        'record_id' => $record->id,
                    ]);
            });
    }
}
