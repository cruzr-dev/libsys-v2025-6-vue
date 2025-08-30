<?php

namespace App\Http\Controllers;

use App\Models\DdcClassification;
use App\Models\Record;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return to_route('records.all');
    }

    public function all()
    {
        return Inertia::render('records/Index', []);
    }

    public function fetchAll(Request $request)
    {
        $query = Record::with(['book', 'addedBy', 'updatedBy', 'importedBy'])
            ->whereNull('deleted_at'); // Respect soft deletes

        // Handle search
        if ($request->has('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('accession_number', 'like', "%{$searchTerm}%")
                    ->orWhere('title', 'like', "%{$searchTerm}%")
                    ->orWhere('subject', 'like', "%{$searchTerm}%")
                    ->orWhereHas('book', function ($q) use ($searchTerm) {
                        $q->where('isbn', 'like', "%{$searchTerm}%")
                            ->orWhere('call_number', 'like', "%{$searchTerm}%")
                            ->orWhere('publisher', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Handle sorting
        if ($request->has('sort_field')) {
            $sortField = $request->get('sort_field');
            $sortDirection = $request->get('sort_direction', 'asc');

            // Handle sorting for fields in the books table
            if (in_array($sortField, ['isbn', 'publication_year', 'call_number'])) {
                $query->join('books', 'records.id', '=', 'books.record_id')
                    ->select('records.*') // Avoid selecting books columns
                    ->orderBy('books.' . $sortField, $sortDirection);
            } else {
                $query->orderBy('records.' . $sortField, $sortDirection);
            }
        } else {
            $query->latest('records.created_at');
        }

        // Handle pagination
        $perPage = $request->get('per_page', 10);

        // Handle column visibility
        $with = ['book'];
        if ($request->has('show_added_by') && $request->get('show_added_by') === '1') {
            $with[] = 'addedBy';
        }
        if ($request->has('show_updated_by') && $request->get('show_updated_by') === '1') {
            $with[] = 'updatedBy';
        }
        if ($request->has('show_imported_by') && $request->get('show_imported_by') === '1') {
            $with[] = 'importedBy';
        }
        $query->with($with);

        return $query->paginate($perPage);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Record $record)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Record $record)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Record $record)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Record $record)
    {
        //
    }
}
