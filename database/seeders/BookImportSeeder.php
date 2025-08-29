<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\CoverType;
use App\Models\DdcClassification;
use App\Models\PhysicalLocation;
use App\Models\Record;
use App\Models\Source;
use App\Models\Status;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BookImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = public_path(config('app.book_import.csv_directory') . '/' . config('app.book_import.csv_file'));

        try {
            $csvData = $this->loadCsvData($csvPath);
            $lookupIds = $this->getLookupIds();
            $this->processCsvRows($csvData, $lookupIds);
        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Load and parse CSV data.
     *
     * @param string $csvPath
     * @return array
     * @throws \Exception
     */
    private function loadCsvData(string $csvPath): array
    {
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            throw new \Exception("CSV file not found");
        }

        $csvContent = file_get_contents($csvPath);
        $csvData = array_map('str_getcsv', explode("\n", $csvContent));
        array_shift($csvData); // Remove header row

        return $csvData;
    }

    /**
     * Retrieve lookup IDs for status, cover types, sources, etc.
     *
     * @return array
     */
    private function getLookupIds(): array
    {
        $defaultStatus = Status::where('key', 'available')->firstOrFail();

        return [
            'default_status_id' => $defaultStatus->id,
            'default_status_name' => $defaultStatus->name,
            'default_imported_by' => 1, // Adjust as needed
        ];
    }

    /**
     * Process CSV rows and import books.
     *
     * @param array $csvData
     * @param array $lookupIds
     */
    private function processCsvRows(array $csvData, array $lookupIds): void
    {
        $importedCount = 0;
        $failedCount = 0;
        $errors = [];
        $progressBar = $this->command->getOutput()->createProgressBar(count($csvData));
        $progressBar->start();

        foreach ($csvData as $rowIndex => $row) {
            try {
                $row = array_map('trim', $row);
                if ($this->isRowEmpty($row)) {
                    Log::warning('Skipping empty row ' . ($rowIndex + 1) . ':', $row);
                    $progressBar->advance();
                    continue;
                }

                $recordData = $this->parseRecordData($row, $lookupIds);
                $bookData = $this->parseBookData($row);
                $remarkData = $this->parseRemarkData($row);

                // Check for duplicate accession number
                if (!empty($recordData['accession_number']) && Record::where('accession_number', $recordData['accession_number'])->exists()) {
                    throw new \Exception("Book with Accession Number {$recordData['accession_number']} already exists");
                }

                // Validate required fields
                if (empty($recordData['title']) && empty($recordData['accession_number'])) {
                    throw new \Exception("Missing required fields (title or accession_number)");
                }

                $record = Record::create($recordData);
                $record->book()->create($bookData);

                foreach ($remarkData as $remark) {
                    $record->remarks()->create($remark);
                }

                $importedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = "Row " . ($rowIndex + 1) . ": " . $e->getMessage();
                Log::error("Processing row " . ($rowIndex + 1) . ": " . $e->getMessage(), ['data' => $row]);
                if ($failedCount > 5) {
                    throw new \Exception("Too many errors occurred during processing");
                }
            }
            $progressBar->advance();
        }

        $this->displayResults($progressBar, $importedCount, $failedCount, $errors);
    }

    /**
     * Check if a row is empty.
     *
     * @param array $row
     * @return bool
     */
    private function isRowEmpty(array $row): bool
    {
        return empty(array_filter($row, fn($value) => $value !== '' && $value !== null));
    }

    /**
     * Parse record data from a CSV row.
     *
     * @param array $row
     * @param array $lookupIds
     * @return array
     */
    private function parseRecordData(array $row, array $lookupIds): array
    {
        return [
            'accession_number' => $this->parseString($row[1] ?? null),
            'date_received' => $this->parseDate($row[2] ?? null),
            'title' => $this->parseString($row[5] ?? null),
            'status' => $lookupIds['default_status_name'],
            'imported_by' => $lookupIds['default_imported_by'],
            'subject_headings' => $this->parseString($row[13] ?? null),
        ];
    }

    /**
     * Parse book data from a CSV row.
     *
     * @param array $row
     * @return array
     */
    private function parseBookData(array $row): array
    {
        $sourceData = $this->parseSourceData($row);
        return [
            'volume' => $this->parseString($row[0] ?? null),
            'authors' => $this->parseString($row[4] ?? null),
            'edition' => $this->parseString($row[6] ?? null),
            'publication_year' => $this->parseNumeric($row[11] ?? null),
            'publisher' => $this->parseString($row[10] ?? null),
            'publication_place' => $this->parseString($row[9] ?? null),
            'isbn' => $this->parseIsbn($row[12] ?? null),
            'call_number' => $this->parseString($row[3] ?? null),
            'ddc_class_id' => $this->parseDdcClassification($row[7] ?? null),
            'physical_location_id' => $this->parsePhysicalLocation($row[8] ?? null),
            'cover_type_id' => $this->parseCoverType($row[16] ?? null),
            'cover_image' => $this->parseString($row[19] ?? null, ['-']),
            'source_id' => $sourceData['source_id'],
            'purchase_amount' => $sourceData['purchase_amount'],
            'supplier' => $this->parseString($row[18] ?? null),
            'donated_by' => $sourceData['donated_by'],
            'table_of_contents' => $this->parseTableOfContents($row[14] ?? null),
        ];
    }

    /**
     * Parse source-related data (source, purchase amount, donated by).
     *
     * @param array $row
     * @return array
     */
    private function parseSourceData(array $row): array
    {
        $purchaseAmount = $this->parseNumeric($row[17] ?? null);
        $donatedBy = null;
        $source = null;

        if (!is_null($purchaseAmount) && $purchaseAmount < 0) {
            $donatedBy = $this->parseString($row[17] ?? null);
            $source = 'Donation';
            $purchaseAmount = null;
        }

        $sourceId = null;
        if ($source) {
            $sourceId = $this->getOrCreateSource($source);
        } elseif (!empty($row[15])) {
            $sourceId = $this->getOrCreateSource($row[15]);
        }

        return [
            'source_id' => $sourceId,
            'purchase_amount' => $purchaseAmount,
            'donated_by' => $donatedBy,
        ];
    }

    /**
     * Parse remark data for academic periods.
     *
     * @param array $row
     * @return array
     */
    private function parseRemarkData(array $row): array
    {
        $academicPeriods = [
            20 => ['year' => '2007-2008', 'semester' => 'Whole Year'],
            21 => ['year' => '2008-2009', 'semester' => 'Whole Year'],
            22 => ['year' => '2009-2010', 'semester' => 'Whole Year'],
            23 => ['year' => '2010-2011', 'semester' => 'Whole Year'],
            24 => ['year' => '2011-2012', 'semester' => 'Whole Year'],
            25 => ['year' => '2017-2018', 'semester' => 'Whole Year'],
            26 => ['year' => '2018-2019', 'semester' => 'Whole Year'],
            27 => ['year' => '2019-2020', 'semester' => 'Whole Year'],
            28 => ['year' => '2020-2021', 'semester' => 'Whole Year'],
            29 => ['year' => '2021-2022', 'semester' => 'Whole Year'],
            30 => ['year' => '2022-2023', 'semester' => 'Whole Year'],
            31 => ['year' => '2023-2024', 'semester' => '1st Semester'],
            32 => ['year' => '2023-2024', 'semester' => '2nd Semester'],
            33 => ['year' => '2023-2024', 'semester' => 'Whole Year'],
        ];

        $remarkData = [];
        foreach ($academicPeriods as $index => $periodInfo) {
            if (!empty($row[$index])) {
                $academicPeriod = AcademicPeriod::where('academic_year', $periodInfo['year'])
                    ->where('semester', $periodInfo['semester'])
                    ->first();
                if ($academicPeriod) {
                    $remarkData[] = [
                        'academic_period_id' => $academicPeriod->id,
                        'content' => $this->parseString($row[$index]),
                    ];
                }
            }
        }

        return $remarkData;
    }

    /**
     * Parse string values, optionally excluding specific values.
     *
     * @param mixed $value
     * @param array $excludeValues
     * @return string|null
     */
    private function parseString($value, array $excludeValues = []): ?string
    {
        if (!empty($value) && is_string($value) && !in_array($value, $excludeValues, true)) {
            return trim($value);
        }
        return null;
    }

    /**
     * Parse numeric values.
     *
     * @param mixed $value
     * @return int|float|null
     */
    private function parseNumeric($value): int|float|null
    {
        if (!empty($value) && is_numeric($value) && $value !== 0) {
            return is_float($value) ? (float)$value : (int)$value;
        }
        return null;
    }

    /**
     * Parse date values in m/d/Y format.
     *
     * @param mixed $value
     * @return string|null
     */
    private function parseDate($value): ?string
    {
        if (!empty($value) && is_string($value)) {
            try {
                return Carbon::createFromFormat('m/d/Y', $value)->format('Y-m-d');
            } catch (\Exception $e) {
                throw new \Exception("Invalid date format: {$value}");
            }
        }
        return null;
    }

    /**
     * Parse ISBN, excluding invalid values.
     *
     * @param mixed $value
     * @return string|null
     */
    private function parseIsbn($value): ?string
    {
        return $this->parseString($value, ['-']);
    }

    /**
     * Parse DDC classification, creating new records if needed.
     *
     * @param mixed $value
     * @return int|null
     */
    private function parseDdcClassification($value): ?int
    {
        if (!empty($value) && is_string($value)) {
            $name = ucwords(strtolower(trim($value)));
            $ddcClass = DdcClassification::where('name', $name)->first();
            if ($ddcClass) {
                return $ddcClass->id;
            }

            $baseCode = strlen($name) >= 3 ? substr($name, 0, 3) : $name;
            $code = $baseCode;
            $counter = 1;
            while (DdcClassification::where('code', $code)->exists()) {
                $code = $baseCode . $counter++;
            }

            return DdcClassification::create([
                'name' => $name,
                'code' => $code,
            ])->id;
        }
        return null;
    }

    /**
     * Parse physical location, creating new records if needed.
     *
     * @param mixed $value
     * @return int|null
     */
    private function parsePhysicalLocation($value): ?int
    {
        if (!empty($value) && is_string($value)) {
            $name = ucwords(strtolower(trim($value)));
            $location = PhysicalLocation::where('name', $name)->first();
            if ($location) {
                return $location->id;
            }

            $baseSymbol = strlen($name) >= 3 ? substr($name, 0, 3) : $name;
            $symbol = $baseSymbol;
            $counter = 1;
            while (PhysicalLocation::where('symbol', $symbol)->exists()) {
                $symbol = $baseSymbol . $counter++;
            }

            return PhysicalLocation::create([
                'name' => $name,
                'symbol' => $symbol,
            ])->id;
        }
        return null;
    }

    /**
     * Parse cover type, creating new records if needed.
     *
     * @param mixed $value
     * @return int|null
     */
    private function parseCoverType($value): ?int
    {
        if (!empty($value) && is_string($value)) {
            $name = ucwords(strtolower(trim($value)));
            $coverType = CoverType::where('name', $name)->first();
            if ($coverType) {
                return $coverType->id;
            }

            return CoverType::create([
                'key' => strtolower($name),
                'name' => $name,
            ])->id;
        }
        return null;
    }

    /**
     * Parse source, creating new records if needed.
     *
     * @param mixed $value
     * @return int|null
     */
    private function getOrCreateSource($value): ?int
    {
        if (!empty($value) && is_string($value)) {
            $name = ucwords(strtolower(trim($value)));
            $source = Source::where('name', $name)->first();
            if ($source) {
                return $source->id;
            }

            return Source::create([
                'key' => strtolower($name),
                'name' => $name,
            ])->id;
        }
        return null;
    }

    /**
     * Parse table of contents into JSON format.
     *
     * @param mixed $value
     * @return string|null
     */
    private function parseTableOfContents($value): ?string
    {
        if (!empty($value) && is_string($value)) {
            $cleanedContent = str_replace('--', ' - ', trim($value));
            $cleanedContent = preg_replace('/\s+/', ' ', $cleanedContent);
            $tocArray = explode(' - ', $cleanedContent);
            return json_encode($tocArray);
        }
        return null;
    }

    /**
     * Display import results.
     *
     * @param mixed $progressBar
     * @param int $importedCount
     * @param int $failedCount
     * @param array $errors
     */
    private function displayResults($progressBar, int $importedCount, int $failedCount, array $errors): void
    {
        $progressBar->finish();
        $this->command->newLine();
        $this->command->info("Import completed! {$importedCount} book(s) imported successfully.");

        if ($failedCount > 0) {
            $this->command->warn("{$failedCount} row(s) failed.");
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->command->error($error);
            }
            if (count($errors) > 10) {
                $this->command->warn("... and " . (count($errors) - 10) . " more errors. Check logs for details.");
            }
        }
    }

    /**
     * Handle exceptions during seeding.
     *
     * @param \Exception $e
     */
    private function handleException(\Exception $e): void
    {
        $message = app()->environment('production')
            ? 'An unexpected error occurred during seeding.'
            : 'Seeding error: ' . $e->getMessage();

        $this->command->error($message);
        Log::error($message, ['exception' => $e]);
    }
}
