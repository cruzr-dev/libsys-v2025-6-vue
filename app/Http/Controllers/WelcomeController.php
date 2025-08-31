<?php

namespace App\Http\Controllers;

use App\Models\BorrowingTransaction;
use App\Models\LibraryVisit;
use App\Models\Record;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WelcomeController extends Controller
{
    public function  index(Request $request)
    {
        return Inertia::render('Welcome', [
        ]);
    }

    public function searchRecords(Request $request)
    {
        // Validate the search query and optional type filter
        $request->validate([
            'q' => 'required|string|min:2|max:255',
            'type' => 'nullable|string|in:book,digital_resource,periodical,thesis'
        ]);

        $searchQuery = $request->get('q');
        $typeFilter = $request->get('type');
        $limit = $request->get('limit', 5); // Limit results to prevent overwhelming UI

        try {
            $query = Record::query()
                ->whereNull('deleted_at')
                ->where(function ($query) use ($searchQuery) {
                    $query->where('title', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('accession_number', 'LIKE', "%{$searchQuery}%");
                });

            // Apply type filter if specified
            if ($typeFilter) {
                switch ($typeFilter) {
                    case 'book':
                        $query->whereHas('book');
                        break;
                    case 'digital_resource':
                        $query->whereHas('digitalResource');
                        break;
                    case 'periodical':
                        $query->whereHas('periodical');
                        break;
                    case 'thesis':
                        $query->whereHas('thesis');
                        break;
                }
            }

            $records = $query
                ->with(['book', 'digitalResource', 'periodical', 'thesis']) // Eager load relations
                ->orderBy('title', 'asc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'records' => $records,
                'count' => $records->count(),
                'query' => $searchQuery,
                'type_filter' => $typeFilter
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
