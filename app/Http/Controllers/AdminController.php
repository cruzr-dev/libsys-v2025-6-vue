<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminController extends Controller
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

        // Get the admin user type ID by key
        $adminUserType = UserType::where('key', 'staff_admin')->first();
        $adminUserTypeId = $adminUserType ? $adminUserType->id : null;

        // Set default filter to admin user type, or use request parameter
        $user_type_id = $request->input('user_type_id', $adminUserTypeId);

        // Handle user_type_id filter (can be single value or array)
        if (!empty($user_type_id)) {
            $filters[] = [
                'id' => 'user_type_id',
                'value' => $user_type_id
            ];
        }

        // Capture search parameters
        $searchTerm = $request->input('search');
        if (!empty($searchTerm)) {
            $filters[] = [
                'id' => 'search',
                'value' => $searchTerm
            ];
        }

        // Get all user types for filter dropdown
        $userTypes = UserType::select('id', 'name')
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->with('userType')
            ->when($user_type_id, function ($query, $user_type_id) {
                // Handle both single values and arrays
                if (is_array($user_type_id) && !empty($user_type_id)) {
                    // Convert string values to integers if needed
                    $userTypeIds = array_map('intval', array_filter($user_type_id));
                    if (!empty($userTypeIds)) {
                        $query->whereIn('user_type_id', $userTypeIds);
                    }
                } elseif (!empty($user_type_id)) {
                    $query->where('user_type_id', intval($user_type_id));
                }
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

        return Inertia::render('admins/Index', [
            'data' => $users,
            'filter' => $filters,
            'userTypes' => $userTypes,
            'currentSortField' => $sortField,
            'currentSortDirection' => $sortDirection,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admins/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            $request->validate([
                'library_id' => 'required|integer|digits_between:1,10|unique:users,library_id',
                'first_name' => 'required|string|max:50',
                'middle_initial' => 'nullable|string|max:1',
                'last_name' => 'required|string|max:50',
                'sex' => 'required|in:m,f',
                'role_title' => 'required|string|max:100',
                'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
                'password' => ['required', 'confirmed', Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please fix the validation errors below.');
            throw $e; // Re-throw to let Laravel handle the redirect with errors
        }

        try {
            // Database operations that could fail
            $adminType = UserType::where('key', 'staff_admin')->firstOrFail();

            $user = User::create([
                'library_id' => $request->library_id,
                'first_name' => $request->first_name,
                'middle_initial' => $request->middle_initial,
                'last_name' => $request->last_name,
                'sex' => $request->sex,
                'contact_number' => $request->contact_number,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type_id' => $adminType->id,
            ]);

            $user->admin()->create([
                'role_title' => $request->role_title,
            ]);

            // event(new Registered($user));
            // i trigger ana ang mailler

            return to_route('admins.index')->with('success', 'You successfully created a new Admin');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Error creating admin user: ' . $e->getMessage(), [
                'request_data' => $request->except(['password', 'password_confirmation']),
                'exception' => $e
            ]);
            return back()->withInput()->with('error', 'Admin user type not found. Please contact system administrator.');
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Error creating admin user: ' . $e->getMessage(), [
                'request_data' => $request->except(['password', 'password_confirmation']),
                'exception' => $e
            ]);
            // Handle database constraint violations, connection issues, etc.
            return back()->withInput()->with('error', 'Database error occurred while creating the admin. Please try again.');
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            \Log::error('Error creating admin user: ' . $e->getMessage(), [
                'request_data' => $request->except(['password', 'password_confirmation']),
                'exception' => $e
            ]);

            return back()->withInput()->with('error', 'An unexpected error occurred. Please try again or contact support.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            // Force a complete page reload to ensure fresh data
            $queryParams = request()->only(['per_page', 'sort_field', 'sort_direction', 'user_type_id', 'search', 'page']);
            $url = route('admins.index', $queryParams);

            // Add success message to session
            session()->flash('success', 'Admin deleted successfully');

            return \Inertia\Inertia::location($url);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Admin not found.');
        } catch (\Exception $e) {
            \Log::error('Error deleting admin: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while deleting the admin.');
        }
    }
}
