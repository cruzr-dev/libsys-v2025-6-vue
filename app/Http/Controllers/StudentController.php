<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Course;
use App\Models\Major;
use App\Models\User;
use App\Models\UserType;
use App\Services\BarcodeService;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('students/Index');
    }

    public function fetchAll(Request $request)
    {
        $query = User::with(['userType', 'student.college', 'student.course', 'student.major']);

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
        ])->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        // Get the highest library_id and card_number from the users table
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
    public function store(
        Request $request,
        ProfileImageService $imageService,
        BarcodeService $barcodeService
    ): \Illuminate\Http\RedirectResponse {
        // 1. Validation
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

                    if ($course && Major::where('course_id', $course->id)->exists() && is_null($value)) {
                        $fail('The major field is required when the selected course has majors.');
                    }
                },
            ],
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // 2. Ensure user type exists
        $studentType = UserType::where('key', 'student')->first();
        if (! $studentType) {
            return back()->withInput()
                ->with('error', 'GraduateStudent user type not found. Please contact the system administrator.');
        }

        // 3. Transaction
        try {
            DB::transaction(function () use ($validated, $studentType, $request, $imageService, $barcodeService) {
                $filename = null;

                if ($request->hasFile('profile_image')) {
                    $filename = $imageService->store($request->file('profile_image'), $validated['library_id']);
                }

                $user = User::create([
                    'library_id'     => $validated['library_id'],
                    'card_number'    => $validated['card_number'],
                    'first_name'     => $validated['first_name'],
                    'middle_initial' => $validated['middle_initial'],
                    'last_name'      => $validated['last_name'],
                    'sex'            => $validated['sex'],
                    'email'          => $validated['email'],
                    'user_type_id'   => $studentType->id,
                    'profile_image'  => $filename,
                ]);

                $user->student()->create([
                    'contact_number' => $validated['contact_number'],
                    'college_id'     => $validated['college_id'],
                    'course_id'      => $validated['course_id'],
                    'major_id'       => $validated['major_id'],
                ]);

                $barcodeFile = $barcodeService->store($validated['card_number']);
                $user->update(['barcode_path' => $barcodeFile]);
            });

            return to_route('students.index')
                ->with('success', 'You successfully created a new GraduateStudent with barcode');

        } catch (\Throwable $e) {
            \Log::error('Error creating GraduateStudent: ' . $e->getMessage(), [
                'library_id' => $validated['library_id'],
                'email'      => $validated['email'],
            ]);
            return back()->withInput()->with('error', 'Failed to create student. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $student = User::with('student')->find($id);

        $colleges = College::with([
            'courses:id,college_id,code,name',
            'courses.majors:id,course_id,name'
        ])->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('students/Edit', [
            'colleges' => $colleges,
            'student' => $student,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
                $id,
        ProfileImageService $imageService,
        BarcodeService $barcodeService
    ): \Illuminate\Http\RedirectResponse {

        $user = User::find($id);

        // 1. Validation
        $validated = $request->validate([
            'library_id'     => 'required|integer|min:1|max:9999999999|unique:users,library_id,' . $user->id,
            'first_name'     => 'required|string|max:50',
            'middle_initial' => 'nullable|string|max:1',
            'last_name'      => 'required|string|max:50',
            'sex'            => 'required|in:m,f',
            'contact_number' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
            'email'          => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
            'card_number'    => 'required|integer|min:1|max:9999999999|unique:users,card_number,' . $user->id,
            'college_id'     => 'required|exists:colleges,id',
            'course_id'      => 'required|exists:courses,id',
            'major_id' => [
                'nullable',
                'exists:majors,id',
                function ($attribute, $value, $fail) use ($request) {
                    $course = Course::find($request->input('course_id'));

                    if ($course && Major::where('course_id', $course->id)->exists() && is_null($value)) {
                        $fail('The major field is required when the selected course has majors.');
                    }
                },
            ],
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // 2. Transaction
        try {
            DB::transaction(function () use ($validated, $user, $request, $imageService, $barcodeService) {

                // Prepare user update data (excluding profile_image initially)
                $userUpdateData = [
                    'library_id'     => $validated['library_id'],
                    'card_number'    => $validated['card_number'],
                    'first_name'     => $validated['first_name'],
                    'middle_initial' => $validated['middle_initial'],
                    'last_name'      => $validated['last_name'],
                    'sex'            => $validated['sex'],
                    'email'          => $validated['email'],
                ];

                // Handle profile image update
                if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
                    // Delete old profile image if it exists
                    if ($user->profile_image) {
                        $imageService->delete($user->profile_image);
                    }

                    // Store new image
                    $filename = $imageService->store($request->file('profile_image'), $validated['library_id']);
                    $userUpdateData['profile_image'] = $filename;
                }
                // If no new file is uploaded, keep the existing image (don't modify profile_image field)

                // Update user data
                $user->update($userUpdateData);

                // Update student data
                $user->student()->update([
                    'contact_number' => $validated['contact_number'] ?? null,
                    'college_id'     => $validated['college_id'],
                    'course_id'      => $validated['course_id'],
                    'major_id'       => $validated['major_id'] ?? null,
                ]);

                // Regenerate barcode if card number changed
                if ($user->wasChanged('card_number')) {
                    // Delete old barcode if it exists
                    if ($user->barcode_path) {
                        $barcodeService->delete($user->barcode_path);
                    }

                    $barcodeFile = $barcodeService->store($validated['card_number']);
                    $user->update(['barcode_path' => $barcodeFile]);
                }
            });

            return to_route('students.index')
                ->with('success', 'GraduateStudent updated successfully');

        } catch (\Throwable $e) {
            \Log::error('Error updating GraduateStudent: ' . $e->getMessage(), [
                'user_id'    => $user->id,
                'library_id' => $validated['library_id'],
                'email'      => $validated['email'],
            ]);
            return back()->withInput()->with('error', 'Failed to update student. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $student = User::findOrFail($id);
            $student->delete();

            return redirect()
                ->route('students.index')
                ->with('success', 'GraduateStudent deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('students.index')
                ->with('error', 'Failed to delete the student.');
        }
    }

}
