<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['key' => 'available', 'name' => 'Available'],
            ['key' => 'damaged', 'name' => 'Damaged'],
            ['key' => 'missing', 'name' => 'Missing'],
            ['key' => 'borrowed', 'name' => 'Borrowed'],
            ['key' => 'discarded', 'name' => 'Discarded'],
            ['key' => 'transferred', 'name' => 'Transferred'],
        ];

        foreach ($statuses as $status) {
            Status::create([
                'key' => $status['key'],
                'name' => $status['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
