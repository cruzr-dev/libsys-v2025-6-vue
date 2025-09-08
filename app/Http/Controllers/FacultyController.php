<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Faculty;
use App\Models\User;
use App\Models\UserType;
use App\Services\BarcodeService;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FacultyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('faculties/Index');
    }

    public function fetchAll(Request $request)
    {
        $query = User::with(['userType', 'faculty.college', 'faculty.course']);

        $query->whereHas('userType', function ($q) {
            $q->where('key', 'faculty');
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
    public function create()
    {
        $colleges = College::with(
            'courses:id,college_id,code,name'
        )
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        // Get the highest library_id and card_number from the users table
        $maxLibraryId = User::max('library_id') ?? 0;
        $maxCardNumber = User::max('card_number') ?? 0;

        return Inertia::render('faculties/Create', [
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

        try {

            $validated = $request->validate([
                'library_id'     => 'required|integer|digits_between:1,10|unique:users,library_id',
                'card_number'    => 'required|integer|min:1|max:9999999999|unique:users,card_number',
                'first_name'     => 'required|string|max:50',
                'middle_initial' => 'nullable|string|max:1',
                'last_name'      => 'required|string|max:50',
                'sex'            => 'required|in:m,f',
                'email'          => 'required|string|lowercase|email|max:255|unique:users,email',
                'college_id'     => 'required|exists:colleges,id',
                'course_id'      => 'required|exists:courses,id',
                'profile_image'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

        }  catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->validator)->with('error', 'Please correct the errors in the form.');
        }

        // 2. Ensure user type exists
        $facultyType = UserType::where('key', 'faculty')->first();
        if (!$facultyType) {
            return back()->withInput()
                ->with('error', 'Faculty user type not found. Please contact the system administrator.');
        }

        // 3. Transaction
        try {
            DB::transaction(function () use ($validated, $facultyType, $request, $imageService, $barcodeService) {
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
                    'user_type_id'   => $facultyType->id,
                    'profile_image'  => $filename,
                ]);

                $user->faculty()->create([
                    'college_id' => $validated['college_id'],
                    'course_id'  => $validated['course_id'],
                ]);

                $barcodeFile = $barcodeService->store($validated['card_number']);
                $user->update(['barcode_path' => $barcodeFile]);
            });

            return to_route('faculties.index')
                ->with('success', 'You successfully created a new Faculty with barcode');

        } catch (\Throwable $e) {
            \Log::error('Error creating Faculty: ' . $e->getMessage(), [
                'library_id' => $validated['library_id'],
                'email'      => $validated['email'],
            ]);
            return back()->withInput()->with('error', 'Failed to create faculty. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Faculty $faculty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): \Inertia\Response
    {
        $faculty = User::with('faculty')->find($id);

        $colleges = College::with(
            'courses:id,college_id,code,name'
        )
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('faculties/Edit', [
            'colleges' => $colleges,
            'faculty' =>  $faculty,
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

        $user = User::findOrFail($id);

        try {
            $validated = $request->validate([
                'library_id'     => 'required|integer|digits_between:1,10|unique:users,library_id,' . $user->id,
                'card_number'    => 'required|integer|min:1|max:9999999999|unique:users,card_number,' . $user->id,
                'first_name'     => 'required|string|max:50',
                'middle_initial' => 'nullable|string|max:1',
                'last_name'      => 'required|string|max:50',
                'sex'            => 'required|in:m,f',
                'email'          => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
                'college_id'     => 'required|exists:colleges,id',
                'course_id'      => 'required|exists:courses,id',
                'profile_image'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->validator)->with('error', 'Please fix the following errors:');
        }

        // Ensure faculty user type exists
        $facultyType = UserType::where('key', 'faculty')->first();
        if (!$facultyType) {
            return back()->withInput()->with('error', 'Faculty user type not found. Please contact the system administrator.');
        }

        try {
            DB::transaction(function () use ($validated, $user, $request, $imageService, $barcodeService, $facultyType) {
                // Prepare user update data
                $userUpdateData = [
                    'library_id'     => $validated['library_id'],
                    'card_number'    => $validated['card_number'],
                    'first_name'     => $validated['first_name'],
                    'middle_initial' => $validated['middle_initial'],
                    'last_name'      => $validated['last_name'],
                    'sex'            => $validated['sex'],
                    'email'          => $validated['email'],
                    'user_type_id'   => $facultyType->id,
                ];

                // Handle profile image update
                if ($request->hasFile('profile_image') && $request->file('profile_image')->isValid()) {
                    if ($user->profile_image) {
                        $imageService->delete($user->profile_image);
                    }
                    $filename = $imageService->store($request->file('profile_image'), $validated['library_id']);
                    $userUpdateData['profile_image'] = $filename;
                }

                // Update user data
                $user->update($userUpdateData);

                // Update faculty data
                $user->faculty()->update([
                    'college_id' => $validated['college_id'],
                    'course_id'  => $validated['course_id'],
                ]);

                // Regenerate barcode if card number changed
                if ($user->wasChanged('card_number')) {
                    if ($user->barcode_path) {
                        $barcodeService->delete($user->barcode_path);
                    }
                    $barcodeFile = $barcodeService->store($validated['card_number']);
                    $user->update(['barcode_path' => $barcodeFile]);
                }
            });

            return to_route('faculties.index')->with('success', 'Faculty updated successfully');
        } catch (\Throwable $e) {
            \Log::error('Error updating Faculty: ' . $e->getMessage(), [
                'user_id'    => $user->id,
                'library_id' => $validated['library_id'],
                'email'      => $validated['email'],
            ]);
            return back()->withInput()->with('error', 'Failed to update faculty. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            User::findOrFail($id)->delete();

            return redirect()
                ->route('faculties.index')
                ->with('success', 'Student deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('faculties.index')
                ->with('error', 'Failed to delete the student.');
        }

    }
}
