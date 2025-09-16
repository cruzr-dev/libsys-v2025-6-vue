<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopFiguresStatisticsController extends Controller
{
    public function topFigures(): \Inertia\Response
    {
        return Inertia::render('statistics/topFigures');
    }
}
