<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Course;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class UserImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = public_path('storage/csv_for_seeding/users.csv');

        try {
            $csvData = $this->loadCsvData($csvPath);
            $userTypeIds = $this->getUserTypeIds();
            $this->processCsvRows($csvData, $userTypeIds);
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
     * Retrieve user type IDs from the database.
     *
     * @return array
     */
    private function getUserTypeIds(): array
    {
        return [
            'undergrad' => UserType::where('key', 'undergrad_student')->firstOrFail()->id,
            'grad' => UserType::where('key', 'grad_student')->firstOrFail()->id,
            'faculty' => UserType::where('key', 'faculty')->firstOrFail()->id,
            'staff' => UserType::where('key', 'staff')->firstOrFail()->id,
        ];
    }

    /**
     * Process CSV rows and import users.
     *
     * @param array $csvData
     * @param array $userTypeIds
     */
    private function processCsvRows(array $csvData, array $userTypeIds): void
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
                    continue;
                }

                $userData = $this->parseUserData($row, $userTypeIds);
                $user = User::create($userData);

                if (in_array($user->user_type_id, [$userTypeIds['undergrad'], $userTypeIds['grad']])) {
                    $this->createStudentRecord($user, $row);
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
     * Parse user data from a CSV row.
     *
     * @param array $row
     * @param array $userTypeIds
     * @return array
     */
    private function parseUserData(array $row, array $userTypeIds): array
    {
        return [
            'library_id' => $this->parseNumeric($row[0] ?? null),
            'card_number' => $this->parseNumeric($row[1] ?? null),
            'school_id' => $this->parseNumeric($row[2] ?? null),
            'first_name' => $this->parseString($row[3] ?? null),
            'middle_initial' => $this->parseMiddleInitial($row[4] ?? null),
            'last_name' => $this->parseString($row[5] ?? null),
            'sex' => $this->parseSex($row[6] ?? null),
            'email' => $this->parseEmail($row[8] ?? null),
            'profile_image' => $this->parseString($row[10] ?? null),
            'user_type_id' => $this->parseUserType($row[11] ?? null, $userTypeIds),
        ];
    }

    /**
     * Parse numeric values.
     *
     * @param mixed $value
     * @return int|null
     */
    private function parseNumeric($value): ?int
    {
        return (!empty($value) && is_numeric($value) && (int)$value !== 0) ? (int)$value : null;
    }

    /**
     * Parse string values.
     *
     * @param mixed $value
     * @return string|null
     */
    private function parseString($value): ?string
    {
        return (!empty($value) && is_string($value) && $value !== '') ? $value : null;
    }

    /**
     * Parse middle initial.
     *
     * @param mixed $value
     * @return string|null
     */
    private function parseMiddleInitial($value): ?string
    {
        if (!empty($value) && is_string($value)) {
            $clean = preg_replace('/[^\p{L}]/u', '', $value);
            return strlen($clean) >= 1 ? strtoupper($clean[0]) : null;
        }
        return null;
    }

    /**
     * Parse sex value.
     *
     * @param mixed $value
     * @return string|null
     */
    private function parseSex($value): ?string
    {
        if (!empty($value) && is_string($value)) {
            $value = strtolower($value);
            return $value === 'female' ? 'F' : ($value === 'male' ? 'M' : null);
        }
        return null;
    }

    /**
     * Parse email value.
     *
     * @param mixed $value
     * @return string|null
     */
    private function parseEmail($value): ?string
    {
        return (!empty($value) && is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) ? $value : null;
    }

    /**
     * Parse user type.
     *
     * @param mixed $value
     * @param array $userTypeIds
     * @return int|null
     */
    private function parseUserType($value, array $userTypeIds): ?int
    {
        if (!empty($value) && is_string($value)) {
            $userType = ucwords($value);
            return match ($userType) {
                'Undergraduate' => $userTypeIds['undergrad'],
                'Graduate', 'Graduate School' => $userTypeIds['grad'],
                'Faculty' => $userTypeIds['faculty'],
                'Staff' => $userTypeIds['staff'],
                default => null,
            };
        }
        return null;
    }

    /**
     * Create student record for a user.
     *
     * @param User $user
     * @param array $row
     */
    private function createStudentRecord(User $user, array $row): void
    {
        try {
            // Get a random college
            $college = College::inRandomOrder()->first();

            if (!$college) {
                Log::error('No colleges found in database');
                $collegeId = null;
                $courseId = null;
                $majorId = null;
            } else {
                $collegeId = $college->id;

                // Get a random course from the selected college
                $course = $college->courses()->inRandomOrder()->first();

                if (!$course) {
                    Log::error("No courses found for college: {$college->code}");
                    $courseId = null;
                    $majorId = null;
                } else {
                    $courseId = $course->id;

                    // Get a random major from the selected course (if any exist)
                    $major = $course->majors()->inRandomOrder()->first();
                    $majorId = $major ? $major->id : null;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error selecting random college/course: ' . $e->getMessage());
            $collegeId = null;
            $courseId = null;
            $majorId = null;
        }

        $contactNumber = $this->parseContactNumber($row[7] ?? null);
        $studentData = [
            'college_id' => $collegeId,
            'course_id' => $courseId,
            'major_id' => $majorId,
            'contact_number' => $contactNumber,
        ];

        $user->student()->create($studentData);
    }

    /**
     * Parse contact number.
     *
     * @param mixed $value
     * @return string|null
     */
    private function parseContactNumber($value): ?string
    {
        if (!empty($value) && is_string($value)) {
            $digitsOnly = preg_replace('/\D/', '', $value);
            return strlen($digitsOnly) === 10 ? $digitsOnly : null;
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
        $this->command->info("Import completed! {$importedCount} user(s) imported successfully.");

        if ($failedCount > 0) {
            $this->command->warn("{$failedCount} row(s) failed.");
            foreach ($errors as $error) {
                $this->command->error($error);
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
