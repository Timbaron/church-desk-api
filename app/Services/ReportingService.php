<?php

namespace App\Services;

use App\Models\Requisition;
use App\Models\Section;
use App\Models\Church;
use App\Models\User;
use App\Models\AuditLog;

class ReportingService
{
    /**
     * Generates a basic financial summary for a section.
     * In a real system, this would interact with a dedicated G/L system.
     * @param Section $section
     * @return array
     */
    public function getFinancialSummary(Section $section): array
    {
        // Mock calculation based on requisitions
        $outflow = Requisition::where('section_id', $section->id)
            ->whereIn('status', ['Completed', 'Pending Finance Verification', 'Awaiting Receipt'])
            ->sum('amount_requested');

        // Assume a mock starting balance and inflow for demonstration
        $totalInflow = 50000.00;
        $balance = $totalInflow - $outflow;

        return [
            'balance' => round($balance, 2),
            'total_inflow' => round($totalInflow, 2),
            'total_outflow' => round($outflow, 2),
        ];
    }

    /**
     * Generates a finance overview dashboard for a section (primarily for Finance role).
     * @param Section $section
     * @return array
     */
    public function getFinanceOverview(Section $section): array
    {
        $awaitingDisbursement = $section->requisitions()
            ->where('status', 'Approved by Section President')
            ->latest()->limit(5)->get();

        $pendingVerification = $section->requisitions()
            ->where('status', 'Pending Finance Verification')
            ->latest()->limit(5)->get();

        $recentlyCompleted = $section->requisitions()
            ->where('status', 'Completed')
            ->latest()->limit(5)->get();

        $totalDisbursed = $section->requisitions()->whereHas('payment')->sum('amount_requested');

        return [
            'awaiting_disbursement' => $awaitingDisbursement,
            'pending_verification' => $pendingVerification,
            'recently_completed' => $recentlyCompleted,
            'total_disbursed' => round($totalDisbursed, 2),
        ];
    }

    /**
     * Generates platform-wide data (for App Owner/Super Admin).
     * @return array
     */
    public function getPlatformData(): array
    {
        $totalUsers = User::count();
        $totalRequisitions = Requisition::count();
        $totalAmountRequested = Requisition::sum('amount_requested');

        $requisitionStatusCounts = Requisition::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $subscriptionStatusCounts = Church::selectRaw('subscription_status, count(*) as count')
            ->groupBy('subscription_status')
            ->pluck('count', 'subscription_status');

        $recentActivities = AuditLog::with('user', 'church')
            ->orderByDesc('timestamp')
            ->limit(10)->get();

        return [
            'churches' => Church::with('sections')->get(),
            'total_users' => $totalUsers,
            'total_requisitions' => $totalRequisitions,
            'total_amount_requested' => round($totalAmountRequested, 2),
            'requisition_status_counts' => $requisitionStatusCounts,
            'subscription_status_counts' => $subscriptionStatusCounts,
            'recent_activities' => $recentActivities,
        ];
    }
}
