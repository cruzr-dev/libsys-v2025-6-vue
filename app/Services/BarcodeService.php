<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    protected BarcodeGeneratorPNG $generator;

    public function __construct()
    {
        $this->generator = new BarcodeGeneratorPNG();
    }

    /**
     * Generate and store a barcode image for a given card number.
     *
     * @param string|int $cardNumber
     * @return string The stored relative path (e.g. /barcodes/123.png)
     */
    public function store($cardNumber): string
    {
        $barcodeData = $this->generator->getBarcode($cardNumber, $this->generator::TYPE_CODE_128);

        $path = 'barcodes/' . $cardNumber . '.png';

        Storage::put($path, $barcodeData);

        // store with leading slash in DB
        return '/' . $path;
    }

    /**
     * Delete a barcode image from storage.
     *
     * @param string $path The stored relative path (e.g. /barcodes/123.png)
     * @return bool True if deletion was successful or file doesn't exist, false otherwise
     */
    public function delete(string $path): bool
    {
        // Remove leading slash for Storage::delete
        $path = ltrim($path, '/');

        // Check if file exists before attempting deletion
        if (Storage::exists($path)) {
            return Storage::delete($path);
        }

        // Return true if file doesn't exist (no action needed)
        return true;
    }
}
