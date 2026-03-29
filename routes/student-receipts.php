<?php

use App\Http\Controllers\StudentReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('receipts/lookup', [StudentReceiptController::class, 'lookupForm'])
    ->middleware('throttle:15,1')
    ->name('student-receipts.lookup');
Route::post('receipts/lookup', [StudentReceiptController::class, 'lookup'])
    ->middleware('throttle:10,1')
    ->name('student-receipts.search');
Route::post('payment-requests/{paymentRequest}/receipt', [StudentReceiptController::class, 'createFromPaymentRequest'])
    ->name('student-receipts.from-payment-request');
Route::get('receipts/{receipt}', [StudentReceiptController::class, 'show'])
    ->middleware('signed')
    ->name('student-receipts.show');
Route::get('receipts/{receipt}/download', [StudentReceiptController::class, 'download'])
    ->middleware('signed')
    ->name('student-receipts.download');
