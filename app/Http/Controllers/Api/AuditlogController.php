<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditlogController extends Controller
{
    public function __construct()
    {
        // All audit routes require authentication
    }

    /**
     * GET /audit-logs - get all audit logs (Super Admin/Auditor access)
     * Fetches all logs across the platform with pagination.
     */
    public function index(Request $request)
    {
        // Only Super Admin, Auditor, or App Owner can access global logs
        if (!in_array($request->user()->role, ['Super Admin', 'Auditor', 'App Owner'])) {
            return response()->json([
                'message' => 'Unauthorized to view global audit logs.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Fetch logs with user, church, and requisition relationships and paginate them
        $logs = AuditLog::with(['user:id,name,email,role', 'church:id,name', 'requisition:id,title'])
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * GET /audit-logs/{auditLog} - get single audit log
     * Ensures the user has permission to view this specific log entry.
     */
    public function show(AuditLog $auditLog, Request $request)
    {
        // Must belong to the user's church OR be a Super Admin/Auditor/App Owner
        if (
            $request->user()->church_id !== $auditLog->church_id &&
            !in_array($request->user()->role, ['Super Admin', 'Auditor', 'App Owner'])
        ) {
            return response()->json([
                'message' => 'Unauthorized to view this audit log.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Load relationships for detailed view
        $auditLog->load(['user:id,name,email,role', 'church:id,name', 'requisition:id,title']);

        return response()->json($auditLog);
    }
}
