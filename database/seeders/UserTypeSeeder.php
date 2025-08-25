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
            ['key' => 'undergrad_student', 'name' => 'Undergraduate Student'],
            ['key' => 'grad_student', 'name' => 'Graduate School Student'],
            ['key' => 'faculty', 'name' => 'Faculty'],
            ['key' => 'staff', 'name' => 'Staff'],
        ];
        DB::table('user_types')->insert($userTypes);
    }
}
