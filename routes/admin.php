<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\ChargeSettingController;
use App\Http\Controllers\Admin\PaymentTypeController;
use App\Http\Controllers\Admin\PaymentRecordController;
use App\Http\Controllers\Admin\ProgramTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin_role'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('super_admin')->group(function () {
            Route::get('charge-settings', [ChargeSettingController::class, 'edit'])->name('charge-settings.edit');
            Route::put('charge-settings', [ChargeSettingController::class, 'update'])->name('charge-settings.update');
        });

        Route::get('payment-types', [PaymentTypeController::class, 'index'])->name('payment-types.index');
        Route::get('payment-types/create', [PaymentTypeController::class, 'create'])->name('payment-types.create');
        Route::post('payment-types', [PaymentTypeController::class, 'store'])->name('payment-types.store');
        Route::get('payment-types/{paymentType}/edit', [PaymentTypeController::class, 'edit'])->name('payment-types.edit');
        Route::put('payment-types/{paymentType}', [PaymentTypeController::class, 'update'])->name('payment-types.update');
        Route::patch('payment-types/{paymentType}/status', [PaymentTypeController::class, 'updateStatus'])->name('payment-types.status.update');
        Route::delete('payment-types/{paymentType}', [PaymentTypeController::class, 'destroy'])->name('payment-types.destroy');

        Route::get('program-types', [ProgramTypeController::class, 'index'])->name('program-types.index');
        Route::get('program-types/create', [ProgramTypeController::class, 'create'])->name('program-types.create');
        Route::post('program-types', [ProgramTypeController::class, 'store'])->name('program-types.store');
        Route::get('program-types/{programType}/edit', [ProgramTypeController::class, 'edit'])->name('program-types.edit');
        Route::put('program-types/{programType}', [ProgramTypeController::class, 'update'])->name('program-types.update');
        Route::patch('program-types/{programType}/status', [ProgramTypeController::class, 'updateStatus'])->name('program-types.status.update');
        Route::delete('program-types/{programType}', [ProgramTypeController::class, 'destroy'])->name('program-types.destroy');

        Route::get('faculties', [FacultyController::class, 'index'])->name('faculties.index');
        Route::get('faculties/create', [FacultyController::class, 'create'])->name('faculties.create');
        Route::post('faculties', [FacultyController::class, 'store'])->name('faculties.store');
        Route::get('faculties/{faculty}/edit', [FacultyController::class, 'edit'])->name('faculties.edit');
        Route::put('faculties/{faculty}', [FacultyController::class, 'update'])->name('faculties.update');
        Route::patch('faculties/{faculty}/status', [FacultyController::class, 'updateStatus'])->name('faculties.status.update');
        Route::delete('faculties/{faculty}', [FacultyController::class, 'destroy'])->name('faculties.destroy');

        Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::patch('departments/{department}/status', [DepartmentController::class, 'updateStatus'])->name('departments.status.update');
        Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        Route::get('payment-records/print', [PaymentRecordController::class, 'print'])->name('payment-records.print');
        Route::get('payment-records/download-pdf', [PaymentRecordController::class, 'downloadPdf'])->name('payment-records.download-pdf');
        Route::get('payment-records', [PaymentRecordController::class, 'index'])->name('payment-records.index');
        Route::post('payment-records/bulk-delete', [PaymentRecordController::class, 'bulkDelete'])->name('payment-records.bulk-delete');
        Route::get('payment-records/{paymentRequest}', [PaymentRecordController::class, 'show'])->name('payment-records.show');
        Route::get('payment-records/{paymentRequest}/print', [PaymentRecordController::class, 'printSingle'])->name('payment-records.print-single');
        Route::post('payment-records/{paymentRequest}/receipt', [PaymentRecordController::class, 'receipt'])->name('payment-records.receipt');
    });
