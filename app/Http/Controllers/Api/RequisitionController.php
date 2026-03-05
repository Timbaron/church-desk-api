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
        $this->requisitionService = $requisitionService;
    }

    /**
     * GET /requisitions - Get all filtered requisitions
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $requisitions = $this->requisitionService->getRequisitions($user);

        return $this->successResponse($requisitions, 'Requisitions retrieved successfully.');
    }

    /**
     * GET /requisitions/{id} - Get requisition by ID
     */
    public function show(Requisition $requisition)
    {
        return $this->successResponse($requisition->load(['approvals', 'payment']), 'Requisition details retrieved successfully.');
    }

    /**
     * POST /requisitions - Create a new requisition
     */
    public function store(RequisitionStoreRequest $request)
    {
        try {
            $requisition = $this->requisitionService->createRequisition($request->validated(), $request->user());
            return $this->successResponse($requisition, 'Requisition created successfully.', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->errorResponse('Error creating requisition.', Response::HTTP_INTERNAL_SERVER_ERROR);
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
            return $this->successResponse($updatedRequisition, 'Action processed successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_FORBIDDEN);
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
            return $this->successResponse($updatedRequisition, 'Payment disbursed successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * PUT /requisitions/{id} - Update a requisition
     */
    public function update(Request $request, Requisition $requisition)
    {
        try {
            // Validation should ideally use a separate FormRequest
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'amount_requested' => 'sometimes|numeric',
                'purpose' => 'sometimes|string',
                'category' => 'sometimes|string',
                'date_needed' => 'sometimes|date',
            ]);

            $updatedRequisition = $this->requisitionService->updateRequisition($requisition, $validated, $request->user());
            return $this->successResponse($updatedRequisition, 'Requisition updated successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * POST /requisitions/{reqId}/upload-receipt - Upload final receipt
     */
    public function uploadReceipt(Request $request, Requisition $requisition)
    {
        $request->validate(['receiptFileName' => ['required', 'string']]); // Mocking file upload

        try {
            $updatedRequisition = $this->requisitionService->uploadFinalReceipt(
                $requisition,
                $request->input('receiptFileName'),
                $request->user()
            );
            return $this->successResponse($updatedRequisition, 'Receipt uploaded successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * POST /requisitions/{reqId}/verify-receipt - Verify final receipt
     */
    public function verifyReceipt(Request $request, Requisition $requisition)
    {
        $request->validate([
            'action' => 'required|in:VERIFY,REJECT',
            'comments' => 'nullable|string'
        ]);

        try {
            $updatedRequisition = $this->requisitionService->verifyFinalReceipt(
                $requisition,
                $request->user(),
                $request->input('action'),
                $request->input('comments')
            );
            return $this->successResponse($updatedRequisition, 'Receipt verified successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_FORBIDDEN);
        }
    }
}
