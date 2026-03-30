<?php

use App\Http\Controllers\Cashier\CashierReceiptController;
use App\Http\Controllers\Cashier\PaymentRecordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'cashier_role'])
    ->prefix('cashier')
    ->name('cashier.')
    ->group(function () {
        Route::get('payment-records', [PaymentRecordController::class, 'index'])->name('payment-records.index');
        Route::get('receipts/verify', [CashierReceiptController::class, 'index'])->name('receipts.verify');
        Route::post('receipts/verify', [CashierReceiptController::class, 'verify'])->name('receipts.verify.submit');
    });
