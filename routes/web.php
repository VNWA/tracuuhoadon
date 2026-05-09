<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicBillLookupController;
use App\Http\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'role:admin|staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return to_route('admin.dashboard');
    })->name('index');

    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::controller(BillController::class)->prefix('bills')->name('bills.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{bill}/pdf', 'pdf')->name('pdf');
        Route::get('/{bill}/edit', 'edit')->name('edit');
        Route::put('/{bill}', 'update')->name('update');
        Route::delete('/{bill}', 'destroy')->name('destroy');
    });

    Route::middleware('role:admin')->controller(StaffController::class)->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::get('/{staff}/edit', 'edit')->name('edit');
        Route::post('/', 'store')->name('store');
        Route::put('/{staff}', 'update')->name('update');
        Route::delete('/{staff}', 'destroy')->name('destroy');
    });
});

Route::middleware(['auth', 'verified', 'role:admin|staff'])->get('dashboard', function () {
    return to_route('admin.dashboard');
})->name('dashboard');

require __DIR__.'/settings.php';

Route::get('/utilities/invoice-search', HomeController::class)->name('home');

Route::post('lookup', [PublicBillLookupController::class, 'search'])->name('public-bill.search');
Route::get('invoice-pdf', [PublicBillLookupController::class, 'pdf'])->name('public-bill.pdf');
Route::fallback(function () {
    return redirect()->route('home');
})->name('fallback');
