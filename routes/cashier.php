<?php

use App\Http\Controllers\Cashier\CashierReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'cashier_role'])
    ->prefix('cashier')
    ->name('cashier.')
    ->group(function () {
        Route::get('receipts/verify', [CashierReceiptController::class, 'index'])->name('receipts.verify');
        Route::post('receipts/verify', [CashierReceiptController::class, 'verify'])->name('receipts.verify.submit');
    });
