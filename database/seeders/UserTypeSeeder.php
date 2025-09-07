<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userTypes = [
            ['key' => 'super_admin', 'name' => 'Super Admin'],
            ['key' => 'library_staff', 'name' => 'Library Staff'],
            ['key' => 'undergraduate', 'name' => 'Undergraduate'],
            ['key' => 'graduate_school', 'name' => 'Graduate School'],
            ['key' => 'faculty', 'name' => 'Faculty'],
            ['key' => 'staff', 'name' => 'Staff'],
        ];
        DB::table('user_types')->insert($userTypes);
    }
}
