<?php

use App\Http\Controllers\MarketerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketerController::class, 'index'])->name('dashboard');
Route::get('/login', [MarketerController::class, 'showLogin'])->name('login');
Route::post('/login', [MarketerController::class, 'login']);
Route::post('/logout', [MarketerController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/api/customer', [MarketerController::class, 'saveCustomer'])->name('api.customer.save');
    Route::post('/api/checkin', [MarketerController::class, 'saveCheckin'])->name('api.checkin.save');
    Route::post('/api/order', [MarketerController::class, 'saveOrder'])->name('api.order.save');
    Route::get('/api/visits/today/{customer}', [MarketerController::class, 'todayVisit']);
    Route::post('/api/checkin/{visit}/update', [MarketerController::class, 'updateCheckin']);

    // Tambahan untuk dropdown kota & kecamatan
    Route::get('/api/wilayah/kota', [MarketerController::class, 'getKota'])->name('api.wilayah.kota');
    Route::get('/api/wilayah/kecamatan', [MarketerController::class, 'getKecamatan'])->name('api.wilayah.kecamatan');
});
