<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RequisitionStoreRequest;
use App\Http\Requests\WorkflowActionRequest;
use App\Http\Requests\DisbursePaymentRequest;
use App\Models\Requisition;
use App\Services\RequisitionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RequisitionController extends Controller
{
    protected RequisitionService $requisitionService;

    public function __construct(RequisitionService $requisitionService)
    {
        // Apply middleware to ensure user is authenticated
        $this->middleware('auth:sanctum');
        $this->requisitionService = $requisitionService;
    }

    /**
     * GET /requisitions - Get all filtered requisitions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $requisitions = $this->requisitionService->getRequisitions($user);

        return response()->json($requisitions);
    }

    /**
     * GET /requisitions/{id} - Get requisition by ID
     */
    public function show(Requisition $requisition)
    {
        // Policy check would normally go here to ensure user can view the requisition
        return response()->json($requisition->load(['approvals', 'payment']));
    }

    /**
     * POST /requisitions - Create a new requisition
     */
    public function store(RequisitionStoreRequest $request)
    {
        try {
            $requisition = $this->requisitionService->createRequisition($request->validated(), $request->user());
            return response()->json($requisition, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating requisition.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST /requisitions/{reqId}/action - Process workflow action
     */
    public function processAction(WorkflowActionRequest $request, Requisition $requisition)
    {
        $user = $request->user();
        $validated = $request->validated();

        try {
            $updatedRequisition = $this->requisitionService->processWorkflowAction(
                $requisition,
                $user,
                $validated['action'],
                $validated['comments'] ?? null
            );
            return response()->json($updatedRequisition);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * POST /requisitions/{reqId}/disburse - Disburse payment
     */
    public function disburse(DisbursePaymentRequest $request, Requisition $requisition)
    {
        try {
            $updatedRequisition = $this->requisitionService->disbursePayment(
                $requisition,
                $request->validated('paymentDetails'), // Client sends { paymentDetails: {...} }
                $request->user()
            );
            return response()->json($updatedRequisition);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * POST /requisitions/{reqId}/upload-receipt - Upload final receipt
     */
    public function uploadReceipt(Request $request, Requisition $requisition)
    {
        $request->validate(['receiptFileName' => ['required', 'string']]); // Mocking file upload

        if ($requisition->requested_by_id !== $request->user()->id || $requisition->status !== 'Awaiting Receipt') {
            return response()->json(['message' => 'Unauthorized or invalid status for receipt upload.'], Response::HTTP_FORBIDDEN);
        }

        // In a real app: handle file upload, store path/name
        $requisition->final_receipt = [
            'name' => $request->input('receiptFileName'),
            'url' => 'path/to/receipt/' . $request->input('receiptFileName'), // Mock URL
            'uploadedAt' => now()->toIso8601String(),
        ];
        $requisition->status = 'Pending Finance Verification';
        $requisition->save();

        // Log the action
        // AuditLogService::log($request->user(), 'RECEIPT_UPLOADED', 'Final receipt uploaded.', $requisition->id);

        return response()->json($requisition);
    }

    // ... other methods like updateRequisition and verifyFinalReceipt would follow a similar pattern
}
