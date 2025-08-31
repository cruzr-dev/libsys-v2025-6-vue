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
        $query = Record::query()
            ->whereNull('deleted_at') // respect soft deletes
            ->with(['book.authors', 'book.editors']); // Eager load book, authors, and editors relationships

        // Handle search
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('accession_number', 'like', "%{$searchTerm}%")
                    ->orWhere('title', 'like', "%{$searchTerm}%")
                    ->orWhereHas('book.authors', function ($authorQuery) use ($searchTerm) {
                        $authorQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('book.editors', function ($editorQuery) use ($searchTerm) {
                        $editorQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
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

        // Transform the data to include author and editor information
        $records->getCollection()->transform(function ($record) {
            // Transform authors
            if ($record->book && $record->book->authors && $record->book->authors->count() > 0) {
                $record->authors_list = $record->book->authors->pluck('name')->join(', ');
            } else {
                $record->authors_list = null;
            }

            // Transform editors
            if ($record->book && $record->book->editors && $record->book->editors->count() > 0) {
                $record->editors_list = $record->book->editors->pluck('name')->join(', ');
            } else {
                $record->editors_list = null;
            }

            return $record;
        });

        return $records;
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
