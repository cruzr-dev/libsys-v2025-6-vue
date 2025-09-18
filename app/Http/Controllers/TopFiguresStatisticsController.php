<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LibraryVisit;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TopFiguresStatisticsController extends Controller
{
    public function topFigures(): \Inertia\Response
    {
        return Inertia::render('statistics/topFigures');
    }

    public function getTopLibraryVisits(Request $request)
    {
        $entries = $request->input('entries', 5);
        $year = $request->input('year', now()->year);
        $quarter = $request->input('quarter', 'Q1');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Define quarter date ranges according to requirements
        $quarterRanges = [
            'Q1' => ['01-01', '03-31'], // Jan, Feb, Mar
            'Q2' => ['04-01', '06-30'], // Apr, May, Jun
            'Q3' => ['07-01', '09-30'], // Jul, Aug, Sep
            'Q4' => ['10-01', '12-31'], // Oct, Nov, Dec
        ];

        // Build the query
        $query = LibraryVisit::query()
            ->join('users', 'library_visits.user_id', '=', 'users.id')
            ->whereIn('users.user_type_id', [3, 4]) // Only undergraduate (3) and graduate (4) students
            ->select('library_visits.*', 'users.user_type_id');

        // Apply date filters
        if ($dateFrom && $dateTo) {
            // Use custom date range if provided (overrides Quarter/Year)
            $query->whereBetween('library_visits.entry_time', [
                Carbon::parse($dateFrom)->startOfDay(),
                Carbon::parse($dateTo)->endOfDay()
            ]);
        } else {
            // Use quarter and year
            $startDate = Carbon::parse("$year-" . $quarterRanges[$quarter][0])->startOfDay();
            $endDate = Carbon::parse("$year-" . $quarterRanges[$quarter][1])->endOfDay();
            $query->whereBetween('library_visits.entry_time', [$startDate, $endDate]);
        }

        // Get visits with user relationships
        $visits = $query->with([
            'user.undergraduateStudent.course',
            'user.graduateStudent.course'
        ])->get();

        // Debug information
        $totalLibraryVisits = LibraryVisit::count();
        $totalStudentVisits = LibraryVisit::join('users', 'library_visits.user_id', '=', 'users.id')
            ->whereIn('users.user_type_id', [3, 4])
            ->count();

        // Group visits by course and count
        $courseCounts = [];

        foreach ($visits as $visit) {
            $course = null;

            // Determine course based on user type
            if ($visit->user->user_type_id == 3 && $visit->user->undergraduateStudent) {
                $course = $visit->user->undergraduateStudent->course;
            } elseif ($visit->user->user_type_id == 4 && $visit->user->graduateStudent) {
                $course = $visit->user->graduateStudent->course;
            }

            if ($course) {
                $courseKey = $course->code ?? $course->name;
                if (!isset($courseCounts[$courseKey])) {
                    $courseCounts[$courseKey] = [
                        'label' => $course->name,
                        'code' => $course->code,
                        'value' => 0
                    ];
                }
                $courseCounts[$courseKey]['value']++;
            }
        }

        // Sort by visit count and limit results
        $sortedCourses = collect($courseCounts)
            ->sortByDesc('value')
            ->take($entries)
            ->values()
            ->map(function ($course) {
                return [
                    'label' => $course['code'] ? "{$course['code']} - {$course['label']}" : $course['label'],
                    'value' => $course['value']
                ];
            })
            ->toArray();

        // No fallback search - return empty array if no data matches filters
        // This change ensures we don't display all data when filters return no results

        return response()->json([
            'success' => true,
            'data' => $sortedCourses,
            'debug' => [
                'total_library_visits' => $totalLibraryVisits,
                'total_student_visits' => $totalStudentVisits,
                'filtered_visits_count' => $visits->count(),
                'course_counts_found' => count($courseCounts),
                'date_range' => $dateFrom && $dateTo ?
                    ['from' => $dateFrom, 'to' => $dateTo] :
                    ['quarter' => $quarter, 'year' => $year,
                     'start_date' => "$year-" . $quarterRanges[$quarter][0],
                     'end_date' => "$year-" . $quarterRanges[$quarter][1]],
                'query_params' => $request->all()
            ]
        ]);
    }
}
