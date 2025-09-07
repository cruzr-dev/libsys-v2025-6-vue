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

        $successCount = 0;
        $errorCount = 0;

        $this->command->info("Starting to resize book covers...");

        // Create progress bar
        $progressBar = $this->command->getOutput()->createProgressBar(count($files));
        $progressBar->start();

        foreach ($files as $filePath) {
            // Extract just the filename with extension
            $filename = basename($filePath);

            try {
                // Use the resize method from BookCoverImageService
                $resizedFilename = $bookCoverService->resize($filename);
                $successCount++;

            } catch (\Exception $e) {
                $errorCount++;
                // Show error on new line without breaking progress bar
                $progressBar->clear();
                $this->command->error("Failed to resize {$filename}: " . $e->getMessage());
                $progressBar->display();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->newLine(2);
        $this->command->info("Book cover resizing completed! Success: {$successCount}, Errors: {$errorCount}");
    }}
