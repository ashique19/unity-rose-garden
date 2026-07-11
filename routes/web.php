<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CommonWaterController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FlatBillTypeSettingController;
use App\Http\Controllers\Admin\FlatController as AdminFlatController;
use App\Http\Controllers\Admin\GasMeterReadingController;
use App\Http\Controllers\Admin\LedgerController;
use App\Http\Controllers\Admin\MonthGenerateController;
use App\Http\Controllers\Admin\OtherChargeController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BillGenerator;
use App\Http\Controllers\ChargeTemplateController;
use App\Http\Controllers\FlatController;
use App\Http\Controllers\Public\FlatController as PublicFlatController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\StatementController;
use App\Http\Controllers\Public\StatementPrintController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('login', function () {
    return view('auth.login');
})->name('login');

Route::post('login', [AuthenticatedSessionController::class, 'store']);

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/flats', function () {
    return redirect()->route('home');
})->name('flats.index');

Route::get('/flats/{flat}', [PublicFlatController::class, 'show'])->name('public.flats.show');
Route::get('/flats/{flat}/statements/gas', [StatementController::class, 'gas'])->name('public.statements.gas');
Route::get('/flats/{flat}/statements/others', [StatementController::class, 'others'])->name('public.statements.others');
Route::get('/flats/{flat}/statements/print', [StatementPrintController::class, 'show'])->name('public.statements.print');

Route::middleware(['auth'])->group(function () {
    // Legacy tools — admin only until cutover
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('meter-readings-and-charges', \App\Http\Controllers\MeterReadingAndChargesController::class)
            ->parameters(['meter-readings-and-charges' => 'meterReading']);
        Route::get('meter-readings-and-charges/by-month/{date}', [\App\Http\Controllers\MeterReadingAndChargesController::class, 'showByMonth']);

        Route::get('generate-bill', [BillGenerator::class, 'index'])->name('generate-bill');
        Route::post('generate-bill', [BillGenerator::class, 'store'])->name('generate-bill.store');

        Route::post('/bill-details/{id}/toggle-payment', [BillGenerator::class, 'togglePayment'])->name('bill-details.toggle-payment');

        Route::get('bill-history/{date}', [BillGenerator::class, 'show'])->name('bill-history.show');
        Route::get('bill-history', [BillGenerator::class, 'history'])->name('bill-history');
        Route::get('/flats/{flat_id}/bill/{bill_month}', [FlatController::class, 'printBill'])->name('flats.bill.print');
    });

    Route::middleware(['role:admin,secretary'])->group(function () {
        Route::resource('charge-templates', ChargeTemplateController::class)->except(['create', 'edit', 'show']);
    });

    Route::middleware(['role:admin,secretary'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('flat-bill-type-settings', [FlatBillTypeSettingController::class, 'index'])
            ->name('flat-bill-type-settings.index');
        Route::put('flat-bill-type-settings', [FlatBillTypeSettingController::class, 'update'])
            ->name('flat-bill-type-settings.update');

        Route::get('gas-readings', [GasMeterReadingController::class, 'index'])->name('gas-readings.index');
        Route::post('gas-readings', [GasMeterReadingController::class, 'store'])->name('gas-readings.store');
        Route::get('gas-readings/{flat}/assist', [GasMeterReadingController::class, 'assist'])->name('gas-readings.assist');
        Route::post('gas-readings/{flat}/photo', [GasMeterReadingController::class, 'uploadPhoto'])->name('gas-readings.photo');
        Route::post('gas-readings/{flat}/ocr', [GasMeterReadingController::class, 'requestOcr'])->name('gas-readings.ocr');
        Route::post('gas-readings/{flat}/suggest', [GasMeterReadingController::class, 'suggest'])->name('gas-readings.suggest');
        Route::put('gas-readings/{gasMeterReading}', [GasMeterReadingController::class, 'update'])->name('gas-readings.update');
        Route::delete('gas-readings/{gasMeterReading}', [GasMeterReadingController::class, 'destroy'])->name('gas-readings.destroy');

        Route::get('other-charges', [OtherChargeController::class, 'index'])->name('other-charges.index');
        Route::post('other-charges', [OtherChargeController::class, 'store'])->name('other-charges.store');
        Route::delete('other-charges/{customCharge}', [OtherChargeController::class, 'destroy'])->name('other-charges.destroy');

        Route::get('water', [CommonWaterController::class, 'index'])->name('water.index');
        Route::post('water', [CommonWaterController::class, 'store'])->name('water.store');
        Route::delete('water', [CommonWaterController::class, 'destroy'])->name('water.destroy');

        Route::get('generate', [MonthGenerateController::class, 'index'])->name('generate.index');
        Route::post('generate', [MonthGenerateController::class, 'store'])->name('generate.store');
    });

    Route::middleware(['role:admin,treasurer'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
        Route::post('collections', [CollectionController::class, 'store'])->name('collections.store');
        Route::delete('collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');

        Route::get('ledger', [LedgerController::class, 'index'])->name('ledger.index');
        Route::post('ledger', [LedgerController::class, 'store'])->name('ledger.store');
        Route::delete('ledger/{accountLedgerEntry}', [LedgerController::class, 'destroy'])->name('ledger.destroy');
    });

    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('flats', [AdminFlatController::class, 'index'])->name('flats.index');
        Route::put('flats/{flat}', [AdminFlatController::class, 'update'])->name('flats.update');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        Route::get('audit-log', [AuditLogController::class, 'index'])->name('audit.index');
    });
});

Route::get('logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/login');
})->name('logout');
