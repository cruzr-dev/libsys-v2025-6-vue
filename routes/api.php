<?php

use App\Http\Controllers\BorrowingTransactionController;
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
