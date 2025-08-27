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
        $users = User::whereNull('barcode_path')->get();
        $totalUsers = $users->count();

        if ($totalUsers === 0) {
            $this->command->info('No users without barcodes found.');
            return;
        }

        $this->command->info("Generating barcodes for {$totalUsers} users...");
        $progressBar = $this->command->getOutput()->createProgressBar($totalUsers);
        $progressBar->start();

        $generator = new BarcodeGeneratorPNG();

        foreach ($users as $user) {
            if ($user->card_number) {
                try {
                    // Generate barcode
                    $barcodeData = $generator->getBarcode($user->card_number, $generator::TYPE_CODE_128);

                    // Save barcode (removed extra slash)
                    $filename = 'barcodes/' . $user->card_number . '.png';
                    Storage::put($filename, $barcodeData);

                    // Update user
                    $user->update(['barcode_path' => $filename]);

                } catch (\Exception $e) {
                    $this->command->error("Failed to generate barcode for user {$user->id}: " . $e->getMessage());
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->info("\nBarcode generation completed!");
    }
}
