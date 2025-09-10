<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class RecordReportsController extends Controller
{
    public function BookBarcodes(): \Inertia\Response
    {
        return Inertia::render('reports/records/StudentBarcodes');
    }
}
