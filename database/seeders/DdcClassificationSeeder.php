<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DdcClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $classifications = [
            ['title' => 'GENERAL WORKS', 'number_range' => '000-009'],
            ['title' => 'GENERAL WORKS/INFORMATION', 'number_range' => '010-019'],
            ['title' => 'PHILOSOPHY & PSYCHOLOGY', 'number_range' => '100-199'],
            ['title' => 'RELIGION', 'number_range' => '200-299'],
            ['title' => 'SOCIAL SCIENCE', 'number_range' => '300-399'],
            ['title' => 'LANGUAGE', 'number_range' => '400-499'],
            ['title' => 'PURE SCIENCE', 'number_range' => '500-599'],
            ['title' => 'APPLIED SCIENCE / TECHNOLOGY', 'number_range' => '600-699'],
            ['title' => 'ARTS & RECREATION', 'number_range' => '700-799'],
            ['title' => 'LITERATURE', 'number_range' => '800-899'],
            ['title' => 'HISTORY & GEOGRAPHY', 'number_range' => '900-999'],
            ['title' => 'FICTION', 'number_range' => '813-813.9, 823-823.9, 833-833.9, 843-843.9'],
        ];

        foreach ($classifications as $classification) {
            DB::table('ddc_classifications')->insert([
                'title' => $classification['title'],
                'number_range' => $classification['number_range'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
