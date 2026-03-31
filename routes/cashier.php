<?php

use App\Http\Controllers\Cashier\CashierReceiptController;
use App\Http\Controllers\Cashier\PaymentRecordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'cashier_role'])
    ->prefix('cashier')
    ->name('cashier.')
    ->group(function () {
        Route::get('payment-records', [PaymentRecordController::class, 'index'])->name('payment-records.index');
        Route::get('payment-records/successful', [PaymentRecordController::class, 'successful'])->name('payment-records.successful');
        Route::post('payment-records/{paymentRequest}/verify', [PaymentRecordController::class, 'verify'])->name('payment-records.verify');
        Route::post('payment-records/{paymentRequest}/receipt', [PaymentRecordController::class, 'receipt'])->name('payment-records.receipt');
        Route::get('receipts/verify', [CashierReceiptController::class, 'index'])->name('receipts.verify');
        Route::post('receipts/verify', [CashierReceiptController::class, 'verify'])->name('receipts.verify.submit');
    });
