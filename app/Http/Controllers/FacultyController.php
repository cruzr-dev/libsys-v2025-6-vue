<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Faculty;
use App\Models\User;
use App\Models\UserType;
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
        $query = User::with('userType');

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
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        // 1. Validation
        $validated = $request->validate([
            'library_id'     => 'required|integer|digits_between:1,10|unique:users,library_id',
            'first_name'     => 'required|string|max:50',
            'middle_initial' => 'nullable|string|max:1',
            'last_name'      => 'required|string|max:50',
            'sex'            => 'required|in:m,f',
            'contact_number' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
            'email'          => 'required|string|lowercase|email|max:255|unique:users,email',
            'role_title'     => 'required|string|max:50',
            'office_id'      => 'required|exists:offices,id',
        ]);

        // 2. Ensure user type exists
        $facultyType = UserType::where('key', 'faculty')->first();
        if (!$facultyType) {
            return back()->withInput()
                ->with('error', 'Faculty user type not found. Please contact the system administrator.');
        }

        // 3. Transaction
        try {
            DB::transaction(function () use ($validated, $facultyType) {
                $user = User::create([
                    'library_id'     => $validated['library_id'],
                    'first_name'     => $validated['first_name'],
                    'middle_initial' => $validated['middle_initial'],
                    'last_name'      => $validated['last_name'],
                    'sex'            => $validated['sex'],
                    'contact_number' => $validated['contact_number'],
                    'email'          => $validated['email'],
                    'user_type_id'   => $facultyType->id,
                ]);

                $user->faculty()->create([
                    'role_title' => $validated['role_title'],
                    'office_id'  => $validated['office_id'],
                ]);
            });

            return to_route('faculties.index')
                ->with('success', 'You successfully created a new Faculty');

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
    public function edit(Faculty $faculty)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Faculty $faculty)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faculty $faculty)
    {
        //
    }
}
