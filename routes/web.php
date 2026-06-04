<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\BillGenerator;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlatController;
use Illuminate\Support\Facades\Auth;

// Show the login form
Route::get('login', function () {
    return view('auth.login');
})->name('login');

// Handle form submission via the controller
Route::post('login', [AuthenticatedSessionController::class, 'store']);




Route::get('bill-history/{date}', [BillGenerator::class, 'show']);
Route::get('bill-history', [BillGenerator::class, 'history'])->name('bill-history');


// View historical statements for an individual flat
Route::get('/flats/{id}', [FlatController::class, 'show'])->name('flats.show');

// View all flats
Route::get('/flats', [FlatController::class, 'index'])->name('flats.index');

// POS Print view for an individual flat's monthly bill
Route::get('/flats/{flat_id}/bill/{bill_month}', [FlatController::class, 'printBill'])->name('flats.bill.print');


// 2. Protected routes: Only logged-in users can view the edit form or submit changes
Route::middleware(['auth'])->group(function () {

    Route::resource('meter-readings', MeterReadingController::class);
    Route::get('meter-readings/by-month/{date}', 'MeterReadingController@showByMonth');

    Route::get('generate-bill', [BillGenerator::class, 'index']);
    Route::post('generate-bill', [BillGenerator::class, 'store']);


    Route::get('/flats/{id}/edit', [FlatController::class, 'edit'])->name('flats.edit');
    Route::put('/flats/{id}', [FlatController::class, 'update'])->name('flats.update');

});


Route::get('/', [DashboardController::class, 'index']);

Route::get('logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    
    return redirect('/login');
})->name('logout');