<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingTransactionController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
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

Route::get('/borrowings/books/search', [BorrowingTransactionController::class, 'searchBook']);

Route::get('/borrowings/users/search', [BorrowingTransactionController::class, 'searchUser']);

Route::get('/users/', [UserController::class, 'fetchAll']);
Route::get('/students/', [StudentController::class, 'fetchAll']);
Route::get('/faculties/', [FacultyController::class, 'fetchAll']);
Route::get('/staff/', [StaffController::class, 'fetchAll']);
Route::get('/admins/', [AdminController::class, 'fetchAll']);

Route::get('/records/', [RecordController::class, 'fetchAll']);
Route::get('/books/', [BookController::class, 'fetchAll']);
