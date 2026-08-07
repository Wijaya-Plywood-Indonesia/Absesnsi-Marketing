<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth')->group(function () {
    Route::post('/api/customer', [CustomerController::class, 'saveCustomer'])->name('api.customer.save');
    Route::post('/api/checkin', [CheckinController::class, 'saveCheckin'])->name('api.checkin.save');
    Route::post('/api/order', [OrderController::class, 'saveOrder'])->name('api.order.save');
    Route::get('/api/visits/today/{customer}', [CheckinController::class, 'todayVisit']);
    Route::post('/api/checkin/{visit}/update', [CheckinController::class, 'updateCheckin']);

    // Tambahan untuk dropdown kota & kecamatan
    Route::get('/api/wilayah/kota', [WilayahController::class, 'getKota'])->name('api.wilayah.kota');
    Route::get('/api/wilayah/kecamatan', [WilayahController::class, 'getKecamatan'])->name('api.wilayah.kecamatan');

    Route::post('/customer/{customer}/foto', [CustomerController::class, 'updateCustomerPhoto'])
        ->name('customer.updateFoto');
});
