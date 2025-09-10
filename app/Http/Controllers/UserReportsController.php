<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class UserReportsController extends Controller
{
    public function barcodes(): \Inertia\Response
    {
        return Inertia::render('reports/users/Barcodes');
    }

    public function fetchBarcodes()
    {
        $query = User::whereNotNull('barcode_file')->get();

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
}
