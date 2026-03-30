<?php

use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\StudentPaymentCheckoutController;
use App\Http\Controllers\StudentPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('payments', [StudentPaymentController::class, 'create'])->name('student-payments.create');
Route::post('payments', [StudentPaymentController::class, 'store'])->name('student-payments.store');
Route::get('payment-requests/{paymentRequest}', [StudentPaymentController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('student-payments.show');
Route::post('payment-requests/{paymentRequest}/access', [StudentPaymentController::class, 'access'])
    ->middleware('throttle:10,1')
    ->name('student-payments.access');
Route::post('payment-requests/{paymentRequestf}/paystack/initialize', [StudentPaymentCheckoutController::class, 'initialize'])
    ->middleware('throttle:10,1')
    ->name('student-payments.paystack.initialize');
Route::post('payment-requests/{paymentRequest}/paystack/verify', [StudentPaymentCheckoutController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('student-payments.paystack.verify');
Route::get('payment-requests/{paymentRequest}/paystack/cancel', [StudentPaymentCheckoutController::class, 'cancel'])
    ->middleware('throttle:10,1')
    ->name('student-payments.paystack.cancel');
Route::get('payments/paystack/callback', [StudentPaymentCheckoutController::class, 'callback'])
    ->middleware('throttle:30,1')
    ->name('student-payments.paystack.callback');
Route::post('payments/paystack/webhook', PaystackWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('student-payments.paystack.webhook');
