<?php

namespace App\Http\Controllers\Admin;

use App\Models\DoctorDocument;
use App\Models\DoctorPost;
use App\Models\Enrollment;
use App\Models\LegalCase;
use App\Models\PolicyReceipt;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $currentYear = (int) $now->year;
        $previousYear = $currentYear - 1;

        $doctorCount = (int) Enrollment::query()->count();
        $moneyReceiptCount = (int) Enrollment::query()
            ->whereNotNull('money_rc_no')
            ->where('money_rc_no', '!=', '')
            ->count();
        $doctorCaseCount = (int) LegalCase::query()->count();
        $lapseCount = (int) Enrollment::query()
            ->whereRaw('DATE_ADD(created_at, INTERVAL 1 YEAR) < ?', [$now->toDateTimeString()])
            ->count();
        $premiumAmountCount = $moneyReceiptCount;
        $doctorPostCount = (int) DoctorPost::query()->count();

        $stats = [
            'enrollment_doctors' => $doctorCount,
            'money_receipts' => $moneyReceiptCount,
            'doctor_cases' => $doctorCaseCount,
            'lapse_list' => $lapseCount,
            'premium_amount' => $premiumAmountCount,
            'doctor_posts' => $doctorPostCount,
        ];

        $withDocumentsCount = (int) DoctorDocument::query()
            ->whereNotNull('enrollment_id')
            ->distinct('enrollment_id')
            ->count('enrollment_id');

        $withCasesCount = (int) LegalCase::query()
            ->whereNotNull('enrollment_id')
            ->distinct('enrollment_id')
            ->count('enrollment_id');

        $withPremiumCount = $premiumAmountCount;

        $withPhotoCount = (int) DoctorDocument::query()
            ->whereNotNull('enrollment_id')
            ->where(function ($query) {
                $query->whereRaw("LOWER(COALESCE(document_type, '')) like ?", ['%photo%'])
                    ->orWhereRaw("LOWER(COALESCE(document_title, '')) like ?", ['%photo%']);
            })
            ->distinct('enrollment_id')
            ->count('enrollment_id');

        $progress = [
            'with_documents' => ['count' => $withDocumentsCount, 'total' => $doctorCount],
            'with_cases' => ['count' => $withCasesCount, 'total' => $doctorCount],
            'with_premium' => ['count' => $withPremiumCount, 'total' => $doctorCount],
            'with_photo' => ['count' => $withPhotoCount, 'total' => $doctorCount],
            'renew_expired' => ['count' => $lapseCount, 'total' => $doctorCount],
        ];

        $normalPlanCount = (int) Enrollment::query()->where('plan', 1)->count();
        $highPlanCount = (int) Enrollment::query()->where('plan', 2)->count();
        $comboPlanCount = (int) Enrollment::query()->where('plan', 3)->count();

        $plans = [
            'normal' => $normalPlanCount,
            'high' => $highPlanCount,
            'combo' => $comboPlanCount,
        ];

        $lastSixMonthsEnrollment = (int) Enrollment::query()
            ->where('created_at', '>=', $now->copy()->subMonths(6)->startOfDay())
            ->count();

        $lastSixMonthsRenew = (int) PolicyReceipt::query()
            ->where(function ($query) use ($now) {
                $query->where('receive_date', '>=', $now->copy()->subMonths(6)->startOfDay())
                    ->orWhere(function ($fallback) use ($now) {
                        $fallback->whereNull('receive_date')
                            ->where('created_at', '>=', $now->copy()->subMonths(6)->startOfDay());
                    });
            })
            ->count();

        $lastSixMonthsLapse = (int) Enrollment::query()
            ->whereRaw('DATE_ADD(created_at, INTERVAL 1 YEAR) < ?', [$now->toDateTimeString()])
            ->whereRaw('DATE_ADD(created_at, INTERVAL 1 YEAR) >= ?', [$now->copy()->subMonths(6)->toDateTimeString()])
            ->count();

        $yearComparison = [
            'previous_enrollment' => (int) Enrollment::query()->whereYear('created_at', $previousYear)->count(),
            'current_enrollment' => (int) Enrollment::query()->whereYear('created_at', $currentYear)->count(),
            'previous_renew' => (int) PolicyReceipt::query()
                ->where(function ($query) use ($previousYear) {
                    $query->whereYear('receive_date', $previousYear)
                        ->orWhere(function ($fallback) use ($previousYear) {
                            $fallback->whereNull('receive_date')
                                ->whereYear('created_at', $previousYear);
                        });
                })
                ->count(),
            'current_renew' => (int) PolicyReceipt::query()
                ->where(function ($query) use ($currentYear) {
                    $query->whereYear('receive_date', $currentYear)
                        ->orWhere(function ($fallback) use ($currentYear) {
                            $fallback->whereNull('receive_date')
                                ->whereYear('created_at', $currentYear);
                        });
                })
                ->count(),
        ];

        $sumExpr = DB::raw('COALESCE(NULLIF(total_amount, 0), COALESCE(payment_amount, 0) + COALESCE(service_amount, 0))');

        $payments = [
            'this_year' => (float) Enrollment::query()->whereYear('created_at', $currentYear)->sum($sumExpr),
            'previous_year' => (float) Enrollment::query()->whereYear('created_at', $previousYear)->sum($sumExpr),
            'all_time' => (float) Enrollment::query()->sum($sumExpr),
        ];

        $latest_doctors = Enrollment::query()
            ->select('id', 'doctor_name', 'created_at')
            ->whereNotNull('doctor_name')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'progress',
            'plans',
            'payments',
            'latest_doctors',
            'lastSixMonthsEnrollment',
            'lastSixMonthsRenew',
            'lastSixMonthsLapse',
            'yearComparison'
        ));
    }
}

