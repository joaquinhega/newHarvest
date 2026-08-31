<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CombustibleController;
use App\Http\Controllers\PersonalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\SanctionController;
use App\Http\Controllers\SalaryReceiptController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('vouchers.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'backoffice'])->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Operaciones: Vouchers
    Route::get('/vouchers/export/excel', [VoucherController::class, 'exportExcel'])->name('vouchers.export.excel');
    Route::get('/vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
    Route::patch('/vouchers/{id}/aprobar', [VoucherController::class, 'approve'])->name('vouchers.approve');
    Route::put('/vouchers/{id}', [VoucherController::class, 'update'])->name('vouchers.update');

    // Operaciones: Combustible
    Route::get('/combustible/export/excel', [CombustibleController::class, 'exportExcel'])->name('combustible.export.excel');
    Route::get('/combustible', [CombustibleController::class, 'index'])->name('combustible.index');
    Route::patch('/combustible/{id}/aprobar', [CombustibleController::class, 'approve'])->name('combustible.approve');

    // Empresas
    Route::get('/empresas', [CompanyController::class, 'index'])->name('empresas.index');
    Route::post('/empresas', [CompanyController::class, 'store'])->name('empresas.store');
    Route::put('/empresas/{id}', [CompanyController::class, 'update'])->name('empresas.update');
    Route::delete('/empresas/{id}', [CompanyController::class, 'destroy'])->name('empresas.destroy');

    // Recursos Humanos
    Route::prefix('rrhh')->name('rrhh.')->group(function () {
        // Personal (Legajos)
        Route::get('/personal/export/excel', [PersonalController::class, 'exportExcel'])->name('personal.export.excel');
        Route::get('/personal', [PersonalController::class, 'index'])->name('personal');
        Route::post('/personal', [PersonalController::class, 'store'])->name('personal.store');
        Route::put('/personal/{id}', [PersonalController::class, 'update'])->name('personal.update');
        Route::delete('/personal/{id}', [PersonalController::class, 'destroy'])->name('personal.destroy');

        // Vacaciones y Certificados
        Route::get('/vacaciones/export/excel', [LeaveRequestController::class, 'exportExcel'])->name('vacaciones.export.excel');
        Route::get('/vacaciones', [LeaveRequestController::class, 'index'])->name('vacaciones');
        Route::post('/vacaciones', [LeaveRequestController::class, 'store'])->name('vacaciones.store');
        Route::patch('/vacaciones/{id}/aprobar', [LeaveRequestController::class, 'approve'])->name('vacaciones.approve');
        Route::patch('/vacaciones/{id}/rechazar', [LeaveRequestController::class, 'reject'])->name('vacaciones.reject');
        Route::delete('/vacaciones/{id}', [LeaveRequestController::class, 'destroy'])->name('vacaciones.destroy');

        // Sanciones y Recibos
    Route::get('/recibos/export/excel', [SalaryReceiptController::class, 'exportExcel'])->name('recibos.export.excel');
    Route::get('/recibos', [SalaryReceiptController::class, 'index'])->name('recibos');
    Route::post('/recibos', [SalaryReceiptController::class, 'store'])->name('recibos.store');
    Route::put('/recibos/{id}', [SalaryReceiptController::class, 'update'])->name('recibos.update');
    Route::delete('/recibos/{id}', [SalaryReceiptController::class, 'destroy'])->name('recibos.destroy');

        // Sanciones
        Route::get('/sanciones/export/excel', [SanctionController::class, 'exportExcel'])->name('sanciones.export.excel');
        Route::get('/sanciones', [SanctionController::class, 'index'])->name('sanciones');
        Route::post('/sanciones', [SanctionController::class, 'store'])->name('sanciones.store');
        Route::put('/sanciones/{id}', [SanctionController::class, 'update'])->name('sanciones.update');
        Route::delete('/sanciones/{id}', [SanctionController::class, 'destroy'])->name('sanciones.destroy');
    });
});