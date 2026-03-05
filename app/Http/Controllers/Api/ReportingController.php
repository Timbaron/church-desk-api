<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Services\ReportingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReportingController extends Controller
{
    protected ReportingService $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * GET /financial-summary/{section} - getFinancialSummary
     */
    public function getFinancialSummary(Section $section, Request $request)
    {
        // Add policy check: user must belong to this section or be Super Admin/Finance
        if ($request->user()->section_id !== $section->id && !in_array($request->user()->role, ['Super Admin', 'Finance'])) {
            return $this->errorResponse('Unauthorized access to this section data.', Response::HTTP_FORBIDDEN);
        }

        $summary = $this->reportingService->getFinancialSummary($section);
        return $this->successResponse($summary, 'Financial summary retrieved successfully.');
    }

    /**
     * GET /finance-overview/{section} - getFinanceOverview
     */
    public function getFinanceOverview(Section $section, Request $request)
    {
        // Add policy check: user must belong to this section or be Super Admin/Finance
        if ($request->user()->section_id !== $section->id && !in_array($request->user()->role, ['Super Admin', 'Finance'])) {
            return $this->errorResponse('Unauthorized access to this section data.', Response::HTTP_FORBIDDEN);
        }

        $overview = $this->reportingService->getFinanceOverview($section);
        return $this->successResponse($overview, 'Finance overview retrieved successfully.');
    }

    /**
     * GET /platform-data - getPlatformData (App Owner/Super Admin)
     */
    public function getPlatformData(Request $request)
    {
        // Role check already handled by middleware in routes, but keeping the logic consistent for safety
        $data = $this->reportingService->getPlatformData();
        return $this->successResponse($data, 'Platform data retrieved successfully.');
    }
}
