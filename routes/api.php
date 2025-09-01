<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingTransactionController;
use App\Http\Controllers\DigitalResourceController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\LibraryVisitController;
use App\Http\Controllers\PeriodicalController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\ThesisController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
});

Route::get('/sample', function () {
    return response()->json([
        'message' => 'This data came from Laravel!',
    ]);
});

Route::get('/logger/patron/search', [LibraryVisitController::class, 'search']);
Route::get('/logger/patron/search-by-name', [LibraryVisitController::class, 'searchByName']);
Route::get('/welcome/records/search', [WelcomeController::class, 'searchRecords']);

Route::get('/borrowings/books/search', [BorrowingTransactionController::class, 'searchBook']);
Route::get('/borrowings/users/search', [BorrowingTransactionController::class, 'searchUser']);

Route::get('/users/', [UserController::class, 'fetchAll']);
Route::get('/students/', [StudentController::class, 'fetchAll']);
Route::get('/faculties/', [FacultyController::class, 'fetchAll']);
Route::get('/staff/', [StaffController::class, 'fetchAll']);
Route::get('/admins/', [AdminController::class, 'fetchAll']);

Route::get('/welcome_records/', [RecordController::class, 'fetchAllWelcome']);
Route::get('/records/', [RecordController::class, 'fetchAll']);
Route::get('/books/', [BookController::class, 'fetchAll']);
Route::get('/multimedia/', [DigitalResourceController::class, 'fetchAll']);
Route::get('/periodicals/', [PeriodicalController::class, 'fetchAll']);
Route::get('/theses/', [ThesisController::class, 'fetchAll']);
