<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\CombustibleController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('vouchers.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware(['auth'])->group(function () {
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
        Route::get('/personal', function () {
            return Inertia::render('RRHH/Personal');
        })->name('personal');

        Route::get('/recibos', function () {
            return Inertia::render('RRHH/Recibos');
        })->name('recibos');

        Route::get('/sanciones', function () {
            return Inertia::render('RRHH/Sanciones');
        })->name('sanciones');

        Route::get('/vacaciones', function () {
            return Inertia::render('RRHH/Vacaciones');
        })->name('vacaciones');
    });
});