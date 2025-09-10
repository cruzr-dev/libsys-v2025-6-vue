<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use Illuminate\Support\Facades\Storage;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class BookQrcodeSeeder extends Seeder
{
    public function run(): void
    {
        // Get books that don't have QR codes and have associated records with accession numbers
        $books = Book::whereNull('qrcode_file')
            ->whereHas('record', function($query) {
                $query->whereNotNull('accession_number');
            })
            ->with('record') // Eager load the record relationship
            ->get();

        $totalBooks = $books->count();
        if ($totalBooks === 0) {
            $this->command->info('No books without QR codes found.');
            return;
        }

        $this->command->info("Generating QR codes for {$totalBooks} books...");
        $progressBar = $this->command->getOutput()->createProgressBar($totalBooks);
        $progressBar->start();

        $writer = new PngWriter();
        $skippedCount = 0;
        $generatedCount = 0;

        foreach ($books as $book) {
            // Access accession_number from the related record
            if ($book->record && $book->record->accession_number) {
                try {
                    $filename = 'qrcodes/' . $book->record->accession_number . '.png';

                    // Check if QR code already exists in storage
                    if (Storage::exists($filename)) {
                        // File exists, just update the book record with the path
                        $book->update(['qrcode_file' => $filename]);
                        $skippedCount++;
                    } else {
                        // Generate QR code from accession number
                        $qrCode = QrCode::create($book->record->accession_number)
                            ->setSize(300)        // size in px
                            ->setMargin(10);      // margin around QR

                        $qrResult = $writer->write($qrCode);

                        // Save QR code
                        Storage::put($filename, $qrResult->getString());

                        // Update book record
                        $book->update(['qrcode_file' => $filename]);
                        $generatedCount++;
                    }
                } catch (\Exception $e) {
                    $this->command->error("Failed to process QR code for book {$book->id}: " . $e->getMessage());
                }
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->command->info("\nQR code processing completed!");
        $this->command->info("Generated: {$generatedCount} new QR codes");
        $this->command->info("Skipped: {$skippedCount} existing QR codes");
    }
}
