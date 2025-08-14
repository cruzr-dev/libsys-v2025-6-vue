<?php

namespace Database\Seeders;

use App\Models\PhysicalLocation;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserTypeSeeder::class);
        $this->call(AcademicPeriodSeeder::class);
        $this->call(StatusSeeder::class);
        $this->call(BorrowingPolicySeeder::class);
        $this->call(OfficeSeeder::class);
        $this->call(AcademicSeeder::class);
        $this->call(DdcClassificationSeeder::class);
        $this->call(LcClassificationSeeder::class);
        $this->call(PhysicalLocationSeeder::class);
        $this->call(CoverTypeSeeder::class);
    }
}
