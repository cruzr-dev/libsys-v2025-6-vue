<?php

namespace Database\Seeders;

use App\Models\College;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed Colleges
        $ctet = College::create([
            'code' => 'CTET',
            'name' => 'College of Teacher Education and Technology',
        ]);

        $ctet->courses()->createMany([
            [
                'code' => 'BSED',
                'name' => 'Bachelor of Secondary Education',
            ],
            [
                'code' => 'BEED',
                'name' => 'Bachelor of Elementary Education',
            ],
            [
                'code' => 'BECED',
                'name' => 'Bachelor of Early Childhood Education',
            ],
            [
                'code' => 'BSNED',
                'name' => 'Bachelor of Special Needs Education',
            ],
            [
                'code' => 'BSIT',
                'name' => 'Bachelor Science in Information Technology',
            ],
        ]);

    }
}
