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
            ['title' => 'APPLIED SCIENCE', 'number' => '600'],
            ['title' => 'ARTS', 'number' => '700'],
            ['title' => 'FICTION', 'number' => '800'], // usually under Literature
            ['title' => 'GENERAL WORKS', 'number' => '000'],
            ['title' => 'GENERAL WORKS/INFORMATION', 'number' => '000'], // optional, if library splits this
            ['title' => 'HISTORY', 'number' => '900'],
            ['title' => 'LANGUAGE', 'number' => '400'],
            ['title' => 'LITERATURE', 'number' => '800'],
            ['title' => 'PHILOSOPHY', 'number' => '100'],
            ['title' => 'PURE SCIENCE', 'number' => '500'],
            ['title' => 'RELIGION', 'number' => '200'],
            ['title' => 'SOCIAL SCIENCE', 'number' => '300'],
        ];

        foreach ($classifications as $classification) {
            DB::table('ddc_classifications')->insert([
                'title' => $classification['title'],
                'number' => $classification['number'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
