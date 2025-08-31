<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // users related
        $this->call(UserTypeSeeder::class);
        $this->call(OfficeSeeder::class);
        $this->call(AcademicSeeder::class);
        $this->call(UserImportSeeder::class);

        // records related seeders
        $this->call(DdcClassificationSeeder::class);
        $this->call(PhysicalLocationSeeder::class);
        $this->call(BorrowingPolicySeeder::class);
        $this->call(StatusSeeder::class);
        $this->call(AcademicPeriodSeeder::class);
        $this->call(BookImportSeeder::class);
        $this->call(DigitalResourceSeeder::class);
        $this->call(PeriodicalSeeder::class);

        // discarded
//        $this->call(CoverTypeSeeder::class);
//        $this->call(SourceSeeder::class);
    }
}
