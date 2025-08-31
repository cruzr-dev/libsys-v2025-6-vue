<?php

namespace Database\Seeders;

use App\Models\DigitalResource;
use App\Models\Record;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DigitalResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 5 users for foreign key relationships
        $users = User::factory()->count(5)->create();

        // Create 50 records with associated digital resources
        Record::factory()
            ->count(50)
            ->create()
            ->each(function ($record) use ($users) {
                DigitalResource::factory()
                    ->count(random_int(1, 3))
                    ->create([
                        'record_id' => $record->id,
                    ]);
            });
    }
}
