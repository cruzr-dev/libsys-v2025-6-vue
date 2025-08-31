<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Models\Thesis;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ThesisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('thesis/Index');
    }

    public function fetchAll(Request $request)
    {
        $query = Record::query()
            ->whereNull('deleted_at')
            ->whereHas('thesis') // Only include records with a digitalResource
            ->with('thesis');

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
    public function show(Thesis $thesis)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Thesis $thesis)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Thesis $thesis)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Thesis $thesis)
    {
        //
    }
}
