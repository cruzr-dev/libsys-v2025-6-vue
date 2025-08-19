<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Major;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response
    {
        $perPage = $request->input('per_page', 10);
        $sortField = $request->input('sort_field', null);
        $sortDirection = $request->input('sort_direction', 'asc');
        $filters = [];

        $userType = UserType::where('key', 'student')->first();
        $userTypeId = $userType ? $userType->id : null;

        // Capture search parameters
        $searchTerm = $request->input('search');
        if (!empty($searchTerm)) {
            $filters[] = [
                'id' => 'search',
                'value' => $searchTerm
            ];
        }

        // Define all possible columns that can be toggled
        $toggleableColumns = ['sex', 'middle_initial']; // Add other columns as needed
        $columnVisibility = [];

        // Check for visibility parameters in the URL
        $hasVisibilityParams = collect($request->query())
            ->keys()
            ->contains(function ($key) {
                return str_starts_with($key, 'hide_') || str_starts_with($key, 'show_');
            });

        if ($hasVisibilityParams) {
            // Process explicit visibility settings from URL
            foreach ($toggleableColumns as $columnName) {
                if ($request->has("show_$columnName") && $request->input("show_$columnName") === '1') {
                    $columnVisibility[$columnName] = true;
                } elseif ($request->has("hide_$columnName") && $request->input("hide_$columnName") === '1') {
                    $columnVisibility[$columnName] = false;
                } else {
                    // Default to hidden if no explicit show/hide is provided
                    $columnVisibility[$columnName] = false;
                }
            }
        } else {
            // First visit - apply default hidden columns
            foreach ($toggleableColumns as $columnName) {
                $columnVisibility[$columnName] = false;
            }
        }

        $users = User::query()
            ->with('userType')
            ->when($userTypeId, function ($query, $userTypeId) {
                $query->where('user_type_id', $userTypeId);
            })
            ->when($searchTerm, function ($query, $searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('library_id', 'like', '%' . $searchTerm . '%');
                });
            })
            ->when($sortField, function ($query, $sortField) use ($sortDirection) {
                $query->orderBy($sortField, $sortDirection);
            })
            ->paginate(perPage: $perPage);

        return Inertia::render('students/Index', [
            'data' => $users,
            'filter' => $filters,
            'currentSortField' => $sortField,
            'currentSortDirection' => $sortDirection,
            'columnVisibility' => $columnVisibility,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): \Inertia\Response
    {
        $programs = Program::select('id', 'code', 'name')->orderBy('name')->get();
        $majors = Major::select('id', 'name')->orderBy('name')->get();
        $colleges = College::select('id', 'code', 'name')->orderBy('name')->get();

        return Inertia::render('students/Create', [
            'programs' => $programs,
            'majors' => $majors,
            'colleges' => $colleges,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        // 1. Validation
        try {
            $request->validate([
                'library_id'     => 'required|integer|digits_between:1,10|unique:users,library_id',
                'first_name'     => 'required|string|max:50',
                'middle_initial' => 'nullable|string|max:1',
                'last_name'      => 'required|string|max:50',
                'sex'            => 'required|in:m,f',
                'contact_number' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
                'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
                'student_type'   => 'required|in:undergraduate,graduate',
                'college_id'     => 'required|exists:colleges,id',
                'program_id'     => 'required|exists:programs,id',
                'major_id'       => 'nullable|exists:majors,id',
                'school_id'     => 'required|integer|digits_between:1,10|unique:users,school_id', // <-- added
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please fix the validation errors below.');
            throw $e; // Let Laravel handle redirect back
        }

        // 2. Database operations inside a transaction
        try {
            DB::beginTransaction();

            // Get the "student" user type dynamically
            $studentType = UserType::where('key', 'student')->firstOrFail();

            // Create the User record
            $user = User::create([
                'library_id'     => $request->library_id,
                'school_id'   => $request->school_id,
                'first_name'     => $request->first_name,
                'middle_initial' => $request->middle_initial,
                'last_name'      => $request->last_name,
                'sex'            => $request->sex,
                'contact_number' => $request->contact_number,
                'email'          => $request->email,
                'user_type_id'   => $studentType->id,
            ]);

            // Create the Student profile linked to the user
            $user->student()->create([
                'student_type' => $request->student_type,
                'college_id'   => $request->college_id,
                'program_id'   => $request->program_id,
                'major_id'     => $request->major_id,
            ]);

            DB::commit();

            return to_route('students.index')
                ->with('success', 'You successfully created a new Student');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            \Log::error('Student user type not found: ' . $e->getMessage(), [
                'request_data' => $request->except([]),
                'exception'    => $e
            ]);
            return back()->withInput()
                ->with('error', 'Student user type not found. Please contact the system administrator.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            \Log::error('Database error creating Student: ' . $e->getMessage(), [
                'request_data' => $request->except([]),
                'exception'    => $e
            ]);
            return back()->withInput()
                ->with('error', 'A database error occurred while creating the student. Please try again.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Unexpected error creating Student: ' . $e->getMessage(), [
                'request_data' => $request->except([]),
                'exception'    => $e
            ]);
            return back()->withInput()
                ->with('error', 'An unexpected error occurred. Please try again or contact support.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Student $student)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Student $student)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        //
    }
}
