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
}
