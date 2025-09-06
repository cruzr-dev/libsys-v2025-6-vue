<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;

class BookCoverImageService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Store book cover: resize proportionally, save as JPG.
     */
    public function store(UploadedFile $file, string $bookId): string
    {
        $filename = $bookId . '.jpg'; // always JPG

        // Load image
        $image = $this->imageManager->read($file->getRealPath());

        // Resize proportionally to fit max width/height (e.g., 600x900)
        $image = $image->scaleDown(600, 900);

        // Encode as JPG (quality 85 for better cover detail)
        $encoded = $image->encode(new JpegEncoder(quality: 85));

        // Save to storage
        $path = "book_covers/{$filename}";
        Storage::disk('public')->put($path, (string) $encoded);

        return $filename;
    }

    /**
     * Delete book cover from storage.
     */
    public function delete(string $bookId): bool
    {
        $filename = $bookId . '.jpg';
        $path = "book_covers/{$filename}";

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}
