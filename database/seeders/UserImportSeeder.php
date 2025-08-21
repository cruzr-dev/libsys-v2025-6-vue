<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Program;
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
    public function run()
    {
        $csv_path = public_path('storage/csv_for_seeding/users.csv');

        try {
            if (!file_exists($csv_path)) {
                $this->command->error("CSV file not found at: {$csv_path}");
                return;
            }

            $csv_content = file_get_contents($csv_path);
            $csv_data = array_map('str_getcsv', explode("\n", $csv_content));

            // Remove header row if it exists
            $headers = array_shift($csv_data);

            $imported_count = 0;
            $failed_count = 0;
            $errors = [];

            $undergradStudTypeId = UserType::where('key', 'undergrad_student')->firstOrFail()->id;
            $gradStudTypeId = UserType::where('key', 'grad_student')->firstOrFail()->id;
            $facultyTypeId = UserType::where('key', 'faculty')->firstOrFail()->id;
            $staffTypeId = UserType::where('key', 'staff')->firstOrFail()->id;

            foreach ($csv_data as $row_index => $row) {
                try {
                    // Trim all values in the row and check for emptiness
                    $row = array_map('trim', $row);
                    if (empty(array_filter($row, fn($value) => $value !== '' && $value !== null))) {
                        Log::warning('Skipping empty row ' . ($row_index + 1) . ':', $row);
                        continue;
                    }

                    $library_id = null;
                    if (!empty($row[0]) && is_numeric($row[0]) && (int)$row[0] !== 0) {
                        $library_id = (int)$row[0];
                    }

                    $card_number = null;
                    if (!empty($row[1]) && is_numeric($row[1]) && (int)$row[1] !== 0) {
                        $card_number = (int)$row[1];
                    }

                    $school_id = null;
                    if (!empty($row[2]) && is_numeric($row[2]) && (int)$row[2] !== 0) {
                        $school_id = (int)$row[2];
                    }

                    $first_name = null;
                    if (!empty($row[3]) && is_string($row[3]) && $row[3] !== '') {
                        $first_name = $row[3];
                    }

                    $middle_initial = null;
                    if (!empty($row[4]) && is_string($row[4])) {
                        $clean = preg_replace('/[^\p{L}]/u', '', $row[4]);
                        if (strlen($clean) >= 1) {
                            $middle_initial = strtoupper($clean[0]);
                        }
                    }

                    $last_name = null;
                    if (!empty($row[5]) && is_string($row[5]) && $row[5] !== '') {
                        $last_name = $row[5];
                    }

                    $sex = null;
                    if (!empty($row[6]) && is_string($row[6]) && $row[6] !== '') {
                        if (strtolower($row[6]) === 'female') {
                            $sex = 'F';
                        } elseif (strtolower($row[6]) === 'male') {
                            $sex = 'M';
                        }
                    }

                    $contact_number = null;
                    if (!empty($row[7]) && is_string($row[7]) && $row[7] !== '') {
                        $digits_only = preg_replace('/\D/', '', $row[7]);
                        if (strlen($digits_only) === 10) {
                            $contact_number = $digits_only;
                        }
                    }

                    $email = null;
                    if (!empty($row[8]) && is_string($row[8]) && $row[8] !== '') {
                        if (filter_var($row[8], FILTER_VALIDATE_EMAIL)) {
                            $email = $row[8];
                        }
                    }

                    $user_profile = null;
                    if (!empty($row[10]) && is_string($row[10]) && $row[10] !== '') {
                        $user_profile = $row[10];
                    }

                    $user_type_id = null;
                    if (!empty($row[11]) && is_string($row[11]) && $row[11] !== '') {
                        $user_type = ucwords($row[11]);
                        if ($user_type === 'Undergraduate') {
                            $user_type_id = $undergradStudTypeId;
                        } elseif ($user_type === 'Graduate School' || $user_type === 'Graduate') {
                            $user_type_id = $gradStudTypeId;
                        } elseif ($user_type === 'Faculty') {
                            $user_type_id = $facultyTypeId;
                        } elseif ($user_type === 'Staff') {
                            $user_type_id = $staffTypeId;
                        }
                    }

                    $college_id = null;
                    $program_id = null;
                    try {
                        $college_id = College::where('code', 'NA')->firstOrFail()->id;
                        $program_id = Program::where('code', 'NA')->firstOrFail()->id;
                    } catch (ModelNotFoundException $e) {
                        // Handle the case where no college or program is found
                        \Log::error('No college or program found with code NA');
                        // Set defaults, throw a custom exception, or redirect
                        $college_id = null;
                        $program_id = null;
                    }

                    $user_data = [
                        'library_id' => $library_id,
                        'card_number' => $card_number,
                        'school_id' => $school_id,
                        'first_name' => $first_name,
                        'middle_initial' => $middle_initial,
                        'last_name' => $last_name,
                        'sex' => $sex,
                        'contact_number' => $contact_number,
                        'email' => $email,
                        'profile_image' => $user_profile,
                        'user_type_id' => $user_type_id,
                    ];

                    $student_data = [
                        'college_id' => $college_id,
                        'program_id' => $program_id,
                    ];

                    $user = User::create($user_data);

                    if ($user->user_type_id === $undergradStudTypeId
                        || $user->user_type_id === $gradStudTypeId) {
                        $user->student()->create($student_data);
                    }

                    $imported_count++;

                } catch (\Exception $e) {
                    $failed_count++;
                    $errors[] = "Row " . ($row_index + 1) . ": " . $e->getMessage();
                    Log::error("Processing row " . ($row_index + 1) . ": " . $e->getMessage(), ['data' => $row]);
                    if ($failed_count > 5) {
                        throw new \Exception("Too many errors occurred during processing");
                    }
                }
            }

            $this->command->info("Import completed! {$imported_count} user(s) imported successfully.");
            if ($failed_count > 0) {
                $this->command->warn("{$failed_count} row(s) failed.");
                foreach ($errors as $error) {
                    $this->command->error($error);
                }
            }

        } catch (\Exception $e) {
            $message = app()->environment('production')
                ? 'An unexpected error occurred during seeding.'
                : 'Seeding error: ' . $e->getMessage();
            $this->command->error($message);
            Log::error($message, ['exception' => $e]);
        }
    }
}
