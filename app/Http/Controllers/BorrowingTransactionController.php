<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowingPolicy;
use App\Models\BorrowingTransaction;
use App\Models\Record;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PhpParser\Node\Expr\Cast\Object_;

class BorrowingTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return to_route('borrowings.active');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): \Inertia\Response
    {
        $search_result = null;
        $accession_number = $request->searchAcc ?? $request->scannedAcc;

        if ($accession_number) {
            $search_result = Record::where('accession_number', $accession_number)->first();

            if (!$search_result) {
                session()->flash('error', 'Sorry there is no record found');
            }
        }

        return Inertia::render('borrowings/Create', [
            'searchAcResult' => $search_result,
        ]);
    }

    public function searchBook(Request $request): JsonResponse
    {
        {
            $query = $request->get('q');

            if (empty($query) || strlen($query) < 2) {
                return response()->json([
                    'books' => [],
                    'message' => 'Query must be at least 2 characters long'
                ]);
            }

            try {
                $books = Record::where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                        ->orWhere('accession_number', 'LIKE', "%{$query}%");

                })
                    ->select([
                        'id',
                        'title',
                        'accession_number',
                        'status', // Add status column if you have it
                    ])
                    ->limit(10) // Limit results to prevent overwhelming the UI
                    ->get();

                return response()->json([
                    'books' => $books,
                    'count' => $books->count()
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'books' => [],
                    'error' => 'Search failed',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
    }

    public function indexActive(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortField = $request->input('sort_field', 'id'); // Keep 'id' as default
        $sortDirection = $request->input('sort_direction', 'desc'); // Change to 'desc' for latest first
        $filters = [];

        // Capture search parameters
        $searchTerm = $request->input('search');
        if (!empty($searchTerm)) {
            $filters[] = [
                'id' => 'search',
                'value' => $searchTerm
            ];
        }

        // Define toggleable columns (including user columns)
        $toggleableColumns = ['id', 'client'];
        $columnVisibility = [];

        // Check for visibility parameters in the URL
        $hasVisibilityParams = collect($request->query())
            ->keys()
            ->contains(function ($key) {
                return str_starts_with($key, 'hide_') || str_starts_with($key, 'show_');
            });

        if ($hasVisibilityParams) {
            foreach ($toggleableColumns as $columnName) {
                if ($request->has("show_$columnName") && $request->input("show_$columnName") === '1') {
                    $columnVisibility[$columnName] = true;
                } elseif ($request->has("hide_$columnName") && $request->input("hide_$columnName") === '1') {
                    $columnVisibility[$columnName] = false;
                } else {
                    // Default visibility
                    $columnVisibility[$columnName] = match($columnName) {
                        'id', 'client' => true,
                        default => false
                    };
                }
            }
        } else {
            // Default visibility
            $columnVisibility = [
                'id' => true,
                'client' => true,
            ];
        }

        $borrowings = BorrowingTransaction::query()
            ->select([
                'id',
                'user_id',
                'record_id',
                'checkout_date',
                'due_date',
            ])
            ->with([
                'user:id,library_id,first_name,middle_initial,last_name',
                'record:id,title,accession_number',
            ])
            ->whereIn('status', ['active'])
            ->when($searchTerm, function ($query, $searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('id', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('first_name', 'like', '%' . $searchTerm . '%')
                                ->orWhere('middle_initial', 'like', '%' . $searchTerm . '%')
                                ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
                        });
                });
            })
            ->when($sortField, function ($query, $sortField) use ($sortDirection) {
                // Handle sorting for user fields
                if ($sortField === 'user_name') {
                    $query->join('users', 'borrowing_transactions.user_id', '=', 'users.id')
                        ->orderBy('users.first_name', $sortDirection)
                        ->orderBy('users.last_name', $sortDirection)
                        ->select('borrowing_transactions.*');
                } else {
                    $query->orderBy($sortField, $sortDirection);
                }
            })
            ->paginate(perPage: $perPage);

        return Inertia::render('borrowings/IndexActive', [
            'data' => $borrowings,
            'filter' => $filters,
            'currentSortField' => $sortField,
            'currentSortDirection' => $sortDirection,
            'columnVisibility' => $columnVisibility,
        ]);
    }

    public function searchUser(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'users' => [],
                'message' => 'Query must be at least 2 characters long'
            ]);
        }

        try {
            $users = User::where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhere('middle_initial', 'LIKE', "%{$query}%")
                    ->orWhere('library_id', 'LIKE', "%{$query}%");
            })
                ->select([
                    'id',
                    'first_name',
                    'last_name',
                    'middle_initial',
                    'library_id'
                ])
                ->limit(10) // Limit results
                ->orderBy('first_name')
                ->get();

            return response()->json([
                'users' => $users,
                'count' => $users->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'users' => [],
                'error' => 'User search failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function borrowInside(Request $request)
    {
        try {

            $request->validate([
                'accession_number' => 'required|exists:records,accession_number',
            ]);

            $transaction = null;

            // Get the inside borrowing policy ID
            $insideBorrowingPolicy = BorrowingPolicy::where('name', 'Borrow Inside Policy')->first();

            if (!$insideBorrowingPolicy) {
                Log::warning('Inside borrowing policy not found', [
                    'record_id' => $request->record_id,
                    'user_id' => Auth::id()
                ]);
                session()->flash('error', 'Inside borrowing policy not found');
                return to_route('borrowings.index');
            }

            // Generate unique transaction number
            $transactionNumber = 'BRW-I' . date('ymdHis') . '-' . str_pad(random_int(1, 99999), 4, '0', STR_PAD_LEFT);
            $record_id = Record::where('accession_number', $request->accession_number)->first()->id;

            if (!$record_id) {
                session()->flash('error', 'Book not found');
                return to_route('borrowings.index');
            }

            // Create the borrowing transaction
            $transaction = BorrowingTransaction::create([
                'record_id' => $record_id,
                'borrowing_policy_id' => $insideBorrowingPolicy->id,
                'transaction_type' => 'borrow-inside',
                'status' => 'borrowed-inside',
                'checkout_date' => now(),
                'checked_out_by' => Auth::id(),
            ]);

            return to_route('borrowings.create')
                ->with('success', 'Borrowing transaction ID:' . $transaction->id . ' added successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed in BorrowingController@store', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);
            session()->flash('error', 'An error occurred while creating the borrowing transaction');

        } catch (\Exception $e) {
            Log::error('Error in BorrowingController@store', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
                'user_id' => Auth::id()
            ]);
            session()->flash('error', 'An error occurred while creating the borrowing transaction');
        }
    }

    public function borrow(Request $request)
    {
        $user = null;
        $policy = null;
        $book = null;
        try {
            $user = User::findOrFail($request->user_id);
            $book = Record::where('accession_number', $request->book_accession)->first();
            $policy = BorrowingPolicy::where('user_type_id', $user->user_type_id)->first();

            if ($book->status === 'borrowed') {
                session()->flash('error', 'Book already borrowed');
                return Inertia::render('borrowings/Create');
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong');
            // Log the error
            \Log::error('Error in user or book retrieval: ' . $e->getMessage(), [
                'user_id' => $request->user_id,
                'book_accession' => $request->book_accession,
                'exception' => $e
            ]);
        }

        $transactionNumber = 'BRW-O' . date('ymdHis') . '-' . str_pad(random_int(1, 99999), 4, '0', STR_PAD_LEFT);        $policy_loan_period_days = $policy->loan_period_days;

        $transaction = BorrowingTransaction::create([
            'user_id' => $user->id,
            'record_id' => $book->id,
            'borrowing_policy_id' => $policy->id,
            'transaction_type' => 'checkout',
            'status' => 'active',
            'checkout_date' => now(),
            'due_date' => now()->addDays($policy_loan_period_days),
            'checked_out_by' => Auth::id(),
        ]);

        $book->update([
            'status' => 'borrowed'
        ]);

        return to_route('borrowings.create')
            ->with('success', 'Borrowing transaction ID:' . $transaction->id . ' added successfully');
    }

    public function return(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'transactionId' => 'required|exists:borrowing_transactions,id'
            ]);

            // Find the borrowing transaction
            $transaction = BorrowingTransaction::findOrFail($request->transactionId);

            // Check if the book is already returned
            if ($transaction->status === 'returned') {
                session()->flash('error', 'Book already returned');
                return to_route('borrowings.create');
            }

            // Get the associated book
            $book = Record::findOrFail($transaction->record_id);

            // Update transaction
            $transaction->update([
                'status' => 'returned',
                'return_date' => now(),
                'checked_in_by' => Auth::id(),
            ]);

            // Update book status
            $book->update([
                'status' => 'available'
            ]);

            return to_route('borrowings.index')
                ->with('success', 'Book returned successfully for transaction ID: ' . $transaction->id);

        } catch (\Exception $e) {
            session()->flash('error', 'Something went wrong during return process');
            // Log the error
            \Log::error('Error in book return: ' . $e->getMessage(), [
                'transaction_id' => $request->transactionId,
                'exception' => $e
            ]);

            return Inertia::render('borrowings/IndexActive');
        }
    }

}
