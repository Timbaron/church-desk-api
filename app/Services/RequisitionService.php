<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Requisition;
use App\Models\User;
use App\Notifications\RequisitionActionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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
        $department = Department::with('section')->findOrFail($data['department_id']);

        if ($department->section->church_id !== $user->church_id) {
            throw ValidationException::withMessages([
                'department_id' => 'The selected department does not belong to your church.',
            ]);
        }

        if ($user->role === 'Member' && $user->department_id !== $department->id) {
            throw ValidationException::withMessages([
                'department_id' => 'Members can only create requisitions for their own department.',
            ]);
        }

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
            'section_id' => $department->section_id,
            'status' => 'Pending',
        ]);

        // Log the creation
        AuditLogService::log($user, 'REQUISITION_CREATED', 'New requisition created: ' . $requisition->title, $requisition->id);

        // Notify Department Heads
        $deptHeads = User::where('department_id', $requisition->department_id)
            ->where('role', 'Department Head')
            ->get();
            
        if ($deptHeads->isNotEmpty()) {
            Notification::send($deptHeads, new RequisitionActionNotification(
                $requisition,
                'New Requisition Submitted',
                "{$user->name} has submitted a new requisition that requires your approval."
            ));
        }

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
            $this->assertCanViewRequisition($user, $requisition);

            $normalizedAction = strtoupper($action);
            // Determine the required role for approval/rejection
            $requiredRole = $requisition->status === 'Pending' ? 'Department Head' : ($requisition->status === 'Approved by Dept. Head' ? 'Section President' : null);

            if (!$requiredRole || $user->role !== $requiredRole) {
                throw ValidationException::withMessages(['action' => 'You do not have the required role or the requisition is not in the correct status for your approval.']);
            }

            // Map incoming action to enum values expected by the approvals table
            $status = match($normalizedAction) {
                'APPROVE' => 'APPROVED',
                'REJECT' => 'REJECTED',
                'REQUEST_CHANGES' => 'REQUESTED_CHANGES',
                default => $normalizedAction,
            };

            // Create Approval Log using the mapped status
            $requisition->approvals()->create([
                'approver_id' => $user->id,
                'status' => $status,
                'comments' => $comments,
            ]);

            // Update Requisition Status
            $newStatus = match ($normalizedAction) {
                'APPROVE' => $requiredRole === 'Department Head' ? 'Approved by Dept. Head' : 'Approved by Section President',
                'REJECT' => 'Rejected',
                'REQUEST_CHANGES' => 'Changes Requested',
                default => throw new \Exception('Invalid workflow action.'),
            };

            $requisition->status = $newStatus;
            $requisition->save();

            // Log the action
            AuditLogService::log($user, 'REQUISITION_WORKFLOW', "Requisition {$normalizedAction} by {$requiredRole}", $requisition->id);

            // Notify relevant parties
            $requester = $requisition->requestedBy;
            
            if ($normalizedAction === 'APPROVE') {
                if ($requiredRole === 'Department Head') {
                    // Notify Requester
                    if ($requester) $requester->notify(new RequisitionActionNotification($requisition, 'Requisition Approved by Dept Head', 'Your requisition has been approved by the Department Head.'));
                    
                    // Notify Section Presidents
                    $sectionPresidents = User::where('section_id', $requisition->section_id)
                        ->where('role', 'Section President')
                        ->get();
                    if ($sectionPresidents->isNotEmpty()) {
                        Notification::send($sectionPresidents, new RequisitionActionNotification($requisition, 'Requisition Pending Your Approval', 'A requisition has been approved by the Department Head and now requires your approval.'));
                    }
                } elseif ($requiredRole === 'Section President') {
                    // Notify Requester
                    if ($requester) $requester->notify(new RequisitionActionNotification($requisition, 'Requisition Fully Approved', 'Your requisition has been fully approved by the Section President.'));
                    
                    // Notify Finance
                    $finances = User::where('church_id', $requisition->church_id)
                        ->where('role', 'Finance')
                        ->get();
                    if ($finances->isNotEmpty()) {
                        Notification::send($finances, new RequisitionActionNotification($requisition, 'Requisition Ready for Disbursement', 'A requisition has been fully approved and is awaiting payment disbursement.'));
                    }
                }
            } else {
                // REJECT or REQUEST_CHANGES -> Notify Requester
                $actionText = $normalizedAction === 'REJECT' ? 'rejected' : 'returned for changes';
                if ($requester) {
                    $requester->notify(new RequisitionActionNotification($requisition, "Requisition " . ucfirst($actionText), "Your requisition was {$actionText} by the {$requiredRole}. Comments: " . ($comments ?? 'None')));
                }
            }

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
        $this->assertCanViewRequisition($user, $requisition);

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

            // Notify Requester
            if ($requisition->requestedBy) {
                $requisition->requestedBy->notify(new RequisitionActionNotification($requisition, 'Payment Disbursed', "Payment has been disbursed for your requisition. Please upload the receipt once available. Method: {$paymentDetails['payment_method']}"));
            }

            return $requisition->fresh(['payment', 'approvals']);
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
        $this->assertCanViewRequisition($user, $requisition);

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

        // Notify Finance and Auditors
        $financeAndAuditors = User::where('church_id', $requisition->church_id)
            ->whereIn('role', ['Finance', 'Auditor'])
            ->get();
            
        if ($financeAndAuditors->isNotEmpty()) {
             Notification::send($financeAndAuditors, new RequisitionActionNotification($requisition, 'Receipt Uploaded', "The final receipt for requisition '{$requisition->title}' has been uploaded and is pending verification."));
        }

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
        $this->assertCanViewRequisition($user, $requisition);

        if ($user->role !== 'Finance' && $user->role !== 'Auditor') {
            throw new \Exception('Unauthorized role for receipt verification.');
        }

        if ($requisition->status !== 'Pending Finance Verification') {
            throw new \Exception('Requisition is not awaiting verification.');
        }

        $requisition->status = $action === 'VERIFY' ? 'Completed' : 'Receipt Correction Requested';
        $requisition->save();

        AuditLogService::log($user, 'RECEIPT_VERIFIED', "Receipt verification: {$action}. {$comments}", $requisition->id);

        // Notify Requester
        if ($requisition->requestedBy) {
            $statusText = $action === 'VERIFY' ? 'verified successfully' : 'returned for corrections';
            $requisition->requestedBy->notify(new RequisitionActionNotification($requisition, 'Receipt Review', "Your uploaded receipt was {$statusText}. Comments: " . ($comments ?? 'None')));
        }

        return $requisition;
    }

    public function getRequisitionForUser(Requisition $requisition, User $user): Requisition
    {
        $this->assertCanViewRequisition($user, $requisition);

        return $requisition->load(['approvals', 'payment']);
    }

    private function assertCanViewRequisition(User $user, Requisition $requisition): void
    {
        if ($user->role === 'App Owner') {
            return;
        }

        if ($user->church_id !== $requisition->church_id) {
            throw new \Exception('Unauthorized access to this requisition.');
        }

        $hasAccess = match ($user->role) {
            'Member' => $requisition->requested_by_id === $user->id,
            'Department Head' => $requisition->department_id === $user->department_id,
            'Section President' => $requisition->section_id === $user->section_id,
            default => true,
        };

        if (!$hasAccess) {
            throw new \Exception('Unauthorized access to this requisition.');
        }
    }
}
