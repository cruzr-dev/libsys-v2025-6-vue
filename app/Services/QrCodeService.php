<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

class QrCodeService
{
    /**
     * Generate and store a QR code for the given accession number.
     *
     * @param  string  $accessionNumber
     * @return string  Relative storage path
     */
    public function store(string $accessionNumber): string
    {
        // Build QR code with GD backend (PngWriter)
        $result = Builder::create()
            ->writer(new PngWriter()) // <-- ensures GD backend
            ->data($accessionNumber)
            ->size(300)
            ->margin(10)
            ->build();

        // Define path
        $filename = $accessionNumber . '.png';
        $path = 'qrcodes/' . $filename;

        // Save to storage/app/public/qrcodes
        Storage::disk('public')->put($path, $result->getString());

        return $filename;
    }
}
