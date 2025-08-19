<?php

namespace App\Http\Controllers;

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
    public function index(Request $request): \Inertia\Response
    {
        $perPage = $request->input('per_page', 10);
        $sortField = $request->input('sort_field', null);
        $sortDirection = $request->input('sort_direction', 'asc');
        $filters = [];

        $userType = UserType::where('key', 'faculty')->first();
        $userTypeId = $userType ? $userType->id : null;

        // Capture search parameters
        $searchTerm = $request->input('search');
        if (!empty($searchTerm)) {
            $filters[] = [
                'id' => 'search',
                'value' => $searchTerm
            ];
        }

        $toggleableColumns = ['sex', 'middle_initial', 'card_number', 'school_id'];
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
                    // Default visibility based on column
                    $columnVisibility[$columnName] = $columnName === 'card_number' ? true : false;
                }
            }
        } else {
            // First visit - apply default visibility
            $columnVisibility = [
                'sex' => false,
                'middle_initial' => false,
                'school_id' => false,
                'card_number' => true,
            ];
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

        return Inertia::render('faculties/Index', [
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
    public function create()
    {
        $offices = \App\Models\Office::select('id', 'acronym', 'name')->get();

        return Inertia::render('faculties/Create', [
            'offices' => $offices
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
