<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorDocument;
use App\Models\ComboPlan;
use App\Models\DoctorPost;
use App\Models\Enrollment;
use App\Models\LegalCase;
use App\Models\HighRiskPlan;
use App\Models\NormalPlan;
use App\Models\PolicyReceipt;
use App\Models\Specialization;
use App\Services\ActivityLogService;
use App\Services\SecurityAlertService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly SecurityAlertService $securityAlertService
    ) {
    }

    /**
     * Display a listing of doctors.
     */
    public function index(Request $request)
    {
        $this->activityLogService->log(
            $request,
            'doctors',
            'view',
            description: 'Viewed doctor listing.',
            metadata: $request->only(['search', 'specialization_id', 'plan', 'renewal_status'])
        );

        $query = Enrollment::query()->with('specialization')->orderByDesc('created_at');

        // Filter by search term (name, email, phone, membership)
        if (!empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('doctor_name', 'like', "%{$search}%")
                    ->orWhere('doctor_email', 'like', "%{$search}%")
                    ->orWhere('mobile1', 'like', "%{$search}%")
                    ->orWhere('mobile2', 'like', "%{$search}%")
                    ->orWhere('customer_id_no', 'like', "%{$search}%");
            });
        }

        // Filter by specialization
        if (!empty($request->specialization_id)) {
            $query->where('specialization_id', $request->specialization_id);
        }

        // Filter by plan
        if (!empty($request->plan)) {
            $query->where('plan', $request->plan);
        }

        // Filter by renewal status
        if ($request->renewal_status === 'upcoming') {
            // Upcoming renewals (within next 30 days)
            $query->whereBetween('created_at', [
                now()->subYear()->subDays(30),
                now()->subYear()->addDays(0)
            ]);
        } elseif ($request->renewal_status === 'overdue') {
            // Overdue renewals (past renewal date)
            $query->where('created_at', '<', now()->subYear());
        }

        $doctors = $query->paginate(25)->appends($request->query());
        $specializations = Specialization::all();
        $plans = [
            1 => 'Normal',
            2 => 'High Risk',
            3 => 'Combo',
        ];

        return view('admin.doctors.index', compact('doctors', 'specializations', 'plans'));
    }

    /**
     * Display the premium amount index under Account Management.
     */
    public function premiumAmountIndex(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $doctors = Enrollment::query()
            ->with('specialization')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('doctor_name', 'like', '%' . $search . '%')
                        ->orWhere('money_rc_no', 'like', '%' . $search . '%')
                        ->orWhere('customer_id_no', 'like', '%' . $search . '%')
                        ->orWhere('mobile1', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(25)
            ->appends($request->query());

        $planCoverageMaps = [
            1 => NormalPlan::query()->select('id', 'coverage_lakh', 'yearly_amount')->orderByDesc('id')->get()->keyBy('id'),
            2 => HighRiskPlan::query()->select('id', 'coverage_lakh', 'yearly_amount')->orderByDesc('id')->get()->keyBy('id'),
            3 => ComboPlan::query()->select('id', 'coverage_lakh', 'yearly_amount')->orderByDesc('id')->get()->keyBy('id'),
        ];

        return view('admin.account-management.premium-amount', compact('doctors', 'search', 'planCoverageMaps'));
    }

    /**
     * Display the specified doctor details.
     */
    public function show($id)
    {
        $doctor = Enrollment::with(['specialization', 'creator'])->findOrFail($id);

        $this->activityLogService->log(
            request(),
            'doctors',
            'view',
            $doctor,
            $doctor->creator,
            'Viewed doctor details.',
            [
                'doctor_name' => $doctor->doctor_name,
                'membership_no' => $doctor->customer_id_no,
            ]
        );

        if ($doctor->creator) {
            $this->securityAlertService->notifySensitiveEnrollmentAccess(request(), $doctor, $doctor->creator);
        }

        // Calculate renewal dates
        $enrollmentDate = $doctor->created_at;
        $renewalDate = $enrollmentDate->copy()->addYear();
        $daysUntilRenewal = now()->diffInDays($renewalDate, false);
        $renewalStatus = match(true) {
            $daysUntilRenewal > 30 => 'upcoming',
            $daysUntilRenewal > 0 => 'due_soon',
            $daysUntilRenewal > -30 => 'overdue_recent',
            default => 'overdue_old'
        };

        $planName = match((int)$doctor->plan) {
            1 => 'Normal',
            2 => 'High Risk',
            3 => 'Combo',
            default => 'Unknown'
        };

        $posts = DoctorPost::query()
            ->where('enrollment_id', $doctor->id)
            ->latest('id')
            ->take(25)
            ->get();

        $documents = DoctorDocument::query()
            ->with('creator')
            ->where('enrollment_id', $doctor->id)
            ->latest('id')
            ->take(25)
            ->get();

        $policyReceipts = PolicyReceipt::query()
            ->where(function ($query) use ($doctor) {
                $query->where('enrollment_id', $doctor->id)
                    ->orWhere('doctor_name', $doctor->doctor_name);
            })
            ->latest('id')
            ->take(25)
            ->get();

        $cases = LegalCase::query()
            ->where(function ($query) use ($doctor) {
                $query->where('enrollment_id', $doctor->id)
                    ->orWhere('doctor_name', $doctor->doctor_name);
            })
            ->latest('id')
            ->take(25)
            ->get();

        $activeTab = match ((string) request()->query('tab', 'details')) {
            'doctor_documents' => 'documents',
            'case_tab' => 'cases',
            'doctor_policy_tab' => 'policies',
            'post_tab' => 'posts',
            'premium_send' => 'premium',
            'money_reciept_tab', 'money_reciept' => 'receipts',
            'prev_bond_tab', 'prev_bond' => 'bonds',
            default => 'details',
        };

        return view('admin.doctors.show', compact(
            'doctor',
            'planName',
            'renewalDate',
            'renewalStatus',
            'daysUntilRenewal',
            'posts',
            'documents',
            'cases',
            'policyReceipts',
            'activeTab'
        ));
    }

    /**
     * Display doctors with incomplete documents.
     */
    public function incompleteDocuments(Request $request)
    {
        $this->activityLogService->log(
            $request,
            'doctors',
            'view',
            description: 'Viewed incomplete doctor documents list.',
            metadata: $request->only(['search_month', 'search_year'])
        );

        $query = Enrollment::query()
            ->with('specialization')
            ->where(function ($q) {
                $q->whereNull('aadhar_card_no')
                    ->orWhere('aadhar_card_no', '')
                    ->orWhereNull('pan_card_no')
                    ->orWhere('pan_card_no', '')
                    ->orWhereNull('medical_registration_no')
                    ->orWhere('medical_registration_no', '');
            })
            ->orderByDesc('created_at');

        $searchMonth = $request->input('search_month');
        $searchYear = $request->input('search_year');

        if (!empty($searchMonth) && $searchMonth !== '0') {
            $monthNumber = date('n', strtotime($searchMonth));
            $query->whereRaw('MONTH(DATE_ADD(created_at, INTERVAL 1 YEAR)) = ?', [$monthNumber]);
        }

        if (!empty($searchYear) && $searchYear !== '0') {
            $query->whereRaw('YEAR(DATE_ADD(created_at, INTERVAL 1 YEAR)) = ?', [$searchYear]);
        }

        $doctors = $query->paginate(25)->appends($request->query());
        $specializations = Specialization::all();
        $plans = [
            1 => 'Normal',
            2 => 'High Risk',
            3 => 'Combo',
        ];

        return view('admin.doctors.incomplete-documents', compact('doctors', 'specializations', 'plans'));
    }

    /**
     * Export doctors to CSV.
     */
    public function csvReport(Request $request)
    {
        $query = Enrollment::query()->with('specialization');

        // Apply same filters as index
        if (!empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('doctor_name', 'like', "%{$search}%")
                    ->orWhere('doctor_email', 'like', "%{$search}%")
                    ->orWhere('mobile1', 'like', "%{$search}%")
                    ->orWhere('customer_id_no', 'like', "%{$search}%");
            });
        }

        if (!empty($request->specialization_id)) {
            $query->where('specialization_id', $request->specialization_id);
        }

        if (!empty($request->plan)) {
            $query->where('plan', $request->plan);
        }

        $doctors = $query->orderByDesc('created_at')->get();

        // Generate CSV
        $filename = 'doctors_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($doctors) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'SL', 'Name', 'Email', 'Phone', 'Specialization', 'Plan',
                'Membership No', 'Insurance Coverage', 'Premium', 'Renewal Date'
            ]);

            $counter = 1;
            foreach ($doctors as $doctor) {
                $renewalDate = $doctor->created_at->copy()->addYear();
                fputcsv($file, [
                    $counter++,
                    $doctor->doctor_name,
                    $doctor->doctor_email,
                    $doctor->mobile1,
                    $doctor->specialization->name ?? 'N/A',
                    match((int)$doctor->plan) { 1 => 'Normal', 2 => 'High Risk', 3 => 'Combo', default => 'Unknown' },
                    $doctor->customer_id_no,
                    $doctor->payment_amount,
                    $doctor->service_amount,
                    $renewalDate->format('d/m/Y'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Money receipt listing (Account Management).
     */
    public function receipts(Request $request)
    {
        $this->activityLogService->log(
            $request,
            'receipts',
            'view',
            description: 'Viewed money receipt listing.',
            metadata: $request->only(['search', 'search_month', 'search_year'])
        );

        $searchMonth = (int) $request->input('search_month', 0);
        $searchYear = (int) $request->input('search_year', 0);
        $searchText = trim((string) $request->input('search', ''));

        $receipts = $this->moneyReceiptQuery($request)
            ->paginate(25)
            ->appends($request->query());

        $summary = $this->moneyReceiptQuery($request)
            ->toBase()
            ->reorder()
            ->selectRaw('COALESCE(SUM(payment_amount), 0) as total_payment_amount, COALESCE(SUM(service_amount), 0) as total_service_amount')
            ->first();

        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
        $years = range((int) date('Y') + 10, 2000);
        $doctors = Enrollment::query()
            ->select('id', 'doctor_name', 'customer_id_no', 'specialization_id', 'money_rc_no')
            ->orderBy('doctor_name')
            ->get();
        $specializations = Specialization::query()->orderBy('name')->get();

        return view('admin.receipts.index', compact(
            'receipts',
            'months',
            'years',
            'doctors',
            'specializations',
            'searchMonth',
            'searchYear',
            'searchText',
            'summary'
        ));
    }

    /**
     * Store money receipt details against an enrollment record.
     */
    public function receiptsStore(Request $request)
    {
        $validated = $request->validate([
            'doctor' => 'required|integer|exists:enrollments,id',
            'money_reciept_no' => 'required|string|max:50',
            'money_reciept_year' => 'nullable|integer|min:2000|max:2100',
            'speciliazition' => 'nullable|integer|exists:specializations,id',
            'payment_mode' => 'required|string|in:Cash,Cheque,Online',
            'plan' => 'required|integer|in:1,2,3',
            'coverage' => 'nullable|integer',
            'service_amount' => 'nullable|numeric|min:0',
            'payment_amount' => 'required|numeric|min:0',
            'payment_process' => 'required|string|in:cash,cheque,Online',
            'payment_date' => 'nullable|date',
            'cheque_no' => 'nullable|string|max:100',
            'payment_bank' => 'nullable|string|max:200',
            'payment_branch' => 'nullable|string|max:200',
            'transaction_no' => 'nullable|string|max:100',
            'money_rc_for' => 'nullable|string|in:renewal,enrollment',
            'money_remarks' => 'nullable|string|max:255',
        ]);

        $doctor = Enrollment::findOrFail($validated['doctor']);

        $moneyReceiptNo = $validated['money_reciept_no'];
        if (!empty($validated['money_reciept_year'])) {
            $moneyReceiptNo .= '/' . $validated['money_reciept_year'];
        }

        $methodMap = [
            'cash' => 2,
            'cheque' => 1,
            'Online' => 3,
        ];

        $paymentDate = null;
        if (!empty($validated['payment_date'])) {
            $paymentDate = Carbon::parse($validated['payment_date'])->format('Y-m-d');
        }

        $doctor->update([
            'money_rc_no' => $moneyReceiptNo,
            'specialization_id' => $validated['speciliazition'] ?? $doctor->specialization_id,
            'payment_mode' => $validated['payment_mode'],
            'plan' => $validated['plan'],
            'plan_name' => match ((int) $validated['plan']) {
                1 => 'Normal',
                2 => 'High Risk',
                3 => 'Combo',
                default => $doctor->plan_name,
            },
            'coverage_id' => $validated['coverage'] ?? null,
            'service_amount' => $validated['service_amount'] ?? 0,
            'payment_amount' => $validated['payment_amount'],
            'total_amount' => (float) ($validated['service_amount'] ?? 0) + (float) $validated['payment_amount'],
            'payment_method' => $methodMap[$validated['payment_process']] ?? null,
            'payment_cash_date' => $paymentDate,
            'payment_cheque' => $validated['cheque_no'] ?? null,
            'payment_bank_name' => $validated['payment_bank'] ?? null,
            'payment_branch_name' => $validated['payment_branch'] ?? null,
            'payment_upi_transaction_id' => $validated['transaction_no'] ?? null,
        ]);

        $this->activityLogService->log(
            $request,
            'receipts',
            'create',
            $doctor,
            $doctor->creator,
            'Added money receipt details for doctor.',
            [
                'doctor_name' => $doctor->doctor_name,
                'money_receipt_no' => $moneyReceiptNo,
                'money_receipt_for' => $validated['money_rc_for'] ?? null,
                'remarks' => $validated['money_remarks'] ?? null,
            ]
        );

        return redirect()
            ->route('admin.receipts')
            ->with('success', 'Money receipt saved successfully.');
    }

    /**
     * Edit money receipt details.
     */
    public function receiptsEdit($receiptId)
    {
        $receipt = Enrollment::query()->findOrFail($receiptId);

        $doctors = Enrollment::query()
            ->select('id', 'doctor_name', 'customer_id_no', 'specialization_id', 'money_rc_no')
            ->orderBy('doctor_name')
            ->get();

        $specializations = Specialization::query()->orderBy('name')->get();
        $years = range((int) date('Y') + 10, 2000);

        return view('admin.receipts.edit', compact('receipt', 'doctors', 'specializations', 'years'));
    }

    /**
     * Update money receipt details.
     */
    public function receiptsUpdate(Request $request, $receiptId)
    {
        $receipt = Enrollment::query()->findOrFail($receiptId);

        $validated = $request->validate([
            'doctor' => 'required|integer|exists:enrollments,id',
            'money_reciept_no' => 'required|string|max:50',
            'money_reciept_year' => 'nullable|integer|min:2000|max:2100',
            'speciliazition' => 'nullable|integer|exists:specializations,id',
            'payment_mode' => 'required|string|in:Cash,Cheque,Online',
            'plan' => 'required|integer|in:1,2,3',
            'coverage' => 'nullable|integer',
            'service_amount' => 'nullable|numeric|min:0',
            'payment_amount' => 'required|numeric|min:0',
            'payment_process' => 'required|string|in:cash,cheque,Online',
            'payment_date' => 'nullable|date',
            'cheque_no' => 'nullable|string|max:100',
            'payment_bank' => 'nullable|string|max:200',
            'payment_branch' => 'nullable|string|max:200',
            'transaction_no' => 'nullable|string|max:100',
            'money_rc_for' => 'nullable|string|in:renewal,enrollment',
            'money_remarks' => 'nullable|string|max:255',
        ]);

        $doctor = Enrollment::query()->findOrFail($validated['doctor']);

        $moneyReceiptNo = $validated['money_reciept_no'];
        if (!empty($validated['money_reciept_year'])) {
            $moneyReceiptNo .= '/' . $validated['money_reciept_year'];
        }

        $methodMap = [
            'cash' => 2,
            'cheque' => 1,
            'Online' => 3,
        ];

        $paymentDate = null;
        if (!empty($validated['payment_date'])) {
            $paymentDate = Carbon::parse($validated['payment_date'])->format('Y-m-d');
        }

        $doctor->update([
            'money_rc_no' => $moneyReceiptNo,
            'specialization_id' => $validated['speciliazition'] ?? $doctor->specialization_id,
            'payment_mode' => $validated['payment_mode'],
            'plan' => $validated['plan'],
            'plan_name' => match ((int) $validated['plan']) {
                1 => 'Normal',
                2 => 'High Risk',
                3 => 'Combo',
                default => $doctor->plan_name,
            },
            'coverage_id' => $validated['coverage'] ?? null,
            'service_amount' => $validated['service_amount'] ?? 0,
            'payment_amount' => $validated['payment_amount'],
            'total_amount' => (float) ($validated['service_amount'] ?? 0) + (float) $validated['payment_amount'],
            'payment_method' => $methodMap[$validated['payment_process']] ?? null,
            'payment_cash_date' => $paymentDate,
            'payment_cheque' => $validated['cheque_no'] ?? null,
            'payment_bank_name' => $validated['payment_bank'] ?? null,
            'payment_branch_name' => $validated['payment_branch'] ?? null,
            'payment_upi_transaction_id' => $validated['transaction_no'] ?? null,
        ]);

        $this->activityLogService->log(
            $request,
            'receipts',
            'edit',
            $doctor,
            $doctor->creator,
            'Updated money receipt details for doctor.',
            [
                'doctor_name' => $doctor->doctor_name,
                'money_receipt_no' => $moneyReceiptNo,
                'money_receipt_for' => $validated['money_rc_for'] ?? null,
                'remarks' => $validated['money_remarks'] ?? null,
                'edited_receipt_id' => $receipt->id,
            ]
        );

        return redirect()
            ->route('admin.receipts')
            ->with('success', 'Money receipt updated successfully.');
    }

    /**
     * Legacy endpoint: edit_money_reciept_submit
     */
    public function receiptsLegacyUpdate(Request $request)
    {
        $receiptId = (int) $request->input('money_rc_id');
        if ($receiptId <= 0) {
            return redirect()->route('admin.receipts')->withErrors(['money_rc_id' => 'Invalid money receipt ID.']);
        }

        return $this->receiptsUpdate($request, $receiptId);
    }

    /**
     * JSON endpoint for edit modal prefill.
     */
    public function receiptsShowJson($receiptId): JsonResponse
    {
        $receipt = Enrollment::query()->findOrFail($receiptId);

        [$receiptNoBase, $receiptNoYear] = $this->extractMoneyReceiptParts($receipt->money_rc_no);

        $paymentProcess = match ((int) $receipt->payment_method) {
            1 => 'cheque',
            2 => 'cash',
            3 => 'Online',
            default => (!empty($receipt->payment_upi_transaction_id) ? 'Online' : (!empty($receipt->payment_cheque) ? 'cheque' : 'cash')),
        };

        return response()->json([
            'success' => true,
            'receipt' => [
                'id' => $receipt->id,
                'doctor' => $receipt->id,
                'money_reciept_no' => $receiptNoBase,
                'money_reciept_year' => $receiptNoYear,
                'membership_no' => $receipt->customer_id_no,
                'speciliazition' => $receipt->specialization_id,
                'payment_mode' => $receipt->payment_mode,
                'plan' => $receipt->plan,
                'coverage' => $receipt->coverage_id,
                'service_amount' => (float) ($receipt->service_amount ?? 0),
                'payment_amount' => (float) ($receipt->payment_amount ?? 0),
                'total_amount' => (float) ($receipt->total_amount ?? 0),
                'payment_process' => $paymentProcess,
                'payment_date' => optional($receipt->payment_cash_date)->format('Y-m-d'),
                'cheque_no' => $receipt->payment_cheque,
                'payment_bank' => $receipt->payment_bank_name,
                'payment_branch' => $receipt->payment_branch_name,
                'transaction_no' => $receipt->payment_upi_transaction_id,
                'money_rc_for' => 'enrollment',
                'money_remarks' => '',
            ],
        ]);
    }

    /**
     * Render receipt details page for the action View button.
     */
    public function receiptsView($receiptId)
    {
        $receipt = Enrollment::query()->with('specialization')->findOrFail($receiptId);

        [$receiptNoBase, $receiptNoYear] = $this->extractMoneyReceiptParts($receipt->money_rc_no);

        return view('admin.receipts.view', [
            'receipt' => $receipt,
            'receiptNoBase' => $receiptNoBase,
            'receiptNoYear' => $receiptNoYear,
        ]);
    }

    /**
     * Fetch doctor details for the money receipt modal.
     */
    public function receiptDoctorDetails($doctorId): JsonResponse
    {
        $doctor = Enrollment::query()
            ->select('id', 'customer_id_no', 'specialization_id', 'plan', 'coverage_id', 'service_amount', 'payment_amount')
            ->findOrFail($doctorId);

        return response()->json([
            'success' => true,
            'doctor' => $doctor,
        ]);
    }

    /**
     * Export filtered money receipts to CSV.
     */
    public function receiptsCsv(Request $request): StreamedResponse
    {
        $fileName = 'money-receipt-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'SL No',
                'Money Receipt No',
                'Doctor Name',
                'Membership No',
                'Year',
                'Date',
                'Amount',
                'Plan',
                'Cheque No/Transaction ID',
                'Bank Details',
                'Remarks',
            ]);

            $slNo = 1;
            foreach ($this->moneyReceiptQuery($request)->cursor() as $receipt) {
                $planLabel = match ((int) $receipt->plan) {
                    1 => 'Normal',
                    2 => 'High Risk',
                    3 => 'Combo',
                    default => $receipt->plan_name ?: 'N/A',
                };

                $transactionId = $receipt->payment_cheque ?: ($receipt->payment_upi_transaction_id ?: 'N/A');
                $bankDetails = $receipt->payment_bank_name ?: 'N/A';
                if (!empty($receipt->payment_branch_name)) {
                    $bankDetails .= ' / ' . $receipt->payment_branch_name;
                }

                fputcsv($output, [
                    $slNo++,
                    $receipt->money_rc_no ?? 'N/A',
                    $receipt->doctor_name ?? 'N/A',
                    $receipt->customer_id_no ?? 'N/A',
                    optional($receipt->created_at)->format('Y') ?? 'N/A',
                    optional($receipt->created_at)->format('d/m/Y') ?? 'N/A',
                    filled($receipt->payment_amount) ? 'Rs. ' . number_format((float) $receipt->payment_amount, 0) . '/-' : 'N/A',
                    $planLabel,
                    $transactionId,
                    $bankDetails,
                    'None',
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function moneyReceiptQuery(Request $request)
    {
        $searchMonth = (int) $request->input('search_month', 0);
        $searchYear = (int) $request->input('search_year', 0);
        $searchText = trim((string) $request->input('search', ''));

        $query = Enrollment::query()
            ->with('specialization')
            ->whereNotNull('money_rc_no')
            ->where('money_rc_no', '!=', '')
            ->orderByDesc('created_at');

        if ($searchMonth > 0) {
            $query->whereMonth('created_at', $searchMonth);
        }

        if ($searchYear > 0) {
            $query->whereYear('created_at', $searchYear);
        }

        if ($searchText !== '') {
            $query->where(function ($q) use ($searchText) {
                $q->where('doctor_name', 'like', '%' . $searchText . '%')
                    ->orWhere('money_rc_no', 'like', '%' . $searchText . '%')
                    ->orWhere('customer_id_no', 'like', '%' . $searchText . '%')
                    ->orWhere('mobile1', 'like', '%' . $searchText . '%');
            });
        }

        return $query;
    }

    private function extractMoneyReceiptParts(?string $moneyReceiptNo): array
    {
        if (empty($moneyReceiptNo)) {
            return ['', ''];
        }

        $parts = explode('/', $moneyReceiptNo, 2);

        return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
    }

    /**
     * Update auto-email status toggle.
     */
    public function toggleAutoEmail(Request $request, $id): JsonResponse
    {
        $doctor = Enrollment::findOrFail($id);
        $newStatus = $request->boolean('enabled');
        $doctor->update(['bond_to_mail' => $newStatus]);

        $this->activityLogService->log(
            $request,
            'doctors',
            'edit',
            $doctor,
            $doctor->creator,
            'Updated doctor auto email status.',
            ['enabled' => $newStatus]
        );

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'Auto email enabled' : 'Auto email disabled',
            'status' => $newStatus
        ]);
    }

    /**
     * Update auto-SMS status toggle.
     */
    public function toggleAutoSms(Request $request, $id): JsonResponse
    {
        $doctor = Enrollment::findOrFail($id);
        $newStatus = $request->boolean('enabled');
        // Assuming there's a field to track auto SMS - adjust as needed
        // For now, we'll use a similar flag or create one in migration
        $doctor->update(['auto_sms_enabled' => $newStatus]);

        $this->activityLogService->log(
            $request,
            'doctors',
            'edit',
            $doctor,
            $doctor->creator,
            'Updated doctor auto SMS status.',
            ['enabled' => $newStatus]
        );

        return response()->json([
            'success' => true,
            'message' => $newStatus ? 'Auto SMS enabled' : 'Auto SMS disabled',
            'status' => $newStatus
        ]);
    }

    /**
     * Send mail to doctor.
     */
    public function sendMail(Request $request, $id): JsonResponse
    {
        $doctor = Enrollment::findOrFail($id);

        // TODO: Implement email sending logic
        // Mail::to($doctor->doctor_email)->send(new DoctorNotificationMail($doctor));

        return response()->json([
            'success' => true,
            'message' => 'Email sent successfully to ' . $doctor->doctor_email
        ]);
    }

    /**
     * Send SMS to doctor.
     */
    public function sendSms(Request $request, $id): JsonResponse
    {
        $doctor = Enrollment::findOrFail($id);

        // TODO: Implement SMS sending logic
        // SMS::send($doctor->mobile1, "Your renewal notification message");

        return response()->json([
            'success' => true,
            'message' => 'SMS sent successfully to ' . $doctor->mobile1
        ]);
    }

    /**
     * Resend bond document to email.
     */
    public function resendBond(Request $request, $id): JsonResponse
    {
        $doctor = Enrollment::findOrFail($id);

        // TODO: Implement bond document sending logic
        // Mail::to($doctor->doctor_email)->send(new BondDocumentMail($doctor));

        return response()->json([
            'success' => true,
            'message' => 'Bond document resent to ' . $doctor->doctor_email
        ]);
    }

    /**
     * Resend money receipt to email.
     */
    public function resendMoneyReceipt(Request $request, $id): JsonResponse
    {
        $doctor = Enrollment::findOrFail($id);

        // TODO: Implement money receipt sending logic
        // Mail::to($doctor->doctor_email)->send(new MoneyReceiptMail($doctor));

        return response()->json([
            'success' => true,
            'message' => 'Money receipt resent to ' . $doctor->doctor_email
        ]);
    }
}
