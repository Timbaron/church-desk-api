<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RequisitionController;
use App\Http\Controllers\Api\ChurchController;
use App\Http\Controllers\Api\ReportingController;
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

        // WORKFLOW & APPROVALS (Dept Heads, Section Presidents, Finance & Auditors)
        Route::post('/{requisition}/action', [RequisitionController::class, 'processAction'])
            ->middleware('role:Department Head,Section President,Finance,Auditor');

        // FINANCE ONLY
        Route::post('/{requisition}/disburse', [RequisitionController::class, 'disburse'])
            ->middleware('role:Finance');

        // REQUESTER ONLY (Checks inside service, but base auth is enough for route)
        Route::post('/{requisition}/upload-receipt', [RequisitionController::class, 'uploadReceipt']);

        // AUDITORS & FINANCE
        Route::post('/{requisition}/verify-receipt', [RequisitionController::class, 'verifyReceipt'])
            ->middleware('role:Auditor,Finance');
    });

    // CHURCH & USER MANAGEMENT
    Route::post('/users', [ChurchController::class, 'createUser'])
        ->middleware('role:Super Admin,App Owner');

    Route::prefix('churches')->group(function () {
        Route::get('/{church}', [ChurchController::class, 'show']); // Get church details
        Route::get('/{church}/users', [ChurchController::class, 'getUsers']); // Get users in church
        Route::post('/{church}/sections', [ChurchController::class, 'createSection'])
            ->middleware('role:Super Admin,App Owner');

        Route::post('/{church}/extend-subscription', [ChurchController::class, 'extendSubscription'])
            ->middleware('role:Super Admin,App Owner');

        Route::get('/{church}/audit-logs', [ChurchController::class, 'getAuditLogs'])
            ->middleware('role:Auditor,Super Admin,App Owner,Finance');
    });

    // DEPARTMENT MANAGEMENT
    Route::post('/sections/{section}/departments', [ChurchController::class, 'createDepartment'])
        ->middleware('role:Super Admin,Section President,App Owner');

    // AUDIT LOGS ENDPOINTS
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [AuditlogController::class, 'index'])
            ->middleware('role:Auditor,Super Admin,App Owner');
        Route::get('/{auditLog}', [AuditlogController::class, 'show']);
    });

    // REPORTING & DASHBOARD
    Route::get('/financial-summary/{section}', [ReportingController::class, 'getFinancialSummary']);
    Route::get('/finance-overview/{section}', [ReportingController::class, 'getFinanceOverview']);
    Route::get('/platform-data', [ReportingController::class, 'getPlatformData'])
        ->middleware('role:App Owner,Super Admin');
});
