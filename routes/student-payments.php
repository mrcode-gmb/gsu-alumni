<?php

use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\StudentPaymentCheckoutController;
use App\Http\Controllers\StudentPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('payments', [StudentPaymentController::class, 'create'])->name('student-payments.create');
Route::post('payments', [StudentPaymentController::class, 'store'])->name('student-payments.store');
Route::get('payment-requests/{paymentRequest}', [StudentPaymentController::class, 'show'])->name('student-payments.show');
Route::post('payment-requests/{paymentRequest}/paystack/initialize', [StudentPaymentCheckoutController::class, 'initialize'])
    ->name('student-payments.paystack.initialize');
Route::post('payment-requests/{paymentRequest}/paystack/verify', [StudentPaymentCheckoutController::class, 'verify'])
    ->name('student-payments.paystack.verify');
Route::get('payment-requests/{paymentRequest}/paystack/cancel', [StudentPaymentCheckoutController::class, 'cancel'])
    ->name('student-payments.paystack.cancel');
Route::get('payments/paystack/callback', [StudentPaymentCheckoutController::class, 'callback'])
    ->name('student-payments.paystack.callback');
Route::post('payments/paystack/webhook', PaystackWebhookController::class)
    ->name('student-payments.paystack.webhook');
