<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfileImageService
{
    /**
     * Handle upload and return filename
     */
    public function store(UploadedFile $file, string $libraryId): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = $libraryId . '.' . $extension;

        $file->storeAs('profile_images', $filename, 'public');

        return $filename; // only filename, not path
    }
}
