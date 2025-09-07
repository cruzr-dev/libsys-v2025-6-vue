<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use App\Services\BookCoverImageService;

class ResizeBookCoverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookCoverService = new BookCoverImageService();

        // Get all files in the resized_book_covers directory
        $files = Storage::disk('public')->files('raw_book_covers');

        $this->command->info('Starting to resize book covers...');

        foreach ($files as $filePath) {
            // Extract just the filename with extension
            $filename = basename($filePath);

            $this->command->info("Resizing: {$filename}");

            try {
                // Use the resize method from BookCoverImageService
                $resizedFilename = $bookCoverService->resize($filename);

                $this->command->info("Successfully resized: {$resizedFilename}");

            } catch (\Exception $e) {
                $this->command->error("Failed to resize {$filename}: " . $e->getMessage());
            }
        }

        $this->command->info('Book cover resizing completed!');
    }
}
