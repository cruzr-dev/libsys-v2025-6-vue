<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate and store a QR code image for a given card number.
     *
     * @param string|int $accessionNumber
     * @return string The stored relative path (e.g. /qrcodes/123.png)
     */
    public function store($accessionNumber): string
    {
        // Generate QR code as PNG binary data
        $qrCodeData = QrCode::format('png')
            ->size(300) // adjust size as needed
            ->margin(2)
            ->generate((string) $accessionNumber);

        // Store file in storage/app/qrcodes/
        $path = 'qrcodes/' . $accessionNumber . '.png';
        Storage::put($path, $qrCodeData);

        // Store with leading slash in DB for easy retrieval
        return '/' . $path;
    }
}
