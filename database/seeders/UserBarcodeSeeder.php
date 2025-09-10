<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Storage;

class UserBarcodeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereNull('barcode_file')->get();
        $totalUsers = $users->count();

        if ($totalUsers === 0) {
            $this->command->info('No users without barcodes found.');
            return;
        }

        $this->command->info("Generating barcodes for {$totalUsers} users...");
        $progressBar = $this->command->getOutput()->createProgressBar($totalUsers);
        $progressBar->start();

        $generator = new BarcodeGeneratorPNG();
        $skippedCount = 0;
        $generatedCount = 0;

        foreach ($users as $user) {
            if ($user->card_number) {
                try {
                    $filename = $user->card_number . '.png';

                    // Check if barcode already exists in storage
                    if (Storage::exists($filename)) {
                        // File exists, just update the user record with the path
                        $user->update(['barcode_file' => $filename]);
                        $skippedCount++;
                    } else {
                        // Generate barcode
                        $barcodeData = $generator->getBarcode($user->card_number, $generator::TYPE_CODE_128);

                        // Save barcode
                        Storage::put($filename, $barcodeData);

                        // Update user
                        $user->update(['barcode_file' => $filename]);
                        $generatedCount++;
                    }

                } catch (\Exception $e) {
                    $this->command->error("Failed to process barcode for user {$user->id}: " . $e->getMessage());
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->info("\nBarcode processing completed!");
        $this->command->info("Generated: {$generatedCount} new barcodes");
        $this->command->info("Skipped: {$skippedCount} existing barcodes");
    }
}
