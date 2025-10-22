<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogService
{
    /**
     * Log an activity to the audit_logs table.
     * @param User $user The user who performed the action.
     * @param string $action The action type (e.g., 'REQUISITION_CREATED').
     * @param string $details A brief description of the action.
     * @param string|null $requisitionId Optional UUID of the affected requisition.
     */
    public static function log(User $user, string $action, string $details, string $requisitionId = null): void
    {
        AuditLog::create([
            'user_id' => $user->id,
            'church_id' => $user->church_id,
            'requisition_id' => $requisitionId,
            'action' => $action,
            'details' => $details,
        ]);
    }
}
