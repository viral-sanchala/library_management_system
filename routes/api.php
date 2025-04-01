<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/user/register', [AuthController::class, 'register']);
Route::post('/admin/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware('checkAuth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);

    Route::get('/roles', [RoleController::class, 'index'])->middleware('checkPermission:view-role');
    Route::get('/roles/{id}', [RoleController::class, 'show'])->middleware('checkPermission:view-role');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('checkPermission:create-role');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('checkPermission:edit-role');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->middleware('checkPermission:delete-role');

    Route::get('/books', [BookController::class, 'index'])->middleware('checkPermission:view-book');
    Route::get('/books/{id}', [BookController::class, 'show'])->middleware('checkPermission:view-book');
    Route::post('/books', [BookController::class, 'store'])->middleware('checkPermission:create-book');
    Route::put('/books/{id}', [BookController::class, 'update'])->middleware('checkPermission:edit-book');
    Route::delete('/books/{id}', [BookController::class, 'destroy'])->middleware('checkPermission:delete-book');

    Route::post('/borrow-book', [BookController::class, 'borrowBook'])->middleware('checkPermission:borrow-book');
    Route::post('/return-book', [BookController::class, 'returnBook'])->middleware('checkPermission:return-book');
    Route::get('/get-borrowed-list', [BookController::class, 'getBorrowedBookList'])->middleware('checkPermission:borrow-book');
    Route::get('/get-bookwise-borrow-list/{bookId}', [BookController::class, 'getBookwiseBorrowList'])->middleware('checkPermission:create-book');
});
