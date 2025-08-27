<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Adjust to your User model namespace
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\Storage;

class UserBarcodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users without a barcode_path
        $users = User::whereNull('barcode_path')->get();
        $totalUsers = $users->count();

        // Initialize the progress bar
        $progressBar = $this->command->getOutput()->createProgressBar($totalUsers);
        $progressBar->start();

        $generator = new BarcodeGeneratorPNG();

        foreach ($users as $user) {
            if ($user->card_number) { // Ensure card_number exists
                // Generate barcode for the card_number
                $barcodeData = $generator->getBarcode($user->card_number, $generator::TYPE_CODE_128);

                // Define the filename and save the barcode
                $filename = 'barcodes/' . $user->card_number . '.png';
                Storage::put('/' . $filename, $barcodeData);

                // Update the user's barcode_path
                $user->update(['barcode_path' => $filename]);
            }

            // Advance the progress bar
            $progressBar->advance();
        }

        // Finish the progress bar
        $progressBar->finish();
    }
}
