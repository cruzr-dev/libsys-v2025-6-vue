<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use App\Models\UserType;
use App\Services\BarcodeService;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('admins/Index');
    }

    public function fetchAll(Request $request)
    {
        $query = User::with(['userType', 'admin']);

        $query->whereHas('userType', function ($q) {
            $q->where('key', 'library_staff');
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
        $maxLibraryId = User::max('library_id') ?? 0;
        $maxCardNumber = User::max('card_number') ?? 0;

        return Inertia::render('admins/Create', [
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
            'library_id'     => 'required|integer|digits_between:1,10|unique:users,library_id',
            'card_number'    => 'required|integer|digits_between:1,10|unique:users,card_number',
            'first_name'     => 'required|string|max:50',
            'middle_initial' => 'nullable|string|max:1',
            'last_name'      => 'required|string|max:50',
            'sex'            => 'required|in:m,f',
            'profile_image'  => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'email'          => 'required|string|lowercase|email|max:255|unique:users,email',
            'password'       => [
                'required',
                'confirmed',
                Rules\Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ]);

        // 2. Ensure user type exists
        $adminType = UserType::where('key', 'library_staff')->first();
        if (!$adminType) {
            return back()->withInput()
                ->with('error', 'Admin user type not found. Please contact the system administrator.');
        }

        // 3. Transaction
        try {
            DB::transaction(function () use ($validated, $imageService, $barcodeService, $request, $adminType) {
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
                    'password'       => Hash::make($validated['password']),
                    'user_type_id'   => $adminType->id,
                    'profile_image'  => $filename,
                ]);

                $user->admin()->create([
                    'office'         => 'library',
                ]);

                $barcodeFile = $barcodeService->store($validated['card_number']);
                $user->update(['barcode_path' => $barcodeFile]);
            });

            return to_route('admins.index')
                ->with('success', 'You successfully created a new Admin with barcode');

        } catch (\Throwable $e) {
            \Log::error('Error creating admin user: ' . $e->getMessage(), [
                'library_id' => $validated['library_id'],
                'email'      => $validated['email'],
            ]);
            return back()->withInput()->with('error', 'Failed to create admin. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $admin = User::with('admin')->find($id);

        return Inertia::render('admins/Edit', [
            'admin' =>  $admin,
        ]);
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
