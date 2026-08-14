<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CombustibleController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\SalaryReceiptController;
use App\Http\Controllers\Api\SanctionController;
use App\Http\Controllers\Api\VoucherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // 1.2 Módulos Operativos
    Route::apiResource('companies', CompanyController::class);
    Route::apiResource('combustibles', CombustibleController::class);
    Route::patch('combustibles/{combustible}/approve', [CombustibleController::class, 'approve']);
    Route::apiResource('vouchers', VoucherController::class);
    Route::patch('vouchers/{voucher}/approve', [VoucherController::class, 'approve']);

    // 1.3 Módulo RRHH
    Route::apiResource('employees', EmployeeController::class);
    
    // Recibos de Sueldo
    Route::apiResource('salary-receipts', SalaryReceiptController::class)->except(['destroy', 'update']);
    Route::patch('salary-receipts/{salaryReceipt}/sign-employer', [SalaryReceiptController::class, 'signEmployer']);
    Route::post('salary-receipts/{salaryReceipt}/sign-employee', [SalaryReceiptController::class, 'signEmployee']);

    // Sanciones
    Route::apiResource('sanctions', SanctionController::class)->except(['destroy', 'update']);
    Route::patch('sanctions/{sanction}/confirm-read', [SanctionController::class, 'confirmRead']);

    // Vacaciones y Certificados Médicos
    Route::apiResource('leave-requests', LeaveRequestController::class)->except(['destroy', 'update']);
    Route::patch('leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
    Route::patch('leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);
});

Route::get('/v1/health-check', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'New Harvest API v1 funcionando correctamente',
        'timestamp' => now()->toIso8601String()
    ], 200);
});