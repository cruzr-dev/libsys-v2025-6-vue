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

        $totalFiles = count($files);
        $successCount = 0;
        $errorCount = 0;

        $this->command->info("Starting to resize {$totalFiles} book covers...");

        foreach ($files as $filePath) {
            // Extract just the filename with extension
            $filename = basename($filePath);

            try {
                // Use the resize method from BookCoverImageService
                $resizedFilename = $bookCoverService->resize($filename);
                $successCount++;

                // Only show progress every 10 files or for errors
                if ($successCount % 10 === 0) {
                    $this->command->info("Progress: {$successCount}/{$totalFiles} completed");
                }

            } catch (\Exception $e) {
                $errorCount++;
                $this->command->error("Failed to resize {$filename}: " . $e->getMessage());
            }
        }

        $this->command->info("Book cover resizing completed! Success: {$successCount}, Errors: {$errorCount}");
    }
}
