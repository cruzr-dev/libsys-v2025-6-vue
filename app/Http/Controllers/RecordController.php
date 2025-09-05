<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\DdcClassification;
use App\Models\Record;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Http\RedirectResponse
    {
        return to_route('records.all');
    }

    public function all()
    {
        return Inertia::render('records/Index', []);
    }

    public function fetchAll(Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        // Define valid relations for record types
        $validRelations = ['book', 'digitalResource', 'periodical', 'thesis'];

        $query = Record::query()
            ->whereNull('deleted_at')
            // Eager load all possible record type relations
            ->with($validRelations);

        // Handle search
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('accession_number', 'like', "%{$searchTerm}%")
                    ->orWhere('title', 'like', "%{$searchTerm}%");
            });
        }

        // Handle record type filtering
        if ($request->filled('record_type')) {
            $recordType = $request->get('record_type');
            if (in_array($recordType, $validRelations)) {
                $query->whereHas($recordType);
            }
        }

        // Handle sorting (only accession_number & title allowed)
        if ($request->filled('sort_field')) {
            $sortField = $request->get('sort_field');
            $sortDirection = $request->get('sort_direction', 'asc');

            if (in_array($sortField, ['accession_number', 'title'])) {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->latest('created_at');
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $records = $query->paginate($perPage);

        // Transform the collection to include record_type information
        $records->getCollection()->transform(function ($record) use ($validRelations) {
            // Determine the record type based on which relation exists
            foreach ($validRelations as $relation) {
                if ($record->relationLoaded($relation) && $record->$relation) {
                    $record->record_type = $relation;
                    break;
                }
            }

            return $record;
        });

        return $records;
    }

    public function fetchAllWelcome(Request $request): JsonResponse
    {
        // Validate per_page to ensure it's within acceptable bounds
        $perPage = in_array($request->get('per_page', 6), [3, 6, 9, 12]) ? $request->get('per_page') : 6;

        $query = Record::query()
            ->whereNull('deleted_at');

        // Handle filter parameter
        $filter = $request->get('filter', 'all');

        // Define valid filter types based on your relations
        $validFilters = ['all', 'books', 'digital_resources', 'periodicals', 'theses'];

        if (in_array($filter, $validFilters) && $filter !== 'all') {
            // Map filter names to relation names
            $relationMap = [
                'books' => 'book',
                'digital_resources' => 'digitalResource',
                'periodicals' => 'periodical',
                'theses' => 'thesis'
            ];

            if (isset($relationMap[$filter])) {
                $query->whereHas($relationMap[$filter]);
            }
        }
        // If filter is 'all' or any other value, show all records (no additional filtering)

        // Handle sorting (optional, aligning with original fetchAll)
        if ($request->filled('sort_field')) {
            $sortField = $request->get('sort_field');
            $sortDirection = $request->get('sort_direction', 'asc');

            if (in_array($sortField, ['created_at', 'accession_number', 'title'])) {
                $query->orderBy($sortField, $sortDirection);
            }
        } else {
            $query->latest('created_at'); // Default sort by created_at DESC
        }

        // Paginate results
        $records = $query->paginate($perPage);

        return response()->json([
            'data' => $records->items(),
            'current_page' => $records->currentPage(),
            'per_page' => $records->perPage(),
            'last_page' => $records->lastPage(),
            'total' => $records->total(),
        ]);
    }

    public function searchAuthor(Request $request): JsonResponse
    {
        $query = $request->get('q');
        $cutterNumber = $request->get('cutter_number');

        if ((empty($query) || strlen($query) < 2) && empty($cutterNumber)) {
            return response()->json([
                'authors' => [],
                'message' => 'Query or cutter_number must be provided and query must be at least 2 characters long'
            ]);
        }

        try {
            $authorsQuery = Author::query();
            $priorityResults = collect();
            $generalResults = collect();

            // First, if we have a cutter number, search for exact matches
            if ($cutterNumber) {
                $priorityResults = Author::where('author_number', $cutterNumber)
                    ->select(['id', 'name', 'author_number'])
                    ->get();
            }

            // Then search by query if provided
            if ($query && strlen($query) >= 2) {
                $generalQuery = Author::where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('author_number', 'LIKE', "%{$query}%");
                });

                // Exclude already found priority results
                if ($priorityResults->isNotEmpty()) {
                    $priorityIds = $priorityResults->pluck('id')->toArray();
                    $generalQuery->whereNotIn('id', $priorityIds);
                }

                $generalResults = $generalQuery
                    ->select(['id', 'name', 'author_number'])
                    ->orderBy('name')
                    ->limit(5 - $priorityResults->count()) // Ensure total doesn't exceed 5
                    ->get();
            }

            // Merge results with priority results first
            $authors = $priorityResults->concat($generalResults);

            return response()->json([
                'authors' => $authors->values(), // Reset array keys
                'count' => $authors->count(),
                'cutter_match' => $priorityResults->isNotEmpty() // Indicate if cutter number had matches
            ]);

        } catch (\Exception $e) {
            Log::error('Author search failed', [
                'query' => $query,
                'cutter_number' => $cutterNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'authors' => [],
                'error' => 'Author search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
