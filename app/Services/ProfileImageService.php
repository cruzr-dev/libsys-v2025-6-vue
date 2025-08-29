<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;

class ProfileImageService
{
    protected ImageManager $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    /**
     * Store profile image: crop square, resize, save as JPG.
     */
    public function store(UploadedFile $file, string $libraryId): string
    {
        $filename = $libraryId . '.jpg'; // always JPG

        // Load image
        $image = $this->imageManager->read($file->getRealPath());

        // Crop to square (centered)
        $size = min($image->width(), $image->height());
        $x = (int)(($image->width() - $size) / 2);
        $y = (int)(($image->height() - $size) / 2);

        $image = $image->crop($size, $size, $x, $y);

        // Resize to optimal size (300x300)
        $image = $image->resize(300, 300);

        // Encode as JPG (quality 80)
        $encoded = $image->encode(new JpegEncoder(quality: 80));

        // Save to storage
        $path = "profile_images/{$filename}";
        Storage::disk('public')->put($path, (string) $encoded);

        return $filename;
    }

    /**
     * Delete profile image from storage.
     */
    public function delete(string $libraryId): bool
    {
        $filename = $libraryId . '.jpg';
        $path = "profile_images/{$filename}";

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }
}
