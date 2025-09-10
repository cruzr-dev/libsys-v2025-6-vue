<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserReportsController extends Controller
{
    public function barcodes(): \Inertia\Response
    {
        return Inertia::render('reports/users/Barcodes');
    }

    public function fetchBarcodes(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = User::whereNotNull('barcode_file');

        // Handle sorting
        if ($request->has('sort_field')) {
            $sortField = $request->get('sort_field');
            $sortDirection = $request->get('sort_direction', 'asc');

            // Validate sort field
            $allowedFields = ['bar_code', 'card_number', 'first_name', 'middle_initial', 'last_name', 'sex', 'email'];
            if (in_array($sortField, $allowedFields)) {
                $query->orderBy($sortField, $sortDirection);
            } else {
                $query->latest();
            }
        } else {
            $query->latest();
        }

        // Handle pagination
        $perPage = $request->get('per_page', 10);
        $paginatedData = $query->paginate($perPage);

        return response()->json($paginatedData);
    }}
