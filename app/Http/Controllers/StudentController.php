<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Course;
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
    public function index(): \Inertia\Response
    {
        return Inertia::render('students/Index', [
        ]);
    }

    public function fetchAll(Request $request)
    {
        $query = User::with('userType');

        $query->whereHas('userType', function ($q) {
            $q->where('key', 'student');
        });

        // Handle search
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('library_id', 'like', "%{$searchTerm}%")
                    ->orWhere('card_number', 'like', "%{$searchTerm}%")
                    ->orWhere('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%");
            });
        }

        // Handle sorting
        if ($request->has('sort_field')) {
            $sortField = $request->get('sort_field');
            $sortDirection = $request->get('sort_direction', 'asc');
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->latest();
        }

        // Handle pagination
        $perPage = $request->get('per_page', 10);
        return $query->paginate($perPage);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): \Inertia\Response
    {
        $colleges = College::with([
            'courses:id,college_id,code,name',
            'courses.majors:id,course_id,name'
        ])
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        // Get the highest library_id and card_number from the students table
        $maxLibraryId = User::max('library_id') ?? 0;
        $maxCardNumber = User::max('card_number') ?? 0;

        return Inertia::render('students/Create', [
            'colleges' => $colleges,
            'nextLibraryId' => $maxLibraryId + 1,
            'nextCardNumber' => $maxCardNumber + 1,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        // 1. Validation (let Laravel handle exceptions + redirect with errors)
        $validated = $request->validate([
            'library_id'     => 'required|integer|min:1|max:9999999999|unique:users,library_id',
            'first_name'     => 'required|string|max:50',
            'middle_initial' => 'nullable|string|max:1',
            'last_name'      => 'required|string|max:50',
            'sex'            => 'required|in:m,f',
            'contact_number' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
            'email'          => 'required|string|lowercase|email|max:255|unique:users,email',
            'card_number'    => 'required|integer|min:1|max:9999999999|unique:users,card_number',
            'college_id'     => 'required|exists:colleges,id',
            'course_id'      => 'required|exists:courses,id',
            'major_id' => [
                'nullable',
                'exists:majors,id',
                function ($attribute, $value, $fail) use ($request) {
                    $course = Course::find($request->input('course_id'));

                    if (!$course) {
                        return; // avoid running if no course
                    }

                    $hasMajors = Major::where('course_id', $course->id)->exists();

                    if ($hasMajors && is_null($value)) {
                        $fail('The major field is required when the selected course has majors.');
                    }
                },
            ],
        ]);

        // 2. Ensure the UserType exists before starting transaction
        $studentType = UserType::where('key', 'student')->first();
        if (! $studentType) {
            \Log::warning('Student user type not found.', [
                'library_id' => $validated['library_id'],
                'email'      => $validated['email'],
            ]);

            return back()->withInput()
                ->with('error', 'Student user type not found. Please contact the system administrator.');
        }

        // 3. Database operations inside transaction
        try {
            DB::transaction(function () use ($validated, $studentType) {
                $user = User::create([
                    'library_id'     => $validated['library_id'],
                    'card_number'    => $validated['card_number'],
                    'first_name'     => $validated['first_name'],
                    'middle_initial' => $validated['middle_initial'],
                    'last_name'      => $validated['last_name'],
                    'sex'            => $validated['sex'],
                    'email'          => $validated['email'],
                    'user_type_id'   => $studentType->id,
                ]);

                $user->student()->create([
                    'contact_number' => $validated['contact_number'],
                    'college_id'     => $validated['college_id'],
                    'course_id'      => $validated['course_id'],
                    'major_id'       => $validated['major_id'],
                ]);

            });

            return to_route('students.index')
                ->with('success', 'You successfully created a new Student');

        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error creating Student: '.$e->getMessage(), [
                'library_id' => $validated['library_id'],
                'email'      => $validated['email'],
            ]);
            return back()->withInput()
                ->with('error', 'A database error occurred while creating the student. Please try again.');

        } catch (\Throwable $e) {
            // Let Laravel handle framework-related exceptions (e.g. HttpException)
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                throw $e;
            }

            \Log::error('Unexpected error creating Student: '.$e->getMessage(), [
                'library_id' => $validated['library_id'],
                'email'      => $validated['email'],
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
