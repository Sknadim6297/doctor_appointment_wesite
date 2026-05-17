<?php

namespace App\Http\Controllers\Admin;

use App\Models\DoctorDocument;
use App\Models\DoctorPost;
use App\Models\Enrollment;
use App\Models\LegalCase;
use App\Models\PolicyReceipt;
use App\Http\Controllers\Controller;
use App\Support\EnrollmentWorkflow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Cache TTL for dashboard aggregations (1 hour)
     */
    private const DASHBOARD_CACHE_TTL = 3600;

    public function index()
    {
        // Cache key that invalidates if any relevant table is modified
        $cacheKey = 'dashboard_stats_' . date('YmdH'); // Hourly cache

        // Try to get from cache first
        $cachedData = Cache::get($cacheKey);
        if ($cachedData) {
            return view('admin.dashboard', $cachedData);
        }

        $now = Carbon::now();
        $currentYear = (int) $now->year;
        $previousYear = $currentYear - 1;
        $sixMonthsAgo = $now->copy()->subMonths(6)->startOfDay();
        $yearStart = $now->copy()->startOfYear()->toDateTimeString();

        // ============================================
        // AGGREGATION QUERIES - Optimized with raw counting
        // ============================================
        
        // Single aggregated query for main stats to reduce database round trips
        $productionDoctors = Enrollment::query()->productionReady();

        $mainStats = (clone $productionDoctors)
            ->selectRaw('
                COUNT(DISTINCT id) as total_doctors,
                COUNT(DISTINCT CASE WHEN money_rc_no IS NOT NULL AND money_rc_no != "" THEN id END) as money_receipt_count,
                COUNT(DISTINCT CASE WHEN DATE_ADD(created_at, INTERVAL 1 YEAR) < ? THEN id END) as lapse_count,
                SUM(DISTINCT CASE WHEN DATE_ADD(created_at, INTERVAL 1 YEAR) < ? AND DATE_ADD(created_at, INTERVAL 1 YEAR) >= ? THEN 1 ELSE 0 END) as last_six_months_lapse
            ', [$now->toDateTimeString(), $now->toDateTimeString(), $sixMonthsAgo->toDateTimeString()])
            ->first();

        $doctorCount = (int) $mainStats->total_doctors;
        $moneyReceiptCount = (int) $mainStats->money_receipt_count;
        $lapseCount = (int) $mainStats->lapse_count;
        $lastSixMonthsLapse = (int) $mainStats->last_six_months_lapse;

        $doctorCaseCount = (int) LegalCase::query()->count();
        $doctorPostCount = (int) DoctorPost::query()->count();

        $stats = [
            'enrollment_doctors' => $doctorCount,
            'money_receipts' => $moneyReceiptCount,
            'doctor_cases' => $doctorCaseCount,
            'lapse_list' => $lapseCount,
            'premium_amount' => $moneyReceiptCount,
            'doctor_posts' => $doctorPostCount,
        ];

        // ============================================
        // PROGRESS TRACKING - Combined with distinct counts
        // ============================================
        $progressStats = DB::table('enrollments as e')
            ->selectRaw('
                (SELECT COUNT(DISTINCT enrollment_id) FROM doctor_documents WHERE enrollment_id IS NOT NULL) as with_documents,
                (SELECT COUNT(DISTINCT enrollment_id) FROM legal_cases WHERE enrollment_id IS NOT NULL) as with_cases,
                (SELECT COUNT(DISTINCT enrollment_id) FROM doctor_documents 
                    WHERE enrollment_id IS NOT NULL AND (LOWER(COALESCE(document_type, "")) LIKE "%photo%" 
                    OR LOWER(COALESCE(document_title, "")) LIKE "%photo%")) as with_photo
            ')
            ->first();

        // Handle null case when database is empty
        $progressStats = $progressStats ?? (object) [
            'with_documents' => 0,
            'with_cases' => 0,
            'with_photo' => 0,
        ];

        $progress = [
            'with_documents' => ['count' => (int) $progressStats->with_documents, 'total' => $doctorCount],
            'with_cases' => ['count' => (int) $progressStats->with_cases, 'total' => $doctorCount],
            'with_premium' => ['count' => $moneyReceiptCount, 'total' => $doctorCount],
            'with_photo' => ['count' => (int) $progressStats->with_photo, 'total' => $doctorCount],
            'renew_expired' => ['count' => $lapseCount, 'total' => $doctorCount],
        ];

        // ============================================
        // PLAN DISTRIBUTION - Combined query
        // ============================================
        $planStats = (clone $productionDoctors)
            ->selectRaw('
                SUM(CASE WHEN plan = 1 THEN 1 ELSE 0 END) as normal_count,
                SUM(CASE WHEN plan = 2 THEN 1 ELSE 0 END) as high_count,
                SUM(CASE WHEN plan = 3 THEN 1 ELSE 0 END) as combo_count
            ')
            ->first();

        // Handle null case when database is empty
        $planStats = $planStats ?? (object) [
            'normal_count' => 0,
            'high_count' => 0,
            'combo_count' => 0,
        ];

        $plans = [
            'normal' => (int) $planStats->normal_count,
            'high' => (int) $planStats->high_count,
            'combo' => (int) $planStats->combo_count,
        ];

        // ============================================
        // RECENT ACTIVITY - Combined query
        // ============================================
        $recentStats = (clone $productionDoctors)
            ->selectRaw('
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as last_six_months_enrollment,
                COALESCE(SUM(COALESCE(NULLIF(total_amount, 0), COALESCE(payment_amount, 0) + COALESCE(service_amount, 0))), 0) as all_time_payments
            ', [$sixMonthsAgo->toDateTimeString()])
            ->first();

        $lastSixMonthsEnrollment = (int) $recentStats->last_six_months_enrollment;

        // Last six months renew from policy_receipts
        $lastSixMonthsRenew = (int) PolicyReceipt::query()
            ->where(function ($query) use ($sixMonthsAgo) {
                $query->where('receive_date', '>=', $sixMonthsAgo)
                    ->orWhere(function ($fallback) use ($sixMonthsAgo) {
                        $fallback->whereNull('receive_date')
                            ->where('created_at', '>=', $sixMonthsAgo);
                    });
            })
            ->count();

        // ============================================
        // YEAR COMPARISON - Combined query
        // ============================================
        $yearStats = (clone $productionDoctors)
            ->selectRaw('
                SUM(CASE WHEN YEAR(created_at) = ? THEN 1 ELSE 0 END) as current_enrollment,
                SUM(CASE WHEN YEAR(created_at) = ? THEN 1 ELSE 0 END) as previous_enrollment,
                COALESCE(SUM(CASE WHEN YEAR(created_at) = ? 
                    THEN COALESCE(NULLIF(total_amount, 0), COALESCE(payment_amount, 0) + COALESCE(service_amount, 0))
                    ELSE 0 END), 0) as this_year_payments,
                COALESCE(SUM(CASE WHEN YEAR(created_at) = ? 
                    THEN COALESCE(NULLIF(total_amount, 0), COALESCE(payment_amount, 0) + COALESCE(service_amount, 0))
                    ELSE 0 END), 0) as previous_year_payments,
                COALESCE(SUM(COALESCE(NULLIF(total_amount, 0), COALESCE(payment_amount, 0) + COALESCE(service_amount, 0))), 0) as all_time_payments
            ', [$currentYear, $previousYear, $currentYear, $previousYear])
            ->first();

        // Year comparison for renewals
        $renewalYearStats = DB::table('policy_receipts')
            ->selectRaw('
                SUM(CASE WHEN YEAR(COALESCE(receive_date, created_at)) = ? THEN 1 ELSE 0 END) as current_renew,
                SUM(CASE WHEN YEAR(COALESCE(receive_date, created_at)) = ? THEN 1 ELSE 0 END) as previous_renew
            ', [$currentYear, $previousYear])
            ->first();

        $yearComparison = [
            'previous_enrollment' => (int) $yearStats->previous_enrollment,
            'current_enrollment' => (int) $yearStats->current_enrollment,
            'previous_renew' => (int) $renewalYearStats->previous_renew,
            'current_renew' => (int) $renewalYearStats->current_renew,
        ];

        $payments = [
            'this_year' => (float) $yearStats->this_year_payments,
            'previous_year' => (float) $yearStats->previous_year_payments,
            'all_time' => (float) $yearStats->all_time_payments,
        ];

        // ============================================
        // LATEST ENROLLMENTS - Optimize with select
        // ============================================
        $latest_doctors = Enrollment::query()
            ->productionReady()
            ->select('id', 'doctor_name', 'created_at')
            ->whereNotNull('doctor_name')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $crmCounters = [
            'new_enrollments' => Enrollment::query()->tap(fn ($q) => EnrollmentWorkflow::scopeNewEntries($q))->count(),
            'pending_approvals' => Enrollment::query()->tap(fn ($q) => EnrollmentWorkflow::scopePendingAdminGate($q))->count(),
            'incomplete_drafts' => Enrollment::query()->tap(fn ($q) => EnrollmentWorkflow::scopeIncompletePipeline($q))->count(),
            'completed_enrollments' => Enrollment::query()->productionReady()->count(),
            'rejected_cases' => Enrollment::query()->tap(fn ($q) => EnrollmentWorkflow::scopeRejectedCases($q))->count(),
            'returned_for_correction' => Enrollment::query()->tap(fn ($q) => EnrollmentWorkflow::scopeReturnedForCorrection($q))->count(),
        ];

        $workflowStats = DB::table('enrollments')
            ->selectRaw('
                SUM(CASE WHEN workflow_status = "draft" THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN workflow_status = "in_progress" THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN workflow_status IN ("pending_approval", "pending_review") THEN 1 ELSE 0 END) as pending_approval_count,
                SUM(CASE WHEN workflow_status = "completed" THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN is_step_incomplete = 1 THEN 1 ELSE 0 END) as incomplete_count
            ')
            ->first();

        $incompleteEnrollments = Enrollment::query()
            ->select('id', 'doctor_name', 'customer_id_no', 'workflow_status', 'current_step', 'is_step_incomplete', 'last_activity_at', 'created_at')
            ->where('is_step_incomplete', true)
            ->orderByDesc('last_activity_at')
            ->limit(6)
            ->get();

        $workflowSummary = [
            'draft' => (int) $workflowStats->draft_count,
            'in_progress' => (int) $workflowStats->in_progress_count,
            'pending_approval' => (int) $workflowStats->pending_approval_count,
            'completed' => (int) $workflowStats->completed_count,
            'incomplete' => (int) $workflowStats->incomplete_count,
        ];

        // ============================================
        // PREPARE CACHE DATA
        // ============================================
        $viewData = compact(
            'stats',
            'progress',
            'plans',
            'payments',
            'latest_doctors',
            'workflowSummary',
            'crmCounters',
            'incompleteEnrollments',
            'lastSixMonthsEnrollment',
            'lastSixMonthsRenew',
            'lastSixMonthsLapse',
            'yearComparison'
        );

        // Cache the computed data for 1 hour
        Cache::put($cacheKey, $viewData, self::DASHBOARD_CACHE_TTL);

        return view('admin.dashboard', $viewData);
    }
}

