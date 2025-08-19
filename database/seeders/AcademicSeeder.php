<?php

namespace Database\Seeders;

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
        // Seed Programs
        DB::table('programs')->insert([
            ['code' => 'BSCS', 'name' => 'Bachelor of Science in Computer Science'],
            ['code' => 'BSEd', 'name' => 'Bachelor of Secondary Education'],
            ['code' => 'BSBA', 'name' => 'Bachelor of Science in Business Administration'],
            ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology'],
            ['code' => 'BSN',  'name' => 'Bachelor of Science in Nursing'],
            ['code' => 'NA', 'name' => 'Not Applicable'],
        ]);

        // Seed Majors
        DB::table('majors')->insert([
            ['name' => 'Filipino'],
            ['name' => 'Mathematics'],
            ['name' => 'Information Security'],
            ['name' => 'Marketing'],
            ['name' => 'Biology'],
        ]);

        // Seed Colleges
        DB::table('colleges')->insert([
            ['code' => 'COE', 'name' => 'College of Engineering'],
            ['code' => 'CAS', 'name' => 'College of Arts and Sciences'],
            ['code' => 'CBA', 'name' => 'College of Business Administration'],
            ['code' => 'CTE', 'name' => 'College of Teacher Education'],
            ['code' => 'CHS', 'name' => 'College of Health Sciences'],
            ['code' => 'NA', 'name' => 'Not Applicable'],
        ]);
    }
}
