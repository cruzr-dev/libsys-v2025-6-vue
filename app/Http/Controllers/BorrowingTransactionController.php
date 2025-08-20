<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BorrowingPolicy;
use App\Models\BorrowingTransaction;
use App\Models\Record;
use App\Models\User;
use App\Models\UserType;
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
            'search_ac_result' => $search_result,
        ]);
    }

    public function indexActive(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortField = $request->input('sort_field', null);
        $sortDirection = $request->input('sort_direction', 'asc');
        $filters = [];

        $userType = UserType::where('key', 'student')->first();
        $userTypeId = $userType ? $userType->id : null;

        // Capture search parameters
        $searchTerm = $request->input('search');
        if (!empty($searchTerm)) {
            $filters[] = [
                'id' => 'search',
                'value' => $searchTerm
            ];
        }

        // Define all possible columns that can be toggled
        $toggleableColumns = ['sex', 'middle_initial', 'card_number', 'school_id']; // Add other columns as needed
        $columnVisibility = [];

        // Check for visibility parameters in the URL
        $hasVisibilityParams = collect($request->query())
            ->keys()
            ->contains(function ($key) {
                return str_starts_with($key, 'hide_') || str_starts_with($key, 'show_');
            });

        if ($hasVisibilityParams) {
            // Process explicit visibility settings from URL
            foreach ($toggleableColumns as $columnName) {
                if ($request->has("show_$columnName") && $request->input("show_$columnName") === '1') {
                    $columnVisibility[$columnName] = true;
                } elseif ($request->has("hide_$columnName") && $request->input("hide_$columnName") === '1') {
                    $columnVisibility[$columnName] = false;
                } else {
                    // Default visibility based on column
                    $columnVisibility[$columnName] = $columnName === 'card_number' ? true : false;
                }
            }
        } else {
            // First visit - apply default visibility
            $columnVisibility = [
                'sex' => false,
                'middle_initial' => false,
                'school_id' => false,
                'card_number' => true,
            ];
        }

        $users = User::query()
            ->with('userType')
            ->when($userTypeId, function ($query, $userTypeId) {
                $query->where('user_type_id', $userTypeId);
            })
            ->when($searchTerm, function ($query, $searchTerm) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('library_id', 'like', '%' . $searchTerm . '%')
                        ->orWhere('card_number', 'like', '%' . $searchTerm . '%');
                });
            })
            ->when($sortField, function ($query, $sortField) use ($sortDirection) {
                $query->orderBy($sortField, $sortDirection);
            })
            ->paginate(perPage: $perPage);

        return Inertia::render('borrowings/IndexActive', [
            'data' => $users,
            'filter' => $filters,
            'currentSortField' => $sortField,
            'currentSortDirection' => $sortDirection,
            'columnVisibility' => $columnVisibility,
        ]);
    }

    public function searchUser(Request $request)
    {
        $query = $request->get('q', '');

        $users = User::select('id', 'first_name', 'last_name', 'email')
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'LIKE', "%{$query}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhere('email', 'LIKE', "%{$query}%");
            })
            ->limit(8)
            ->get();

        return inertia()->render('borrowings/Create', [
            'users' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            $request->validate([
                'accession_number' => 'required|exists:records,accession_number',
                'borrow_type' => 'required|in:inside,take-home',
            ]);

            $transaction = null;

            if ($request->borrow_type === 'inside') {
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
                $transactionNumber = 'BRW-IN-' . date('YmdHis') . '-' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
                $record_id = Record::where('accession_number', $request->accession_number)->first()->id;

                if (!$record_id) {
                    session()->flash('error', 'Book not found');
                    return to_route('borrowings.index');
                }

                // Create the borrowing transaction
                $transaction = BorrowingTransaction::create([
                    'transaction_number' => $transactionNumber,
                    'record_id' => $record_id,
                    'borrowing_policy_id' => $insideBorrowingPolicy->id,
                    'transaction_type' => 'borrow-inside',
                    'status' => 'borrowed-inside',
                    'checkout_date' => now(),
                    'checked_out_by' => Auth::id(),
                ]);
            }

            return to_route('borrowings.index')
                ->with('success', 'Borrowing transaction ' . $transaction->transaction_number . ' added successfully');

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

        $transactionNumber = 'BRW-OUT-' . date('YmdHis') . '-' . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        $policy_loan_period_days = $policy->loan_period_days;

        $transaction = BorrowingTransaction::create([
            'transaction_number' => $transactionNumber,
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

        return to_route('borrowings.index')
            ->with('success', 'Borrowing transaction ' . $transaction->transaction_number . ' added successfully');

    }

    /**
     * Display the specified resource.
     */
    public function show(BorrowingTransaction $borrowing)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BorrowingTransaction $borrowing)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BorrowingTransaction $borrowing)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BorrowingTransaction $borrowing)
    {
        //
    }
}
