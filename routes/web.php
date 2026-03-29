<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StudentPaymentController::class, 'create'])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/student-payments.php';
require __DIR__.'/student-receipts.php';
require __DIR__.'/admin.php';
require __DIR__.'/cashier.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
