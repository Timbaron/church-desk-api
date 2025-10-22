<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RequisitionController;
use App\Http\Controllers\Api\ChurchController;
use App\Http\Controllers\Api\ReportingController;
use App\Http\Controllers\Api\AuditController; // <-- Added AuditController
use App\Http\Controllers\Api\AuditlogController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| The {requisition}, {church}, {section}, and {auditLog} parameters use
| implicit route model binding where applicable.
*/

// Public/Guest Routes (No Auth required)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'registerChurch']);

// Authenticated Routes (Protected by Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Get currently authenticated user data
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // REQUISITION ENDPOINTS
    Route::prefix('requisitions')->group(function () {
        Route::get('/', [RequisitionController::class, 'index']); // Get list of requisitions
        Route::post('/', [RequisitionController::class, 'store']); // Create a new requisition
        Route::get('/{requisition}', [RequisitionController::class, 'show']); // Get single requisition
        Route::put('/{requisition}', [RequisitionController::class, 'update']); // Update requisition details

        // WORKFLOW & APPROVALS
        Route::post('/{requisition}/action', [RequisitionController::class, 'processAction']); // Approve/Reject/Request Changes
        Route::post('/{requisition}/disburse', [RequisitionController::class, 'disburse']); // Finance disbursement
        Route::post('/{requisition}/upload-receipt', [RequisitionController::class, 'uploadReceipt']); // Requester uploads final receipt
        Route::post('/{requisition}/verify-receipt', [RequisitionController::class, 'verifyReceipt']); // Auditor/Finance verifies receipt
    });

    // CHURCH & USER MANAGEMENT
    Route::post('/users', [ChurchController::class, 'createUser']); // Create a user (Admin only)

    Route::prefix('churches')->group(function () {
        Route::get('/{church}', [ChurchController::class, 'show']); // Get church details
        Route::get('/{church}/users', [ChurchController::class, 'getUsers']); // Get users in church
        Route::post('/{church}/sections', [ChurchController::class, 'createSection']); // Create a new section
        Route::post('/{church}/extend-subscription', [ChurchController::class, 'extendSubscription']); // Extend subscription (Admin/App Owner)
        Route::get('/{church}/audit-logs', [ChurchController::class, 'getAuditLogs']); // Get audit logs for a specific church
    });

    // AUDIT LOGS ENDPOINTS
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [AuditlogController::class, 'index']); // Global view of all logs (Auditor/Super Admin only)
        Route::get('/{auditLog}', [AuditlogController::class, 'show']); // Single log view
    });

    // REPORTING & DASHBOARD
    Route::get('/financial-summary/{section}', [ReportingController::class, 'getFinancialSummary']); // Section-level summary
    Route::get('/finance-overview/{section}', [ReportingController::class, 'getFinanceOverview']); // Section-level finance overview
    Route::get('/platform-data', [ReportingController::class, 'getPlatformData']); // Global platform data (App Owner/Super Admin only)
});
