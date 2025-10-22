<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequisitionService
{
    /**
     * Get requisitions filtered by user role and church/section/department.
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRequisitions(User $user)
    {
        $query = Requisition::with(['requestedBy', 'department', 'approvals']);

        switch ($user->role) {
            case 'Member':
                // Member only sees their own requisitions
                $query->where('requested_by_id', $user->id);
                break;
            case 'Department Head':
                // Dept Head sees all in their department
                $query->where('department_id', $user->department_id);
                break;
            case 'Section President':
                // Section President sees all in their section
                $query->where('section_id', $user->section_id);
                break;
            case 'Finance':
            case 'Auditor':
                // Finance/Auditor see all in the church
                $query->where('church_id', $user->church_id);
                break;
            case 'Super Admin':
            case 'App Owner':
                // Super Admins/App Owners see everything (or church-wide if Super Admin is scoped)
                $query->where('church_id', $user->church_id);
                break;
        }

        return $query->latest()->get();
    }

    /**
     * Create a new requisition.
     * @param array $data
     * @param User $user
     * @return Requisition
     */
    public function createRequisition(array $data, User $user): Requisition
    {
        $requisition = Requisition::create([
            ...$data,
            'requested_by_id' => $user->id,
            'church_id' => $user->church_id,
            'section_id' => $user->section_id,
            'status' => 'Pending',
        ]);

        // Log the creation
        AuditLogService::log($user, 'REQUISITION_CREATED', 'New requisition created: ' . $requisition->title, $requisition->id);

        return $requisition;
    }

    /**
     * Process workflow actions (APPROVE, REJECT, REQUEST_CHANGES).
     * @param Requisition $requisition
     * @param User $user
     * @param string $action
     * @param string $comments
     * @return Requisition
     * @throws \Exception
     */
    public function processWorkflowAction(Requisition $requisition, User $user, string $action, string $comments = null): Requisition
    {
        return DB::transaction(function () use ($requisition, $user, $action, $comments) {
            // Determine the required status for approval/rejection
            $requiredRole = $requisition->status === 'Pending' ? 'Department Head' : ($requisition->status === 'Approved by Dept. Head' ? 'Section President' : null);

            if (!$requiredRole || $user->role !== $requiredRole) {
                throw ValidationException::withMessages(['action' => 'You do not have the required role or the requisition is not in the correct status for your approval.']);
            }

            // Create Approval Log
            $requisition->approvals()->create([
                'approver_id' => $user->id,
                'status' => $action,
                'comments' => $comments,
            ]);

            // Update Requisition Status
            $newStatus = match ($action) {
                'APPROVE' => $requiredRole === 'Department Head' ? 'Approved by Dept. Head' : 'Awaiting Receipt',
                'REJECT' => 'Rejected',
                'REQUEST_CHANGES' => 'Changes Requested',
                default => throw new \Exception('Invalid workflow action.'),
            };

            $requisition->status = $newStatus;
            $requisition->save();

            // Log the action
            AuditLogService::log($user, 'REQUISITION_WORKFLOW', "Requisition {$action} by {$requiredRole}", $requisition->id);

            return $requisition->fresh(['approvals']);
        });
    }

    /**
     * Disburse payment for an approved requisition.
     * @param Requisition $requisition
     * @param array $paymentDetails
     * @param User $user
     * @return Requisition
     * @throws ValidationException
     */
    public function disbursePayment(Requisition $requisition, array $paymentDetails, User $user): Requisition
    {
        if ($user->role !== 'Finance' || $requisition->status !== 'Approved by Section President') {
            throw ValidationException::withMessages(['payment' => 'Only Finance can disburse payment for a fully approved requisition.']);
        }

        return DB::transaction(function () use ($requisition, $paymentDetails, $user) {
            $requisition->payment()->create([
                ...$paymentDetails,
                'recorded_by_id' => $user->id,
            ]);

            $requisition->status = 'Awaiting Receipt';
            $requisition->save();

            AuditLogService::log($user, 'PAYMENT_DISBURSED', 'Payment disbursed for requisition.', $requisition->id);

            return $requisition->fresh(['payment']);
        });
    }

    // Add methods for updateRequisition, uploadFinalReceipt, verifyFinalReceipt, etc.
}
