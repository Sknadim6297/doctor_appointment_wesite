<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Services\DoctorDocumentService;
use App\Support\EnrollmentWorkflow;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PolicyReceiptController extends Controller
{
    public function __construct(
        private readonly DoctorDocumentService $doctorDocumentService,
    ) {
    }
    public function index(Request $request)
    {
        $search = $request->query('search');

        $policies = PolicyReceipt::with('enrollment')
                ->where(function ($q) {
                $q->where('workflow_status', PolicyReceipt::STATUS_COMPLETED)
                    ->orWhereNull('workflow_status');
            })
                ->where(function ($q) {
                    $q->whereNull('enrollment_id')
                        ->orWhereHas('enrollment', function ($enrollmentQuery) {
                            $enrollmentQuery->productionReady();
                        });
                })
            ->when($search, function ($q) use ($search) {
                $q->where('policy_no', 'like', "%{$search}%")
                  ->orWhere('doctor_name', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $doctors = Enrollment::query()
            ->productionReady()
            ->select('id', 'doctor_name', 'money_rc_no', 'customer_id_no')
            ->orderBy('doctor_name')
            ->get();

        return view('admin.policy_receipt.index', compact('policies'));
    }

    public function doctors(Request $request)
    {
        $searchYear = (int) $request->query('search_year', 0);
        $searchText = $request->query('search');

        $policies = PolicyReceipt::query()
            ->select([
                'policy_receipts.*',
                'enrollments.doctor_money_reciept_no',
                'enrollments.doctor_money_reciept_year',
            ])
            ->leftJoin('enrollments', 'enrollments.id', '=', 'policy_receipts.enrollment_id')
            ->where(function ($q) {
                $q->where('policy_receipts.workflow_status', PolicyReceipt::STATUS_COMPLETED)
                    ->orWhereNull('policy_receipts.workflow_status');
            })
            ->where(function ($q) {
                $q->whereNull('policy_receipts.enrollment_id')
                    ->orWhereHas('enrollment', function ($enrollmentQuery) {
                        $enrollmentQuery->productionReady();
                    });
            })
            ->when($searchYear > 0, function ($q) use ($searchYear) {
                $q->where(function ($w) use ($searchYear) {
                    $w->whereYear('policy_receipts.receive_date', $searchYear)
                        ->orWhere('policy_receipts.policy_no', 'like', '%(' . $searchYear . '%');
                });
            })
            ->when($searchText, function ($q) use ($searchText) {
                $q->where(function ($w) use ($searchText) {
                    $w->where('policy_receipts.doctor_name', 'like', '%' . $searchText . '%')
                        ->orWhere('policy_receipts.policy_no', 'like', '%' . $searchText . '%');
                });
            })
            ->orderByDesc('policy_receipts.id')
            ->paginate(10)
            ->withQueryString();

        $years = range((int) date('Y') + 10, 2006);

        return view('admin.policy_receipt.doctors', compact('policies', 'years', 'searchYear', 'searchText'));
    }

    public function create($doctorId = 0)
    {
        $doctorId = (int) ($doctorId ?: request('doctor', 0));

        $doctors = Enrollment::query()
            ->productionReady()
            ->select('id', 'doctor_name', 'money_rc_no', 'customer_id_no')
            ->orderBy('doctor_name')
            ->get();

        $selectedDoctor = $doctorId > 0 ? Enrollment::query()->productionReady()->find($doctorId) : null;
        $submitRoute = request()->routeIs('admin.policy-receipt.legacy-create') && $doctorId > 0
            ? route('admin.policy-receipt.legacy-store', $doctorId)
            : route('admin.policy-receipt.store');

        return view('admin.policy_receipt.create', compact('doctors', 'selectedDoctor', 'submitRoute', 'doctorId'));
    }

    public function createForDoctor($doctorId)
    {
        request()->merge(['doctor' => $doctorId]);

        return $this->create();
    }

    public function storeForEnrollment(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $enrollment = $enrollment->fresh();

        if ((int) ($enrollment->current_step ?? 1) >= 4) {
            return redirect()
                ->route('admin.enrollment.step4', $enrollment)
                ->with('info', 'Policy receipt already submitted. Continue with post submission.');
        }

        $data = $this->validatedPolicyReceiptInput($request);
        $policyNo = trim((string) ($data['policy_no'] ?? ''));

        if ($policyNo !== '') {
            $duplicateQuery = PolicyReceipt::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('policy_no', $policyNo);

            $existingDraftId = PolicyReceipt::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('workflow_status', PolicyReceipt::STATUS_DRAFT)
                ->orderByDesc('id')
                ->value('id');

            if ($existingDraftId) {
                $duplicateQuery->where('id', '!=', $existingDraftId);
            }

            if ($duplicateQuery->exists()) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Policy already submitted for this enrollment.');
            }
        }

        DB::transaction(function () use ($request, $enrollment, $data, $policyNo): void {
            $this->pruneDuplicateWorkflowDrafts($enrollment);

            $existingDraft = PolicyReceipt::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('workflow_status', PolicyReceipt::STATUS_DRAFT)
                ->orderByDesc('id')
                ->first();

            $this->upsertEnrollmentWorkflowPolicyReceipt($request, $enrollment, $data, $existingDraft);

            $draftData = is_array($enrollment->draft_data) ? $enrollment->draft_data : [];
            $draftData['step3'] = array_merge($draftData['step3'] ?? [], [
                'policy_no' => $policyNo ?: ($data['policy_no'] ?? null),
                'last_renewed_date' => $data['last_renewed_date'] ?? null,
                'policy_start_date' => $data['policy_start_date'] ?? null,
                'policy_end_date' => $data['policy_end_date'] ?? null,
                'rcv_date' => $data['rcv_date'] ?? null,
                'doctor_money_reciept_no' => $request->input('doctor_money_reciept_no'),
                'doctor_money_reciept_year' => $request->input('doctor_money_reciept_year'),
            ]);

            $completedSteps = array_values(array_unique(array_merge(
                (array) ($enrollment->completed_steps ?? []),
                [1, 2, 3]
            )));

            $enrollment->forceFill([
                'current_step' => 4,
                'workflow_status' => EnrollmentWorkflow::IN_PROGRESS,
                'is_step_incomplete' => true,
                'last_activity_at' => now(),
                'completed_steps' => $completedSteps,
                'draft_data' => $draftData,
            ])->save();
        });

        return redirect()
            ->route('admin.enrollment.step4', $enrollment)
            ->with('success', 'Policy receipt submitted successfully.');
    }

    public function storeForDoctor(Request $request, $doctorId)
    {
        $enrollment = Enrollment::query()->productionReady()->findOrFail($doctorId);

        $policy = $this->persistPolicyReceipt($request, $enrollment, PolicyReceipt::STATUS_DRAFT);

        session()->flash('success', 'Policy received entry added.');

        // Continue workflow to Step 4 after Step 3 policy receipt submission.
        return redirect()->route('admin.enrollment.step4', $enrollment);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'policy_no' => 'nullable|string|max:255',
            'doctor' => 'nullable|integer|exists:enrollments,id',
            'last_renewed_date' => 'nullable|date_format:d/m/Y',
            'rcv_date' => 'nullable|date_format:d/m/Y',
            'policy_start_date' => 'nullable|date_format:d/m/Y',
            'policy_end_date' => 'nullable|date_format:d/m/Y',
            'policy_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:10240',
        ]);

        // If a doctor/enrollment was provided, reuse persistPolicyReceipt to keep logic consistent
        if (!empty($data['doctor'])) {
            $enrollment = Enrollment::query()->productionReady()->findOrFail($data['doctor']);
                $this->persistPolicyReceipt($request, $enrollment, PolicyReceipt::STATUS_COMPLETED);
            session()->flash('success', 'Policy received entry added.');
            return redirect()->route('admin.policy-receipt.index');
        }

        // Otherwise create a standalone policy receipt (no enrollment link)
        $lastRenewed = null;
        if (!empty($data['last_renewed_date'])) {
            try { $lastRenewed = Carbon::createFromFormat('d/m/Y', $data['last_renewed_date'])->format('Y-m-d'); } catch (\Exception $e) { $lastRenewed = null; }
        }
        $receiveDate = null;
        if (!empty($data['rcv_date'])) {
            try { $receiveDate = Carbon::createFromFormat('d/m/Y', $data['rcv_date'])->format('Y-m-d'); } catch (\Exception $e) { $receiveDate = null; }
        }
        $policyStartDate = null;
        if (!empty($data['policy_start_date'])) {
            try { $policyStartDate = Carbon::createFromFormat('d/m/Y', $data['policy_start_date'])->format('Y-m-d'); } catch (\Exception $e) { $policyStartDate = null; }
        }
        $policyEndDate = null;
        if (!empty($data['policy_end_date'])) {
            try { $policyEndDate = Carbon::createFromFormat('d/m/Y', $data['policy_end_date'])->format('Y-m-d'); } catch (\Exception $e) { $policyEndDate = null; }
        }

        $filePath = null;
        if ($request->hasFile('policy_file')) {
            $filePath = $request->file('policy_file')->store('policy_files', 'public');
        }

        PolicyReceipt::create([
            'policy_no' => $data['policy_no'] ?? null,
            'enrollment_id' => null,
            'doctor_name' => null,
            'last_renewed_date' => $lastRenewed,
            'receive_date' => $receiveDate,
            'policy_start_date' => $policyStartDate,
            'policy_end_date' => $policyEndDate,
            'policy_file' => $filePath,
            'workflow_status' => PolicyReceipt::STATUS_COMPLETED,
        ]);

        session()->flash('success', 'Policy received entry added.');
        return redirect()->route('admin.policy-receipt.index');
    }

    public function show($id)
    {
        $policy = PolicyReceipt::with('enrollment')->findOrFail($id);
        return view('admin.policy_receipt.show', compact('policy'));
    }

    public function edit($id)
    {
        $policy = PolicyReceipt::findOrFail($id);
        $policy->load('enrollment');
        return view('admin.policy_receipt.edit', compact('policy'));
    }

    /**
     * Legacy edit screen (doctor-only money receipt fields), linked from doctor show.
     */
    public function legacyEdit($id)
    {
        $policy = PolicyReceipt::findOrFail($id);
        $policy->load('enrollment');
        return view('admin.policy_receipt.legacy-edit', [
            'policy' => $policy,
            'doctor_only' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $policy = PolicyReceipt::findOrFail($id);

        $data = $request->validate([
            'policy_no' => 'nullable|string|max:255',
            'doctor' => 'nullable|integer|exists:enrollments,id',
            'last_renewed_date' => 'nullable|date_format:d/m/Y',
            'rcv_date' => 'nullable|date_format:d/m/Y',
            'policy_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:10240',
        ]);

        $enrollment = null;
        $doctorName = $policy->doctor_name;
        if (!empty($data['doctor'])) {
            $enrollment = Enrollment::query()->productionReady()->find($data['doctor']);
            $doctorName = $enrollment?->doctor_name ?? $doctorName;
        }

        try {
            $lastRenewed = $data['last_renewed_date'] ? Carbon::createFromFormat('d/m/Y', $data['last_renewed_date'])->format('Y-m-d') : null;
        } catch (\Exception $e) { $lastRenewed = null; }
        try {
            $receiveDate = $data['rcv_date'] ? Carbon::createFromFormat('d/m/Y', $data['rcv_date'])->format('Y-m-d') : null;
        } catch (\Exception $e) { $receiveDate = null; }

        if ($request->hasFile('policy_file')) {
            if ($policy->policy_file) {
                Storage::disk('public')->delete($policy->policy_file);
            }
            $policy->policy_file = $request->file('policy_file')->store('policy_files', 'public');
        }

        $policy->update([
            'policy_no' => $data['policy_no'] ?? $policy->policy_no,
            'enrollment_id' => $enrollment?->id,
            'doctor_name' => $doctorName,
            'last_renewed_date' => $lastRenewed,
            'receive_date' => $receiveDate,
        ]);

        // If linked to an enrollment, sync key dates back to the enrollment record
        try {
            if ($policy->enrollment) {
                $policy->enrollment->policy_date = $receiveDate ?? $policy->enrollment->policy_date;
                if (!empty($lastRenewed)) {
                    $policy->enrollment->last_renewal_date = $lastRenewed;
                }
                $policy->enrollment->save();
            }
        } catch (\Exception $e) {
            // ignore
        }

        session()->flash('success', 'Policy received entry updated.');
        return redirect()->route('admin.policy-receipt.index');
    }

    public function destroy($id)
    {
        $policy = PolicyReceipt::findOrFail($id);
        if ($policy->policy_file) {
            Storage::disk('public')->delete($policy->policy_file);
        }
        $policy->delete();
        session()->flash('success', 'Policy received entry deleted.');
        return redirect()->route('admin.policy-receipt.index');
    }

    private function persistPolicyReceipt(Request $request, Enrollment $enrollment, string $workflowStatus = PolicyReceipt::STATUS_COMPLETED): PolicyReceipt
    {
        $data = $this->validatedPolicyReceiptInput($request);
        $parsed = $this->parsePolicyReceiptDates($data);

        $filePath = null;
        if ($request->hasFile('policy_file')) {
            $filePath = $request->file('policy_file')->store('policy_files', 'public');
        }

        $policy = PolicyReceipt::create([
            'policy_no' => $data['policy_no'] ?? null,
            'enrollment_id' => $enrollment->id,
            'doctor_name' => $enrollment->doctor_name,
            'last_renewed_date' => $parsed['last_renewed'],
            'receive_date' => $parsed['receive_date'],
            'policy_start_date' => $parsed['policy_start_date'],
            'policy_end_date' => $parsed['policy_end_date'],
            'policy_file' => $filePath,
            'workflow_status' => $workflowStatus,
        ]);

        $this->syncEnrollmentPolicyDates($enrollment, $parsed);
        $this->syncPolicyDocument($policy, $filePath);

        return $policy;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertEnrollmentWorkflowPolicyReceipt(
        Request $request,
        Enrollment $enrollment,
        array $data,
        ?PolicyReceipt $existingDraft = null,
    ): PolicyReceipt {
        $parsed = $this->parsePolicyReceiptDates($data);

        $filePath = $existingDraft?->policy_file;
        if ($request->hasFile('policy_file')) {
            if ($filePath) {
                Storage::disk('public')->delete($filePath);
            }
            $filePath = $request->file('policy_file')->store('policy_files', 'public');
        }

        $attributes = [
            'policy_no' => $data['policy_no'] ?? null,
            'doctor_name' => $enrollment->doctor_name,
            'last_renewed_date' => $parsed['last_renewed'],
            'receive_date' => $parsed['receive_date'],
            'policy_start_date' => $parsed['policy_start_date'],
            'policy_end_date' => $parsed['policy_end_date'],
            'policy_file' => $filePath,
            'workflow_status' => PolicyReceipt::STATUS_DRAFT,
        ];

        if ($existingDraft) {
            $existingDraft->fill($attributes)->save();
            $policy = $existingDraft->fresh();
        } else {
            $policy = PolicyReceipt::create(array_merge($attributes, [
                'enrollment_id' => $enrollment->id,
            ]));
        }

        $this->syncEnrollmentPolicyDates($enrollment, $parsed);
        $this->syncPolicyDocument($policy, $filePath);

        return $policy;
    }

    private function pruneDuplicateWorkflowDrafts(Enrollment $enrollment): void
    {
        $drafts = PolicyReceipt::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('workflow_status', PolicyReceipt::STATUS_DRAFT)
            ->orderByDesc('id')
            ->get();

        foreach ($drafts->slice(1) as $duplicate) {
            if ($duplicate->policy_file) {
                Storage::disk('public')->delete($duplicate->policy_file);
            }
            $duplicate->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * Doctor-only money receipt edit (enrollment detail / doctor profile).
     */
    public function doctorMoneyReceiptEdit(Enrollment $enrollment)
    {
        return view('admin.policy-receipt.legacy-edit', compact('enrollment'));
    }

    public function updateDoctorMoneyReceiptFromEnrollment(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'doctor_money_reciept_no' => 'nullable|string|max:50',
            'doctor_money_reciept_year' => 'nullable|string|max:10',
        ]);
        $enrollment->doctor_money_reciept_no = $data['doctor_money_reciept_no'] ?? null;
        $enrollment->doctor_money_reciept_year = $data['doctor_money_reciept_year'] ?? null;
        $enrollment->save();

        return redirect()
            ->route('admin.doctors.show', $enrollment->id)
            ->with('success', 'Money receipt updated.');
    }

    /**
     * Legacy enrollment update route: only doctor money receipt fields (not policy receipt workflow).
     */
    public function legacyUpdateDoctorMoneyReceipt(Request $request, Enrollment $enrollment)
    {
        $data = $request->validate([
            'doctor_money_reciept_no' => 'nullable|string|max:50',
            'doctor_money_reciept_year' => 'nullable|string|max:10',
        ]);
        $enrollment->doctor_money_reciept_no = $data['doctor_money_reciept_no'] ?? null;
        $enrollment->doctor_money_reciept_year = $data['doctor_money_reciept_year'] ?? null;
        $enrollment->save();

        return redirect()
            ->route('admin.doctors.show', $enrollment->id)
            ->with('success', 'Money receipt updated.');
    }

    /**
     * Doctor profile alias: enrollment id is the doctor record id in this app.
     */
    public function doctorMoneyReceiptEditForDoctor(Enrollment $doctor)
    {
        return $this->doctorMoneyReceiptEdit($doctor);
    }

    private function validatedPolicyReceiptInput(Request $request): array
    {
        return $request->validate([
            'policy_no' => 'nullable|string|max:255',
            'last_renewed_date' => 'nullable|date_format:d/m/Y',
            'rcv_date' => 'nullable|date_format:d/m/Y',
            'policy_start_date' => 'nullable|date_format:d/m/Y',
            'policy_end_date' => 'nullable|date_format:d/m/Y',
            'policy_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:10240',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{last_renewed: ?string, receive_date: ?string, policy_start_date: ?string, policy_end_date: ?string}
     */
    private function parsePolicyReceiptDates(array $data): array
    {
        return [
            'last_renewed' => !empty($data['last_renewed_date'])
                ? Carbon::createFromFormat('d/m/Y', $data['last_renewed_date'])->format('Y-m-d')
                : null,
            'receive_date' => !empty($data['rcv_date'])
                ? Carbon::createFromFormat('d/m/Y', $data['rcv_date'])->format('Y-m-d')
                : null,
            'policy_start_date' => !empty($data['policy_start_date'])
                ? Carbon::createFromFormat('d/m/Y', $data['policy_start_date'])->format('Y-m-d')
                : null,
            'policy_end_date' => !empty($data['policy_end_date'])
                ? Carbon::createFromFormat('d/m/Y', $data['policy_end_date'])->format('Y-m-d')
                : null,
        ];
    }

    /**
     * @param  array{last_renewed: ?string, receive_date: ?string, policy_start_date: ?string, policy_end_date: ?string}  $parsed
     */
    private function syncEnrollmentPolicyDates(Enrollment $enrollment, array $parsed): void
    {
        $enrollment->policy_date = $parsed['receive_date'] ?? $parsed['policy_start_date'] ?? $enrollment->policy_date;
        if (!empty($parsed['last_renewed'])) {
            $enrollment->last_renewal_date = $parsed['last_renewed'];
        }
        $enrollment->save();
    }

    private function syncPolicyDocument(PolicyReceipt $policy, ?string $filePath): void
    {
        if ($filePath) {
            $this->doctorDocumentService->syncPolicyReceipt($policy);
        }
    }
}
