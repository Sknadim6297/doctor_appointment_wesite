<?php

namespace App\Http\Controllers\Admin;

use App\Models\DoctorDocument;
use App\Models\DoctorPost;
use App\Models\Enrollment;
use App\Models\LegalCase;
use App\Models\PolicyReceipt;
use App\Http\Controllers\Controller;
use App\Services\DashboardCacheService;
use App\Services\EnrollmentRecordAccessService;
use App\Support\EnrollmentWorkflow;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Cache TTL for dashboard aggregations (1 hour)
     */
    private const DASHBOARD_CACHE_TTL = 3600;

    public function __construct(
        private readonly EnrollmentRecordAccessService $recordAccess,
    ) {
    }

    public function index()
    {
        $user = Auth::user();
        $cacheKey = DashboardCacheService::cacheKeyForUser($user?->id);
        $isEmployeeDashboard = $this->recordAccess->isEmployeeLike($user);

        $cachedHeavy = Cache::get($cacheKey);
        if (is_array($cachedHeavy) && isset($cachedHeavy['stats'])) {
            return view('admin.dashboard', array_merge(
                $cachedHeavy,
                $this->buildLivePipelineStats($user, $isEmployeeDashboard)
            ));
        }

        $now = Carbon::now();
        $currentYear = (int) $now->year;
        $previousYear = $currentYear - 1;
        $sixMonthsAgo = $now->copy()->subMonths(6)->startOfDay();

        $productionDoctors = Enrollment::query()->productionReady();

        $mainStats = (clone $productionDoctors)
            ->selectRaw('
                COUNT(DISTINCT id) as total_doctors,
                COUNT(DISTINCT CASE WHEN money_rc_no IS NOT NULL AND money_rc_no != "" THEN id END) as money_receipt_count,
                COUNT(DISTINCT CASE WHEN DATE_ADD(created_at, INTERVAL 1 YEAR) < ? THEN id END) as lapse_count,
                SUM(DISTINCT CASE WHEN DATE_ADD(created_at, INTERVAL 1 YEAR) < ? AND DATE_ADD(created_at, INTERVAL 1 YEAR) >= ? THEN 1 ELSE 0 END) as last_six_months_lapse
            ', [$now->toDateTimeString(), $now->toDateTimeString(), $sixMonthsAgo->toDateTimeString()])
            ->first();

        $doctorCount = (int) ($mainStats->total_doctors ?? 0);
        $moneyReceiptCount = (int) ($mainStats->money_receipt_count ?? 0);
        $lapseCount = (int) ($mainStats->lapse_count ?? 0);
        $lastSixMonthsLapse = (int) ($mainStats->last_six_months_lapse ?? 0);

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

        $progressStats = DB::table('enrollments as e')
            ->selectRaw('
                (SELECT COUNT(DISTINCT enrollment_id) FROM doctor_documents WHERE enrollment_id IS NOT NULL) as with_documents,
                (SELECT COUNT(DISTINCT enrollment_id) FROM legal_cases WHERE enrollment_id IS NOT NULL) as with_cases,
                (SELECT COUNT(DISTINCT enrollment_id) FROM doctor_documents 
                    WHERE enrollment_id IS NOT NULL AND (LOWER(COALESCE(document_type, "")) LIKE "%photo%" 
                    OR LOWER(COALESCE(document_title, "")) LIKE "%photo%")) as with_photo
            ')
            ->first();

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

        $planStats = (clone $productionDoctors)
            ->selectRaw('
                SUM(CASE WHEN plan = 1 THEN 1 ELSE 0 END) as normal_count,
                SUM(CASE WHEN plan = 2 THEN 1 ELSE 0 END) as high_count,
                SUM(CASE WHEN plan = 3 THEN 1 ELSE 0 END) as combo_count
            ')
            ->first();

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

        $recentStats = (clone $productionDoctors)
            ->selectRaw('
                COUNT(CASE WHEN created_at >= ? THEN 1 END) as last_six_months_enrollment,
                COALESCE(SUM(COALESCE(NULLIF(total_amount, 0), COALESCE(payment_amount, 0) + COALESCE(service_amount, 0))), 0) as all_time_payments
            ', [$sixMonthsAgo->toDateTimeString()])
            ->first();

        $lastSixMonthsEnrollment = (int) ($recentStats->last_six_months_enrollment ?? 0);

        $lastSixMonthsRenew = (int) PolicyReceipt::query()
            ->where(function ($query) use ($sixMonthsAgo) {
                $query->where('receive_date', '>=', $sixMonthsAgo)
                    ->orWhere(function ($fallback) use ($sixMonthsAgo) {
                        $fallback->whereNull('receive_date')
                            ->where('created_at', '>=', $sixMonthsAgo);
                    });
            })
            ->count();

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

        $renewalYearStats = DB::table('policy_receipts')
            ->selectRaw('
                SUM(CASE WHEN YEAR(COALESCE(receive_date, created_at)) = ? THEN 1 ELSE 0 END) as current_renew,
                SUM(CASE WHEN YEAR(COALESCE(receive_date, created_at)) = ? THEN 1 ELSE 0 END) as previous_renew
            ', [$currentYear, $previousYear])
            ->first();

        $yearComparison = [
            'previous_enrollment' => (int) ($yearStats->previous_enrollment ?? 0),
            'current_enrollment' => (int) ($yearStats->current_enrollment ?? 0),
            'previous_renew' => (int) ($renewalYearStats->previous_renew ?? 0),
            'current_renew' => (int) ($renewalYearStats->current_renew ?? 0),
        ];

        $payments = [
            'this_year' => (float) ($yearStats->this_year_payments ?? 0),
            'previous_year' => (float) ($yearStats->previous_year_payments ?? 0),
            'all_time' => (float) ($yearStats->all_time_payments ?? 0),
        ];

        $latest_doctors = Enrollment::query()
            ->productionReady()
            ->select('id', 'doctor_name', 'created_at')
            ->whereNotNull('doctor_name')
            ->where('doctor_name', '!=', 'Draft enrollment')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $livePipeline = $this->buildLivePipelineStats($user, $isEmployeeDashboard);

        $viewData = array_merge(compact(
            'stats',
            'progress',
            'plans',
            'payments',
            'latest_doctors',
            'lastSixMonthsEnrollment',
            'lastSixMonthsRenew',
            'lastSixMonthsLapse',
            'yearComparison',
            'isEmployeeDashboard',
        ), $livePipeline);

        Cache::put($cacheKey, array_diff_key($viewData, array_flip([
            'workflowSummary',
            'crmCounters',
            'incompleteEnrollments',
            'latest_pipeline_doctors',
            'employeeDashboardStats',
            'employeeRecentEnrollments',
        ])), self::DASHBOARD_CACHE_TTL);

        return view('admin.dashboard', $viewData);
    }

    /**
     * Live CRM / workflow stats (never long-cached).
     *
     * @return array<string, mixed>
     */
    private function buildLivePipelineStats($user, bool $isEmployeeDashboard): array
    {
        $pipelineBase = $this->recordAccess->applyOwnedScope(Enrollment::query(), $user);

        $crmCounters = [
            'new_enrollments' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeNewEntries($q))->count(),
            'pending_approvals' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopePendingAdminGate($q))->count(),
            'incomplete_drafts' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeIncompletePipeline($q))->count(),
            'completed_enrollments' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeCompletedPipeline($q))->count(),
            'rejected_cases' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeRejectedCases($q))->count(),
            'returned_for_correction' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeReturnedForCorrection($q))->count(),
            'on_hold' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeOnHold($q))->count(),
        ];

        $workflowSummary = [
            'draft' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeDraftOnly($q))->count(),
            'in_progress' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeOnboardingInProgress($q))->count(),
            'pending_approval' => $crmCounters['pending_approvals'],
            'approved' => (clone $pipelineBase)->tap(fn ($q) => EnrollmentWorkflow::scopeApprovedNotCompleted($q))->count(),
            'rejected' => $crmCounters['rejected_cases'],
            'returned' => $crmCounters['returned_for_correction'],
            'on_hold' => $crmCounters['on_hold'],
            'completed' => $crmCounters['completed_enrollments'],
            'incomplete' => $crmCounters['incomplete_drafts'],
        ];

        $incompleteEnrollments = (clone $pipelineBase)
            ->with(['creator', 'approver'])
            ->tap(fn ($q) => EnrollmentWorkflow::scopeIncompletePipeline($q))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        $latest_pipeline_doctors = (clone $pipelineBase)
            ->with(['creator'])
            ->where('doctor_name', '!=', 'Draft enrollment')
            ->whereNotNull('doctor_name')
            ->orderByDesc('submitted_at')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get(['id', 'doctor_name', 'customer_id_no', 'status', 'workflow_status', 'submitted_at', 'created_at', 'created_by']);

        $employeeDashboardStats = null;
        $employeeRecentEnrollments = null;

        if ($isEmployeeDashboard) {
            $ownedBase = $this->recordAccess->applyOwnedScope(Enrollment::query(), $user);
            $employeeDashboardStats = [
                'draft' => (clone $ownedBase)->tap(fn ($q) => EnrollmentWorkflow::scopeDraftOnly($q))->count(),
                'pending' => (clone $ownedBase)->tap(fn ($q) => EnrollmentWorkflow::scopePendingAdminGate($q))->count(),
                'approved' => (clone $ownedBase)->where('status', 'approved')->count(),
                'rejected' => (clone $ownedBase)->tap(fn ($q) => EnrollmentWorkflow::scopeRejectedCases($q))->count(),
                'incomplete' => (clone $ownedBase)->tap(fn ($q) => EnrollmentWorkflow::scopeIncompletePipeline($q))->count(),
            ];
            $employeeRecentEnrollments = (clone $ownedBase)
                ->orderByDesc('last_activity_at')
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get(['id', 'doctor_name', 'customer_id_no', 'status', 'workflow_status', 'current_step', 'rejection_reason', 'last_activity_at', 'submitted_at']);
        }

        return compact(
            'workflowSummary',
            'crmCounters',
            'incompleteEnrollments',
            'latest_pipeline_doctors',
            'employeeDashboardStats',
            'employeeRecentEnrollments'
        );
    }
}
