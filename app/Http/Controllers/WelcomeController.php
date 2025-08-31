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

}
