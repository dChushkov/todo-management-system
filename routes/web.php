<?php

use App\Http\Controllers\Web\PageController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Auth routes
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/todos');
    }
    return view('home');
})->name('home');

// Auth routes
Route::get('/login', function () {
    if (Auth::check()) {
        return redirect('/todos');
    }
    return app(\App\Http\Controllers\Web\PageController::class)->login();
})->name('login');

Route::get('/register', function () {
    if (Auth::check()) {
        return redirect('/todos');
    }
    return app(\App\Http\Controllers\Web\PageController::class)->register();
})->name('register');

Route::get('/csrf-token', function () {
    return response()->json(['csrf_token' => csrf_token()]);
});

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/todos', [PageController::class, 'todos'])->name('todos');
});
