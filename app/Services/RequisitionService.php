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
        // Handle file uploads if 'attachments' are present
        $attachmentData = [];
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $path = $file->store('requisitions/attachments', 'public');
                    $attachmentData[] = [
                        'name' => $file->getClientOriginalName(),
                        'url' => asset('storage/' . $path),
                    ];
                }
            }
        }
        // Always store as array of processed attachment info, even if empty
        $data['attachments'] = $attachmentData;

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
                'APPROVE' => $requiredRole === 'Department Head' ? 'Approved by Dept. Head' : 'Approved by Section President',
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
     * Update an existing requisition.
     * @param Requisition $requisition
     * @param array $data
     * @param User $user
     * @return Requisition
     */
    public function updateRequisition(Requisition $requisition, array $data, User $user): Requisition
    {
        if ($requisition->requested_by_id !== $user->id || !in_array($requisition->status, ['Pending', 'Changes Requested'])) {
            throw new \Exception('Unauthorized or requisition is not in an editable state.');
        }

        $requisition->update($data);
        AuditLogService::log($user, 'REQUISITION_UPDATED', 'Requisition details updated.', $requisition->id);

        return $requisition;
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

    /**
     * Upload final expense receipt.
     * @param Requisition $requisition
     * @param string $receiptFileName
     * @param User $user
     * @return Requisition
     */
    public function uploadFinalReceipt(Requisition $requisition, \Illuminate\Http\UploadedFile $receiptFile, User $user): Requisition
    {
        if ($requisition->requested_by_id !== $user->id || $requisition->status !== 'Awaiting Receipt') {
            throw new \Exception('Unauthorized or invalid status for receipt upload.');
        }

        $path = $receiptFile->store('requisitions/receipts', 'public');

        $requisition->final_receipt = [
            'name' => $receiptFile->getClientOriginalName(),
            'url' => asset('storage/' . $path),
            'uploadedAt' => now()->toIso8601String(),
        ];
        $requisition->status = 'Pending Finance Verification';
        $requisition->save();

        AuditLogService::log($user, 'RECEIPT_UPLOADED', 'Final receipt uploaded.', $requisition->id);

        return $requisition;
    }

    /**
     * Verify or request correction for final receipt.
     * @param Requisition $requisition
     * @param User $user
     * @param string $action (VERIFY or REJECT)
     * @param string|null $comments
     * @return Requisition
     */
    public function verifyFinalReceipt(Requisition $requisition, User $user, string $action, string $comments = null): Requisition
    {
        if ($user->role !== 'Finance' && $user->role !== 'Auditor') {
            throw new \Exception('Unauthorized role for receipt verification.');
        }

        if ($requisition->status !== 'Pending Finance Verification') {
            throw new \Exception('Requisition is not awaiting verification.');
        }

        $requisition->status = $action === 'VERIFY' ? 'Completed' : 'Receipt Correction Requested';
        $requisition->save();

        AuditLogService::log($user, 'RECEIPT_VERIFIED', "Receipt verification: {$action}. {$comments}", $requisition->id);

        return $requisition;
    }
}
