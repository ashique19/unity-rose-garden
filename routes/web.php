<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\BillGenerator;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;

// Show the login form
Route::get('login', function () {
    return view('auth.login');
})->name('login');

// Handle form submission via the controller
Route::post('login', [AuthenticatedSessionController::class, 'store']);

Route::resource('meter-readings', MeterReadingController::class);
Route::get('meter-readings/by-month/{date}', 'MeterReadingController@showByMonth');

Route::get('generate-bill', [BillGenerator::class, 'index']);
Route::post('generate-bill', [BillGenerator::class, 'store']);


Route::get('bill-history/{date}', [BillGenerator::class, 'show']);
Route::get('bill-history', [BillGenerator::class, 'history'])->name('bill-history');


Route::get('/', [DashboardController::class, 'index']);

Route::get('logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    
    return redirect('/login');
})->name('logout');