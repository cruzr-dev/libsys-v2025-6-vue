<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Faculty;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
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
        try {
            $request->validate([
                'library_id'     => 'required|integer|digits_between:1,10|unique:users,library_id',
                'first_name'     => 'required|string|max:50',
                'middle_initial' => 'nullable|string|max:1',
                'last_name'      => 'required|string|max:50',
                'sex'            => 'required|in:m,f',
                'contact_number' => 'nullable|string|size:10|regex:/^[0-9]{10}$/',
                'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
                'role_title'     => 'required|string|max:50',
                'office_id'      => 'required|exists:offices,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please fix the validation errors below.');
            throw $e; // Let Laravel redirect back with errors
        }

        // 2. Database operations with error handling
        try {
            // You could fetch user type ID dynamically instead of hardcoding string
            $facultyType = UserType::where('key', 'faculty')->firstOrFail();

            // Create the User
            $user = User::create([
                'library_id'     => $request->library_id,
                'first_name'     => $request->first_name,
                'middle_initial' => $request->middle_initial,
                'last_name'      => $request->last_name,
                'sex'            => $request->sex,
                'contact_number' => $request->contact_number,
                'email'          => $request->email,
                'user_type_id'   => $facultyType->id,
            ]);

            // Create the Faculty profile linked to the user
            $user->faculty()->create([
                'role_title' => $request->role_title,
                'office_id'  => $request->office_id,
            ]);

            return to_route('faculties.index')
                ->with('success', 'You successfully created a new Faculty');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Faculty user type not found: ' . $e->getMessage(), [
                'request_data' => $request->except([]),
                'exception'    => $e
            ]);
            return back()->withInput()
                ->with('error', 'Faculty user type not found. Please contact the system administrator.');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error creating Faculty: ' . $e->getMessage(), [
                'request_data' => $request->except([]),
                'exception'    => $e
            ]);
            return back()->withInput()
                ->with('error', 'Database error occurred while creating the faculty. Please try again.');
        } catch (\Exception $e) {
            \Log::error('Unexpected error creating Faculty: ' . $e->getMessage(), [
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
