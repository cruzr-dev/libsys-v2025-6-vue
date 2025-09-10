<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class UserReportsController extends Controller
{
    public function barcodes(): \Inertia\Response
    {
        return Inertia::render('reports/users/Barcodes');
    }
}
