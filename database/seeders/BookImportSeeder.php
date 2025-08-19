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
        $filename = config('app.book_import.csv_file');
        $csv_directory = config('app.book_import.csv_directory');

        $csv_path = public_path("{$csv_directory}/{$filename}");

        if (!file_exists($csv_path)) {
            $this->command->error("CSV file not found at: {$csv_path}");
            return;
        }

        $csv_content = file_get_contents($csv_path);
        $csv_data = array_map('str_getcsv', explode("\n", $csv_content));

        // Remove header row if it exists
        $headers = array_shift($csv_data);

        $failed_count = 0;
        $errors = [];
        $imported_count = 0;

        $this->command->info('Starting book import from CSV...');
        $this->command->getOutput()->progressStart(count($csv_data));

        foreach ($csv_data as $row_index => $row) {
            try {
                // Trim all values in the row and check for emptiness
                $row = array_map('trim', $row);
                if (empty(array_filter($row, fn($value) => $value !== '' && $value !== null))) {
                    Log::warning('Skipping empty row ' . ($row_index + 1) . ':', $row);
                    $this->command->getOutput()->progressAdvance();
                    continue;
                }

                $date_received = null;
                if (!empty($row[2]) && is_string($row[2])) {
                    try {
                        $date_received = Carbon::createFromFormat('m/d/Y', $row[2])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $failed_count++;
                        $error = "Row " . ($row_index + 1) . ": Invalid date format in date_received: " . $row[2];
                        $errors[] = $error;
                        Log::error($error, ['row_index' => $row_index + 1, 'data' => $row]);
                        $this->command->getOutput()->progressAdvance();
                        continue;
                    }
                }

                $subject_headings = null;
                if (!empty($row[13])) {
                    $subject_headings = $row[13];
                }

                // Get the default status (assuming you have a default user or use system user)
                $defaultStatus = Status::where('key', 'available')->first();
                if (!$defaultStatus) {
                    $this->command->error('Default status "available" not found. Please seed statuses first.');
                    return;
                }

                $record_data = [
                    'accession_number' => $row[1] ?? null,
                    'date_received' => $date_received,
                    'title' => $row[5] ?? null,
                    'status' => $defaultStatus->name,
                    'imported_by' => 1, // Using user ID 1 or adjust as needed
                    'subject_headings' => $subject_headings,
                ];

                $volume = null;
                if (!empty($row[0])) {
                    $volume = $row[0];
                }

                $authors = [];
                if (!empty($row[4])) {
                    $authors = $row[4];
                }

                $edition = null;
                if (!empty($row[6])) {
                    $edition = $row[6];
                }

                $publication_year = $row[11] ?? null;
                if ($publication_year === '' || $publication_year === '-') {
                    $publication_year = null;
                }

                $publisher = null;
                if (!empty($row[10])) {
                    $publisher = $row[10];
                }

                $publication_place = null;
                if (!empty($row[9])) {
                    $publication_place = $row[9];
                }

                $isbn = null;
                if (!empty($row[12])) {
                    $isbn = $row[12];
                    if ($isbn === '-') {
                        $isbn = null;
                    }
                }

                $call_number = null;
                if (!empty($row[3])) {
                    $call_number = $row[3];
                }

                $ddc_class_id = null;
                if (!empty($row[7])) {
                    $ddc_class_name = ucwords(strtolower($row[7]));
                    $ddc_class = DdcClassification::where('name', $ddc_class_name)->first();
                    if ($ddc_class) {
                        $ddc_class_id = $ddc_class->id;
                    } else {
                        // Generate base code
                        $base_code = strlen($ddc_class_name) >= 3 ? substr($ddc_class_name, 0, 3) : $ddc_class_name;

                        // Check for duplicates and append number if needed
                        $code = $base_code;
                        $counter = 1;
                        while (DdcClassification::where('code', $code)->exists()) {
                            $code = $base_code . $counter;
                            $counter++;
                        }

                        $ddc_class_id = DdcClassification::create([
                            'name' => $ddc_class_name,
                            'code' => $code
                        ])->id;
                    }
                }

                $physical_location_id = null;
                if (!empty($row[8])) {
                    $physical_location_name = ucwords(strtolower($row[8]));
                    $physical_location = PhysicalLocation::where('name', $physical_location_name)->first();
                    if ($physical_location) {
                        $physical_location_id = $physical_location->id;
                    } else {
                        // Generate base symbol
                        $base_symbol = strlen($physical_location_name) >= 3 ? substr($physical_location_name, 0, 3) : $physical_location_name;

                        // Check for duplicates and append number if needed
                        $symbol = $base_symbol;
                        $counter = 1;
                        while (PhysicalLocation::where('symbol', $symbol)->exists()) {
                            $symbol = $base_symbol . $counter;
                            $counter++;
                        }

                        $physical_location_id = PhysicalLocation::create([
                            'name' => $physical_location_name,
                            'symbol' => $symbol
                        ])->id;
                    }
                }

                $cover_type_id = null;
                if (!empty($row[16])) {
                    $cover_type_name = $row[16];
                    $cover_type = CoverType::where('name', ucwords(strtolower($cover_type_name)))->first();
                    if ($cover_type) {
                        $cover_type_id = $cover_type->id;
                    } else {
                        $cover_type_id = CoverType::create([
                            'key' => strtolower($cover_type_name),
                            'name' => $cover_type_name
                        ])->id;
                    }
                }

                $cover_image = null;
                if (!empty($row[19])) {
                    $cover_image = $row[19];
                    if ($cover_image === '-') {
                        $cover_image = null;
                    }
                }

                $source = null;
                $donated_by = null;
                $purchaseAmount = $row[17] ?? null;
                if (!is_numeric($purchaseAmount) || $purchaseAmount < 0) {
                    $donated_by = $purchaseAmount;
                    $source = 'Donation';
                    $purchaseAmount = null;
                }

                $source_id = null;
                if ($source) {
                    $source_name = ucwords(strtolower($source));
                    $source_from_db = Source::where('name', $source_name)->first();
                    if ($source_from_db) {
                        $source_id = $source_from_db->id;
                    } else {
                        $source_id = Source::create([
                            'key' => strtolower($source_name),
                            'name' => $source_name
                        ])->id;
                    }
                } else {
                    if (!empty($row[15])) {
                        $source_name = ucwords(strtolower($row[15]));
                        $source_from_db = Source::where('name', $source_name)->first();
                        if ($source_from_db) {
                            $source_id = $source_from_db->id;
                        } else {
                            $source_id = Source::create([
                                'key' => strtolower($source_name),
                                'name' => $source_name
                            ])->id;
                        }
                    }
                }

                $supplier = null;
                if (!empty($row[18])) {
                    $supplier = $row[18];
                }

                $table_of_contents = null;
                if (isset($row[14]) && !empty($row[14])) {
                    $raw_content = $row[14];

                    // Step 1: Clean the content
                    // Replace double dashes with a single space or dash
                    $cleaned_content = str_replace('--', ' - ', $raw_content);

                    // Step 2: Remove excessive whitespace and normalize
                    $cleaned_content = preg_replace('/\s+/', ' ', $cleaned_content);

                    // Step 3: Optional - Split into array for structured storage (e.g., JSON)
                    $toc_array = explode(' - ', $cleaned_content);
                    $table_of_contents = json_encode($toc_array);
                }

                $book_data = [
                    'volume' => $volume,
                    'authors' => $authors,
                    'edition' => $edition,
                    'publication_year' => $publication_year,
                    'publisher' => $publisher,
                    'publication_place' => $publication_place,
                    'isbn' => $isbn,

                    'call_number' => $call_number,
                    'ddc_class_id' => $ddc_class_id,
                    'physical_location_id' => $physical_location_id,

                    'cover_type_id' => $cover_type_id,
                    'cover_image' => $cover_image,

                    'source_id' => $source_id,
                    'purchase_amount' => $purchaseAmount,
                    'supplier' => $supplier,
                    'donated_by' => $donated_by,

                    'table_of_contents' => $table_of_contents,
                ];

                // Reset after use
                $purchaseAmount = null;
                $donated_by = null;
                $source = null;

                // Initialize remark data
                $remark_data = [];

                // Academic period remarks (rows 20-33)
                $academic_periods = [
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

                foreach ($academic_periods as $row_num => $period_info) {
                    if (!empty($row[$row_num])) {
                        $academic_period = AcademicPeriod::where('academic_year', $period_info['year'])
                            ->where('semester', $period_info['semester'])
                            ->first();

                        if ($academic_period) {
                            $remark_data[] = [
                                'academic_period_id' => $academic_period->id,
                                'content' => $row[$row_num],
                            ];
                        }
                    }
                }

                // Clean and validate data
                $record_data['title'] = trim($record_data['title']);

                // Check for duplicate accession number if provided
                if (!empty($record_data['accession_number'])) {
                    $existing_book = Record::where('accession_number', $record_data['accession_number'])->first();
                    if ($existing_book) {
                        $failed_count++;
                        $error = "Row " . ($row_index + 1) . ": Book with Accession Number {$record_data['accession_number']} already exists";
                        $errors[] = $error;
                        Log::warning($error);
                        $this->command->getOutput()->progressAdvance();
                        continue;
                    }
                }

                // Validate required fields
                if (!empty($record_data['title']) || !empty($record_data['accession_number'])) {
                    // Create the book record
                    $record = Record::create($record_data);
                    $record->book()->create($book_data);

                    foreach ($remark_data as $remark) {
                        $record->remarks()->create($remark);
                    }

                    $imported_count++;
                } else {
                    $failed_count++;
                    $error = "Row " . ($row_index + 1) . ": Missing required fields (title or accession_number)";
                    $errors[] = $error;
                    Log::warning($error);
                }

            } catch (\Exception $e) {
                $failed_count++;
                $error = "Row " . ($row_index + 1) . ": " . $e->getMessage();
                $errors[] = $error;
                Log::error($error, ['row_index' => $row_index + 1, 'data' => $row]);
            }

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();

        // Output results
        $this->command->info("Import completed!");
        $this->command->info("{$imported_count} book(s) imported successfully.");

        if ($failed_count > 0) {
            $this->command->warn("{$failed_count} row(s) failed.");

            if (!empty($errors)) {
                $this->command->error("Errors encountered:");
                foreach (array_slice($errors, 0, 10) as $error) { // Show first 10 errors
                    $this->command->error("  - {$error}");
                }

                if (count($errors) > 10) {
                    $this->command->warn("  ... and " . (count($errors) - 10) . " more errors. Check logs for details.");
                }
            }
        }
    }
}
