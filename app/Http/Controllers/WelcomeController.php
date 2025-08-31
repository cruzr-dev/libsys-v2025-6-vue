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
        // Validate the search query
        $request->validate([
            'q' => 'required|string|min:2|max:255'
        ]);

        $searchQuery = $request->get('q');
        $limit = $request->get('limit', 5); // Limit results to prevent overwhelming UI

        try {
            $records = Record::query()
                ->whereNull('deleted_at')
                ->where(function ($query) use ($searchQuery) {
                    $query->where('title', 'LIKE', "%{$searchQuery}%")
                        ->orWhere('accession_number', 'LIKE', "%{$searchQuery}%");
                })
                ->with(['book', 'digitalResource', 'periodical', 'thesis']) // Eager load relations
                ->orderBy('title', 'asc')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'records' => $records,
                'count' => $records->count(),
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
