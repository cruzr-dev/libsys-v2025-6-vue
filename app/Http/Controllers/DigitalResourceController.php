<?php

namespace App\Http\Controllers;

use App\Models\DigitalResource;
use App\Models\Record;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DigitalResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response
    {
        return Inertia::render('multimedia/Index', [
        ]);
    }

    public function fetchAll(Request $request)
    {
        $query = Record::query()
            ->whereNull('deleted_at')
            ->with('digitalResource');

        // Handle search
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('accession_number', 'like', "%{$searchTerm}%")
                    ->orWhere('title', 'like', "%{$searchTerm}%");
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

        return $records;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sources = Source::select('id', 'key', 'name')->orderBy('name')->get();

        return Inertia::render('multimedia/Create', [
            'sources'         => $sources,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        // 1. Validation based on your form inputs
        try {
            $request->validate([
                // Basic Information
                'accession_number'  => 'required|string|max:50|unique:records,accession_number',
                'title'             => 'required|string|max:255',
                'authors'           => 'required|array|min:1',
                'authors.*'         => 'string|max:255',
                'editors'           => 'nullable|array',
                'editors.*'         => 'string|max:255',
                'publication_year'  => 'required|integer|min:1000|max:' . date('Y'),
                'copyright_year'    => 'nullable|integer|min:1000|max:' . date('Y'),
                'producer'         => 'required|string|max:255',
                'language'          => 'required|in:Bisaya,Filipino,English',

                // Technical Specifications
                'collection_type'   => 'required|in:cd,duplicate_copy,cassette,vhs,cdr',
                'duration'          => 'nullable|string|max:50',
                'cover_image'       => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',

                // Administrative Information
                'source'            => 'required|string|exists:sources,key',
                'purchase_amount'   => 'nullable|numeric|min:0',
                'lot_cost'          => 'nullable|numeric|min:0',
                'supplier'          => 'nullable|string|max:255',
                'donated_by'        => 'nullable|string|max:255',

                // Content Description
                'overview'          => 'nullable|string',
                'subject_headings'  => 'nullable|array',
                'subject_headings.*'=> 'string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please fix the validation errors below.');
            throw $e;
        }

        // 2. Database Transaction
        try {
            DB::beginTransaction();

            // Handle cover image upload
            $coverImagePath = null;
            if ($request->hasFile('cover_image')) {
                $coverImagePath = $request->file('cover_image')->store('uploads/multimedia_covers', 'public');
            }

            // Create the generic record
            $record = Record::create([
                'accession_number'  => $request->accession_number,
                'title'             => $request->title,
                'subject_headings'  => $request->subject_headings,
                'date_received'     => now(),
                'added_by'          => auth()->id(),
            ]);

            // Create multimedia-specific record
            $record->digitalResource()->create([
                'authors'           => $request->authors,
                'editors'           => $request->editors,
                'publication_year'  => $request->publication_year,
                'copyright_year'    => $request->copyright_year,
                'producer'         => $request->producer,
                'language'          => $request->language,
                'collection_type'   => $request->collection_type,
                'duration'          => $request->duration,
                'cover_image'       => $coverImagePath,
                'source'            => $request->source,
                'purchase_amount'   => $request->purchase_amount,
                'lot_cost'          => $request->lot_cost,
                'supplier'          => $request->supplier,
                'donated_by'        => $request->donated_by,
                'overview'          => $request->overview,
            ]);

            DB::commit();

            return to_route('multimedia.index')
                ->with('success', 'Multimedia record created successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error creating Multimedia: ' . $e->getMessage(), [
                'request_data' => $request->except(['cover_image']),
                'exception'    => $e
            ]);
            return back()->withInput()->with('error', 'Database error occurred while creating the multimedia record.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unexpected error creating Multimedia: ' . $e->getMessage(), [
                'request_data' => $request->except(['cover_image']),
                'exception'    => $e
            ]);
            return back()->withInput()->with('error', 'An unexpected error occurred. Please try again.');
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(DigitalResource $digitalResource)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DigitalResource $digitalResource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DigitalResource $digitalResource)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DigitalResource $digitalResource)
    {
        //
    }
}
