<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BorrowingTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalResourceController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\GradSchoolStudentController;
use App\Http\Controllers\LibraryVisitController;
use App\Http\Controllers\PeriodicalController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\ThesisController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [WelcomeController::class, 'index'])->name('home');
Route::get('/logger/create', [LibraryVisitController::class, 'create'])->name('logger.create');
Route::post('/', [LibraryVisitController::class, 'store'])->name('logger.store');

$middleware = ['auth'];
if (app()->environment(['production', 'staging'])) {
    $middleware[] = 'verified';
}
Route::middleware($middleware)->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::get('/all', [UserController::class, 'all'])->name('users.all');
        Route::group(['middleware' => ['can:viewAny, App\Models\User']], function () {
//            Route::get('/import', [UserController::class, 'import'])->name('users.import');
//            Route::post('/import', [UserController::class, 'importStore'])->name('users.import.store');
            Route::get('/admins',[AdminController::class, 'index'])->name('admins.index');
            Route::get('/admins/create', [AdminController::class, 'create'])->name('admins.create');
            Route::post('/admins', [AdminController::class, 'store'])->name('admins.store');
            Route::delete('/admins/{id}', [AdminController::class, 'destroy'])->name('admins.destroy');
        });
        Route::get('/faculties', [FacultyController::class, 'index'])->name('faculties.index');
        Route::get('/faculties/create', [FacultyController::class, 'create'])->name('faculties.create');
        Route::post('/faculties', [FacultyController::class, 'store'])->name('faculties.store');
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    });
    Route::prefix('records')->group(function () {
        Route::group(['middleware' => ['can:viewAny, App\Models\User']], function () {
//            Route::get('/import', [BookController::class, 'import'])->name('books.import');
//            Route::post('/import', [BookController::class, 'importStore'])->name('books.import.store');
        });
        Route::get('/', [RecordController::class, 'index'])->name('records.index');
        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::get('/multimedia', [DigitalResourceController::class, 'index'])->name('multimedia.index');
        Route::get('/multimedia/create', [DigitalResourceController::class, 'create'])->name('multimedia.create');
        Route::post('/multimedia', [DigitalResourceController::class, 'store'])->name('multimedia.store');
        Route::get('/periodicals', [PeriodicalController::class, 'index'])->name('periodicals.index');
        Route::get('/periodicals/create', [PeriodicalController::class, 'create'])->name('periodicals.create');
        Route::get('/theses', [ThesisController::class, 'index'])->name('theses.index');
    });
    Route::prefix('borrowings')->group(function () {
        Route::get('/', [BorrowingTransactionController::class, 'index'])->name('borrowings.index');
        Route::get('/active', [BorrowingTransactionController::class, 'indexActive'])->name('borrowings.active');
        Route::get('/overdue', [BorrowingTransactionController::class, 'indexOverdue'])->name('borrowings.overdue');
        Route::get('/returned', [BorrowingTransactionController::class, 'indexReturned'])->name('borrowings.returned');
        Route::get('/create', [BorrowingTransactionController::class, 'create'])->name('borrowings.create');
        Route::post('/borrow/inside', [BorrowingTransactionController::class, 'borrowInside'])->name('borrowings.borrow.inside');
        Route::post('/borrow', [BorrowingTransactionController::class, 'borrow'])->name('borrowings.borrow');
        Route::post('/return', [BorrowingTransactionController::class, 'returnBook'])->name('borrowings.return');
    });
    Route::prefix('logger')->group(function () {
        Route::get('/', [LibraryVisitController::class, 'index'])->name('logger.index');
    });

    Route::get('/test', [TestController::class, 'index'])->name('test.index');

});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
