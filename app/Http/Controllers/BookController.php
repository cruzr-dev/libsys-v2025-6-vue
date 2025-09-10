<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\Author;
use App\Models\LcClassification;
use App\Models\Status;
use App\Models\Book;
use App\Models\CoverType;
use App\Models\DdcClassification;
use App\Models\PhysicalLocation;
use App\Models\Record;
use App\Models\Source;
use App\Services\QrCodeService;
use App\Services\BookCoverImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('books/Index');
    }

    public function fetchAll(Request $request)
    {
        $query = Record::query()
            ->whereNull('deleted_at') // respect soft deletes
            ->whereHas('book')
            ->with(['authors', 'editors', 'book.ddcClassification', 'book.physicalLocation']);

        // Handle search
        if ($request->filled('search')) {
            $searchTerm = $request->get('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('accession_number', 'like', "%{$searchTerm}%")
                    ->orWhere('title', 'like', "%{$searchTerm}%")
                    ->orWhereHas('authors', function ($authorQuery) use ($searchTerm) {
                        $authorQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('editors', function ($editorQuery) use ($searchTerm) {
                        $editorQuery->where('name', 'like', "%{$searchTerm}%");
                    });
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

        $showDDC = !$request->has('hide_ddc_classification') || $request->get('show_ddc_classification') === '1';
        $showLocation = !$request->has('hide_physical_location') || $request->get('show_physical_location') === '1';

        // Pagination
        $perPage = $request->get('per_page', 10);

        $records = $query->paginate($perPage);

        // Transform the data to include author, editor, and other information
        $records->getCollection()->transform(function ($record) use ($showDDC, $showLocation) {

            if ($showDDC) {
                $record->ddc_classification = $record->book && $record->book->ddcClassification
                    ? $record->book->ddcClassification->title
                    : null;
            }

            if ($showLocation) {
                $record->physical_location = $record->book && $record->book->physicalLocation
                    ? $record->book->physicalLocation->name
                    : null;
            }

            return $record;
        });

        return $records;
    }

    public function create(): \Inertia\Response
    {
        $maxAcc = Record::max('accession_number') ?? 0;

        $ddcClassifications = DdcClassification::select('id', 'title', 'number_range')
            ->orderBy('title')
            ->get();

        $lcClassifications = LcClassification::select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        $physicalLocations = PhysicalLocation::select('id', 'symbol', 'name')
            ->orderBy('name')
            ->get();

        $coverTypes = CoverType::select('id', 'name')->orderBy('name')->get();

        $sources = Source::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('books/Create', [
            'nextAccessionNumber' => $maxAcc + 1,
            'ddcClassifications' => $ddcClassifications,
            'lcClassifications'  => $lcClassifications,
            'physicalLocations'  => $physicalLocations,
            'coverTypes'         => $coverTypes,
            'sources'         => $sources,
        ]);
    }

    public function fetchCoverTypes(): \Illuminate\Http\JsonResponse
    {
        $coverTypes = CoverType::select('id', 'key', 'name')->get();

        return response()->json($coverTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        Request $request,
        BookCoverImageService $imageService,
        QrCodeService $qrCodeService
    ): \Illuminate\Http\RedirectResponse {

        $validated = $request->validate([
            // Basic Information
            'accession_number'      => 'required|string|max:50|unique:records,accession_number',
            'title'                 => 'required|string|max:255',
            'volume'                => 'nullable|string|max:50',
            'edition'               => 'nullable|string|max:50',
            'primary_author'        => 'required|string|max:255',
            'co_authors'            => 'nullable|array',
            'co_authors.*'          => 'string|max:255',
            'editors'               => 'nullable|array',
            'editors.*'             => 'string|max:255',
            'publication_year'      => 'required|integer|min:1000|max:' . date('Y'),
            'publisher'             => 'required|string|max:255',
            'publication_place'     => 'required|string|max:255',
            'isbn'                  => 'required|string|max:20|unique:books,isbn',

            // Classification & Location
            'call_number' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^(?:[a-z]{2,10}\s+)?\d{1,3}(\.\d+)?\s*[A-Z]\d{1,4}(\s*\d{4})?$/i'
            ],
            'ddc_class_id'          => 'nullable|exists:ddc_classifications,id',
            'physical_location_id'  => 'required|exists:physical_locations,id',

            // Physical Description
            'cover_image'           => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'cover_type_id'         => 'required|exists:cover_types,id',
            'status'                => 'required|in:available,damaged,missing,borrowed,discarded',

            // Administrative Information
            'ics_number'            => 'nullable|max:50',
            'ics_date'              => 'required_with:ics_number|date',
            'pr_number'             => 'nullable|max:50',
            'pr_date'               => 'required_with:pr_number|date',
            'po_number'             => 'nullable|max:50',
            'po_date'               => 'required_with:po_number|date',
            'source_id'             => 'required|exists:sources,id',
            'purchase_amount'       => 'nullable|numeric|min:0',
            'lot_cost'              => 'nullable|numeric|min:0',
            'supplier'              => 'nullable|string|max:255',
            'donated_by'            => 'nullable|string|max:255',
            'replaced_by'           => 'nullable|string|max:255',

            // Content Description
            'table_of_contents'     => 'nullable|string',
            'subject_headings'      => 'nullable|array',
            'subject_headings.*'    => 'string|max:255',
        ]);

        // 2. Transaction
        try {
            DB::transaction(function () use ($validated, $request, $imageService, $qrCodeService) {
                $coverImagePath = null;

                if ($request->hasFile('cover_image')) {
                    $coverImagePath = $imageService->store(
                        $request->file('cover_image'),
                        $validated['accession_number']
                    );
                }

                $subject = null;
                if (!empty($validated['subject_headings'])) {
                    $subject = collect($validated['subject_headings'])
                        ->map(fn($heading, $i) => ($i + 1) . '. ' . trim($heading) . '.')
                        ->implode(' ');
                }

                $record = Record::create([
                    'accession_number' => $validated['accession_number'],
                    'title'            => $validated['title'],
                    'subject'          => $subject,
                    'status'           => $validated['status'],
                    'date_received'    => now(),
                    'added_by'         => auth()->id(),
                ]);

                // Create book record
                $book = $record->book()->create([
                    'volume'               => $validated['volume'],
                    'edition'              => $validated['edition'],
                    'publication_year'     => $validated['publication_year'],
                    'publisher'            => $validated['publisher'],
                    'publication_place'    => $validated['publication_place'],
                    'isbn'                 => $validated['isbn'],
                    'call_number'          => $validated['call_number'],
                    'ddc_class_id'         => $validated['ddc_class_id'],
                    'physical_location_id' => $validated['physical_location_id'],
                    'cover_type_id'        => $validated['cover_type_id'],
                    'cover_image'          => $coverImagePath,
                    'ics_number'           => $validated['ics_number'],
                    'ics_date'             => $validated['ics_date'],
                    'pr_number'            => $validated['pr_number'],
                    'pr_date'              => $validated['pr_date'],
                    'po_number'            => $validated['po_number'],
                    'po_date'              => $validated['po_date'],
                    'source_id'            => $validated['source_id'],
                    'purchase_amount'      => $validated['purchase_amount'],
                    'lot_cost'             => $validated['lot_cost'],
                    'supplier'             => $validated['supplier'],
                    'donated_by'           => $validated['donated_by'],
                    'replaced_by'          => $validated['replaced_by'],
                    'table_of_contents'    => $validated['table_of_contents'],
                ]);

                // Generate QR Code for accession number
                $qrCodePath = $qrCodeService->store($validated['accession_number']);
                $book->update(['qrcode_path' => $qrCodePath]);

                // Handle Primary Author
                $primaryAuthor = $this->findOrCreateAuthor($validated['primary_author']);
                $record->authors()->attach($primaryAuthor->id, ['role' => 'primary author']);

                // Handle Co-Authors
                if (!empty($validated['co_authors'])) {
                    foreach ($validated['co_authors'] as $coAuthorName) {
                        $coAuthor = $this->findOrCreateAuthor($coAuthorName);
                        $record->authors()->attach($coAuthor->id, ['role' => 'co-author']);
                    }
                }

                // Handle Editors
                if (!empty($validated['editors'])) {
                    foreach ($validated['editors'] as $editorName) {
                        $editor = $this->findOrCreateAuthor($editorName);
                        $record->editors()->attach($editor->id);
                    }
                }
            });

            return to_route('books.index')
                ->with('success', 'Book record created successfully with QR code.');
        } catch (\Throwable $e) {
            Log::error('Error creating Book: ' . $e->getMessage(), [
                'accession_number' => $validated['accession_number'],
                'isbn'             => $validated['isbn'],
            ]);

            return back()->withInput()->with('error', 'Failed to create book record. Please try again.');
        }
    }

    /**
     * Find or create an author by name
     */
    private function findOrCreateAuthor(string $name): Author
    {
        return Author::firstOrCreate(
            ['name' => trim($name)],
            ['name' => trim($name)]
        );
    }
    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $record = Record::where('id', $id)
            ->whereHas('book')
            ->with(['book','authors'])
            ->firstOrFail();

        $ddcClassifications = DdcClassification::select('id', 'title', 'number_range')
            ->orderBy('title')
            ->get();

        $physicalLocations = PhysicalLocation::select('id', 'symbol', 'name')
            ->orderBy('name')
            ->get();

        $coverTypes = CoverType::select('id', 'name')->orderBy('name')->get();

        $sources = Source::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('books/Edit', [
            'record' => $record,
            'ddcClassifications' => $ddcClassifications,
            'physicalLocations'  => $physicalLocations,
            'coverTypes'         => $coverTypes,
            'sources'         => $sources,
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        //
    }

    public function import()
    {
        return Inertia::render('records/BookImport');
    }

    public function importStore(Request $request)
    {
        $success_message = "";

        try {

            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max, 10240
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', 'Please fix the validation errors below.');
            throw $e; // Re-throw to let Laravel handle the redirect with errors
        }

        try {
            $csv_path = $request->csv_file->store('temp/csv_imports');

            if ($csv_path && Storage::exists($csv_path)) {
                $csv_content = Storage::get($csv_path);
                $csv_data = array_map('str_getcsv', explode("\n", $csv_content));

                // Remove header row if it exists
                $headers = array_shift($csv_data);

                $failed_count = 0;
                $errors = [];
                $imported_count = 0;

                foreach ($csv_data as $row_index => $row) {
                    try {
                        // Trim all values in the row and check for emptiness
                        $row = array_map('trim', $row); // Trim all values
                        if (empty(array_filter($row, fn($value) => $value !== '' && $value !== null))) {
                            \Log::warning('Skipping empty row ' . ($row_index + 1) . ':', $row);
                            continue;
                        }

                        $date_received = null;
                        if (!empty($row[2]) && is_string($row[2])) {
                            try {
                                $date_received = Carbon::createFromFormat('m/d/Y', $row[2])->format('Y-m-d');
                            } catch (\Exception $e) {
                                $failed_count++;
                                $errors[] = "Row " . ($row_index + 1) . ": Invalid date format in date_received: " . $row[2];
                                \Log::info('Processing row', ['row_index' => $row_index + 1, 'data' => $row]);
                                continue;
                            }
                        }

                        $subject_headings = null;
                        if (!empty($row[13])) {
                            $subject_headings = $row[13];
                        }

                        $record_data = [
                            'accession_number' => $row[1] ?? null,
                            'date_received' => $date_received,
                            'title' => $row[5] ?? null,
                            'status' => Status::where('key', 'available')->first()->name,
                            'imported_by' => Auth::user()->id,
                            'subject_headings' => $subject_headings,
                        ];

                        $volume = null;
                        if (!empty($row[0])) {
                            $volume = $row[0];
                        }

                        $authors = [];
                        if (!empty($row[4])) {
                            $authors = $row[4];
                        }

                        $edition = null;
                        if (!empty($row[6])) {
                            $edition = $row[6];
                        }

                        $publication_year = $row[11] ?? null;
                        if ($publication_year === '' || $publication_year === '-') {
                            $publication_year = null;
                        }

                        $publisher = null;
                        if (!empty($row[10])) {
                            $publisher = $row[10];
                        }

                        $publication_place = null;
                        if (!empty($row[9])) {
                            $publication_place = $row[9];
                        }

                        $isbn = null;
                        if (!empty($row[12])) {
                            $isbn = $row[12];
                            if ($isbn === '-') {
                                $isbn = null;
                            }
                        }

                        $call_number = null;
                        if (!empty($row[3])) {
                            $call_number = $row[3];
                        }

                        $ddc_class_id = null;
                        if (!empty($row[7])) {
                            $ddc_class_name = ucwords(strtolower($row[7]));
                            $ddc_class = DdcClassification::where('name', $ddc_class_name)->first();
                            if ($ddc_class) {
                                $ddc_class_id = $ddc_class->id;
                            } else {
                                // Generate base code
                                $base_code = strlen($ddc_class_name) >= 3 ? substr($ddc_class_name, 0, 3) : $ddc_class_name;

                                // Check for duplicates and append number if needed
                                $code = $base_code;
                                $counter = 1;
                                while (DdcClassification::where('code', $code)->exists()) {
                                    $code = $base_code . $counter;
                                    $counter++;
                                }

                                $ddc_class_id = DdcClassification::create([
                                    'name' => $ddc_class_name,
                                    'code' => $code
                                ])->id;
                            }
                        }

                        $physical_location_id = null;
                        if (!empty($row[8])) {
                            $physical_location_name = ucwords(strtolower($row[8]));
                            $physical_location = PhysicalLocation::where('name', $physical_location_name)->first();
                            if ($physical_location) {
                                $physical_location_id = $physical_location->id;
                            } else {
                                // Generate base symbol
                                $base_symbol = strlen($physical_location_name) >= 3 ? substr($physical_location_name, 0, 3) : $physical_location_name;

                                // Check for duplicates and append number if needed
                                $symbol = $base_symbol;
                                $counter = 1;
                                while (PhysicalLocation::where('symbol', $symbol)->exists()) {
                                    $symbol = $base_symbol . $counter;
                                    $counter++;
                                }

                                $physical_location_id = PhysicalLocation::create([
                                    'name' => $physical_location_name,
                                    'symbol' => $symbol
                                ])->id;
                            }
                        }

                        $cover_type_id = null;
                        if (!empty($row[16])) {
                            $cover_type_name = $row[16];
                            $cover_type = CoverType::where('name', ucwords(strtolower($cover_type_name)))->first();
                            if ($cover_type) {
                                $cover_type_id = $cover_type->id;
                            } else {
                                $cover_type_id = CoverType::create([
                                    'key' => strtolower($cover_type_name),
                                    'name' => $cover_type_name
                                ])->id;
                            }
                        }

                        $cover_image = null;
                        if (!empty($row[19])) {
                            $cover_image = $row[19];
                            if ($cover_image === '-') {
                                $cover_image = null;
                            }
                        }

                        $source = null;
                        $donated_by = null;
                        $purchaseAmount = $row[17] ?? null;
                        if (!is_numeric($purchaseAmount) || $purchaseAmount < 0) {
                            $donated_by = $purchaseAmount;
                            $source = 'Donation';
                            $purchaseAmount = null;
                        }

                        $source_id = null;
                        if ($source){
                            $source_name = ucwords(strtolower($source));
                            $source_from_db = Source::where('name', $source_name)->first();
                            if ($source_from_db) {
                                $source_id = $source_from_db->id;
                            } else {
                                $source_id = Source::create([
                                    'key' => strtolower($source_name),
                                    'name' => $source_name
                                ])->id;
                            }
                        } else {

                            if (!empty($row[15])) {
                                $source_name = ucwords(strtolower($row[15]));
                                $source_from_db = Source::where('name', $source_name)->first();
                                if ($source_from_db) {
                                    $source_id = $source_from_db->id;
                                } else {
                                    $source_id = Source::create([
                                        'key' => strtolower($source_name),
                                        'name' => $source_name
                                    ])->id;
                                }
                            }
                        }

                        $supplier = null;
                        if (!empty($row[18])) {
                            $supplier = $row[18];
                        }

                        $table_of_contents = null;
                        if (isset($row[14]) && !empty($row[14])) {
                            $raw_content = $row[14];

                            // Step 1: Clean the content
                            // Replace double dashes with a single space or dash
                            $cleaned_content = str_replace('--', ' - ', $raw_content);

                            // Step 2: Remove excessive whitespace and normalize
                            $cleaned_content = preg_replace('/\s+/', ' ', $cleaned_content);

                            // Step 3: Optional - Split into array for structured storage (e.g., JSON)
                            $toc_array = explode(' - ', $cleaned_content);
                            $table_of_contents = json_encode($toc_array); // Store as JSON for flexibility
                            // OR keep as string: $table_of_contents = $cleaned_content;
                        }

                        $book_data = [
                            'volume' => $volume,
                            'authors' => $authors,
                            'edition' => $edition,
                            'publication_year' => $publication_year,
                            'publisher' => $publisher,
                            'publication_place' => $publication_place,
                            'isbn' => $isbn,

                            'call_number' => $call_number,
                            'ddc_class_id' => $ddc_class_id,
                            'physical_location_id' => $physical_location_id,

                            'cover_type_id' => $cover_type_id,
                            'cover_image' => $cover_image,

                            'source_id' => $source_id,
                            'purchase_amount' => $purchaseAmount,
                            'supplier' => $supplier,
                            'donated_by' => $donated_by,

                            'table_of_contents' => $table_of_contents,
                        ];

                        // reset after use
                        $purchaseAmount = null;
                        $donated_by = null;
                        $source = null;

                        // initialize
                        $remark_data = [];

                        if (!empty($row[20])) {
                            $content = $row[20];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2007-2008')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }

                        if (!empty($row[21])) {
                            $content = $row[21];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2008-2009')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[22])) {
                            $content = $row[22];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2009-2010')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[23])) {
                            $content = $row[23];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2010-2011')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[24])) {
                            $content = $row[24];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2011-2012')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[25])) {
                            $content = $row[25];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2017-2018')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[26])) {
                            $content = $row[26];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2018-2019')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[27])) {
                            $content = $row[27];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2019-2020')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[28])) {
                            $content = $row[28];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2020-2021')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[29])) {
                            $content = $row[29];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2021-2022')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[30])) {
                            $content = $row[30];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2022-2023')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[31])) {
                            $content = $row[31];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2023-2024')
                                    ->where('semester', '1st Semester')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[32])) {
                            $content = $row[32];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2023-2024')
                                    ->where('semester', '2nd Semester')->first()->id,
                                'content' => $content,
                            ];
                        }
                        if (!empty($row[33])) {
                            $content = $row[33];
                            $remark_data[] = [
                                'academic_period_id' => AcademicPeriod::where('academic_year', '2023-2024')
                                    ->where('semester', 'Whole Year')->first()->id,
                                'content' => $content,
                            ];
                        }

                        // Clean and validate data
                        $record_data['title'] = trim($record_data['title']);

                        // Check for duplicate ISBN if provided
                        if (!empty($book_data['accession_number'])) {
                            $existing_book = \App\Models\Record::where('accession_number', $book_data['accession_number'])->first();
                            if ($existing_book) {
                                $failed_count++;
                                $errors[] = "Row " . ($row_index + 1) . ": Book with Accession Number {$book_data['isbn']} already exists";
                                continue;
                            }
                        }

                        // Validate required fields
                        if (!empty($record_data['title']) || !empty($book_data['accession_number'])) {

                            // Create the book record
                            $record = Record::create($record_data);
                            $record->book()->create($book_data);

                            foreach ($remark_data as $remark) {
                                $record->remarks()->create($remark);
                            }

                            $imported_count++;

                            $success_message = "Import completed! { $imported_count } book(s) imported successfully.";
                            if ($failed_count > 0) {
                                $success_message .= " {$failed_count} row(s) failed.";
                            }

                            if (!empty($errors)) {
                                session()->flash('import_errors', $errors);
                            } else {
                                session()->flash('success', $success_message);
                            }
                        }
                        else {
                            session()->flash('error', 'No file uploaded.');
                        }

                    } catch (\Exception $e) {
                        $failed_count++;
                        $errors[] = "Row " . ($row_index + 1) . ": " . $e->getMessage();
                        Log::info('Processing row', ['row_index' => $row_index + 1, 'data' => $row]);
                        foreach ($errors as $error) {
                            Log::error($error);
                        }
                    }
                }
            }

            Storage::delete($csv_path);

        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database-specific errors
            $message = app()->environment('production')
                ? 'Failed to add record. Please try again.'
                : 'Failed to add record: ' . $e->getMessage();
            session()->flash('error', $message);
        } catch (\Exception $e) {
            // Handle any other unexpected errors
            $message = app()->environment('production')
                ? 'An unexpected error occurred. Please try again.'
                : 'An unexpected error occurred: ' . $e->getMessage();
            session()->flash('error', $message);
        }

        return to_route('records.index')->with('success', $success_message);

    }
}
