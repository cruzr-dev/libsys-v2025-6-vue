<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('offices')->insert([
            [
                'acronym' => 'OSAS',
                'name' => 'Office of GraduateStudent Affairs and Services',
            ],
            [
                'acronym' => 'OF1',
                'name' => 'Office 1',
            ],
            [
                'acronym' => 'OF2',
                'name' => 'Office 2',
            ],
        ]);
    }
}
