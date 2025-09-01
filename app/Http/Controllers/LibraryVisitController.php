<?php

namespace App\Http\Controllers;

use App\Models\LibraryVisit;
use App\Models\User;
use App\Models\VisitPurpose;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LibraryVisitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response
    {
        $perPage = $request->input('per_page', 10);
        $sortField = $request->input('sort_field', null);
        $sortDirection = $request->input('sort_direction', 'desc');
        $filters = [];

        // Capture search parameters
        $searchTerm = $request->input('search');
        if (!empty($searchTerm)) {
            $filters[] = [
                'id' => 'search',
                'value' => $searchTerm
            ];
        }

        // Capture purpose filter
        $visitPurposes = $request->input('visit_purpose_id');
        if (!empty($visitPurposes)) {
            $filters[] = [
                'id' => 'visit_purpose_id',
                'value' => is_array($visitPurposes) ? $visitPurposes : [$visitPurposes]
            ];
        }

        $visits = LibraryVisit::with('user', 'visitPurpose')
            ->when($searchTerm, function ($query, $searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('id', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
                        });
                });
            })
            ->when($visitPurposes, function ($query, $visitPurposes) {
                $purposes = is_array($visitPurposes) ? $visitPurposes : [$visitPurposes];
                $query->whereIn('visit_purpose_id', $purposes);
            })
            ->when($sortField, function ($query, $sortField) use ($sortDirection) {
                $query->orderBy($sortField, $sortDirection);
            }, function ($query) {
                $query->latest();
            })
            ->paginate(perPage: $perPage);

        // Fetch available purposes for the filter dropdown
        $availablePurposes = VisitPurpose::select('id', 'name')->get()->map(function ($purpose) {
            return [
                'value' => (string) $purpose->id, // Cast to string for frontend compatibility
                'label' => $purpose->name,
            ];
        })->toArray();

        return Inertia::render('logger/Index', [
            'data' => $visits,
            'filter' => $filters,
            'currentSortField' => $sortField,
            'currentSortDirection' => $sortDirection,
            'availablePurposes' => $availablePurposes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        return Inertia::render('library-visit/Create', [
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $success_message = '';

        $user = User::where('id', $request->patron_id)->first();

        try {

            if (!$request->purpose_id) {
                $user_entry = LibraryVisit::where('user_id', $request->patron_id)->whereNull('exit_time')->first();

                $user_entry->update([
                    'exit_time' => now(),
                ]);
                $success_message = 'Thank you for visiting USeP Library!';

            } else {

                $purpose = VisitPurpose::where('id', $request->purpose_id)->first();
                if ($purpose) {
                    LibraryVisit::create([
                        'user_id' => $user->id,
                        'entry_time' => now(),
                        'visit_purpose_id' => $purpose->id,
                    ]);
                    $success_message = 'Welcome to USeP Library!';
                }
            }

            session()->flash('success', $success_message);

        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong');
            // Log the error
            \Log::error('Error: ' . $e->getMessage(), [
                'user_id' => $request->patron_id,
                'purpose_id' => $request->purpose_id,
                'exception' => $e
            ]);
        }

        return to_route('logger.create');

    }

    public function search(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'library_id' => 'required|string|min:2'
            ]);

            $cardNumber = $request->input('library_id');

            // Search for user with exact card number match
            $user = User::where('library_id', $cardNumber)
                ->select('id', 'first_name', 'last_name', 'email', 'library_id')
                ->first();

            if ($user) {
                return response()->json([
                    'success' => true,
                    'user' => $user,
                    'message' => 'User found successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'user' => null,
                    'message' => 'No user found with the provided card number'
                ], 404);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'user' => null,
                'message' => 'Invalid card number provided',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'user' => null,
                'message' => 'An error occurred while searching for the user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function searchByName(Request $request): ?JsonResponse
    {
        // Validate the search query
        $request->validate([
            'q' => 'required|string|min:2|max:255'
        ]);

        $searchQuery = $request->get('q');
        $limit = $request->get('limit', 5); // Limit results to prevent overwhelming UI

        try {
            $query = User::query()
                ->where(function ($query) use ($searchQuery) {
                    $query->where('first_name', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('last_name', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('email', 'LIKE', "%{$searchQuery}%");
                });

            $users = $query
                ->orderBy('first_name', 'asc')
                ->limit($limit)
                ->get(['first_name', 'last_name', 'library_id', 'email']);

            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => $users->count(),
                'query' => $searchQuery
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
