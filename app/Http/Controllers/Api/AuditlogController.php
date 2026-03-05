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
        $user = $request->user();
        
        $query = AuditLog::with(['user:id,name,email,role', 'church:id,name', 'requisition:id,title'])
            ->latest();

        // If the user is not an App Owner, restrict logs to their church
        if ($user->role !== 'App Owner') {
            $query->where('church_id', $user->church_id);
        }

        $logs = $query->paginate(50);

        return $this->successResponse($logs, 'Audit logs retrieved successfully.');
    }

    /**
     * GET /audit-logs/{auditLog} - get single audit log
     * Ensures the user has permission to view this specific log entry.
     */
    public function show(AuditLog $auditLog, Request $request)
    {
        $user = $request->user();

        // Unless the user is an App Owner, the log must belong to their church
        if ($user->role !== 'App Owner' && $user->church_id !== $auditLog->church_id) {
            return $this->errorResponse('Unauthorized to view this audit log.', Response::HTTP_FORBIDDEN);
        }

        // Load relationships for detailed view
        $auditLog->load(['user:id,name,email,role', 'church:id,name', 'requisition:id,title']);

        return $this->successResponse($auditLog, 'Audit log retrieved successfully.');
    }
}
