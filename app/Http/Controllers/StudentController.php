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

        // ✅ Only include grad_student and undergraduate_student
        $query->whereHas('userType', function ($q) {
            $q->whereIn('key', ['grad_student', 'undergrad_student']);
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

        return Inertia::render('students/Create', [
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
                'course_id'     => 'required|exists:courses,id',
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
            $studentType = UserType::where('key', 'undergrad_student')->firstOrFail();

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
                'course_id'   => $request->course_id,
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
