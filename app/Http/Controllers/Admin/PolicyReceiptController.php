<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Services\DoctorDocumentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        return view('admin.policy_receipt.index', compact('policies', 'doctors'));
    }

    public function doctors(Request $request)
    {
        $searchYear = (int) $request->query('search_year', 0);
        $searchText = $request->query('search');

        $policies = PolicyReceipt::query()
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
            ->when($searchYear > 0, function ($q) use ($searchYear) {
                $q->where(function ($w) use ($searchYear) {
                    $w->whereYear('receive_date', $searchYear)
                      ->orWhere('policy_no', 'like', '%(' . $searchYear . '%');
                });
            })
            ->when($searchText, function ($q) use ($searchText) {
                $q->where(function ($w) use ($searchText) {
                    $w->where('doctor_name', 'like', '%' . $searchText . '%')
                      ->orWhere('policy_no', 'like', '%' . $searchText . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $years = range((int) date('Y') + 10, 2006);

        return view('admin.policy_receipt.doctors', compact('policies', 'years', 'searchYear', 'searchText'));
    }

    public function create()
    {
        $doctorId = (int) request()->query('doctor', 0);

        $doctors = Enrollment::query()
            ->productionReady()
            ->select('id', 'doctor_name', 'money_rc_no', 'customer_id_no')
            ->orderBy('doctor_name')
            ->get();

        $selectedDoctor = $doctorId > 0 ? Enrollment::query()->productionReady()->find($doctorId) : null;
        $submitRoute = request()->routeIs('admin.policy-receipt.legacy-create') && $doctorId > 0
            ? route('admin.policy-receipt.legacy-store', $doctorId)
            : route('admin.policy-receipt.store');

        return view('admin.policy_receipt.create', compact('doctors', 'selectedDoctor', 'submitRoute'));
    }

    public function createForDoctor($doctorId)
    {
        request()->merge(['doctor' => $doctorId]);

        return $this->create();
    }

    public function storeForEnrollment(Request $request, Enrollment $enrollment)
    {
        $policy = $this->persistPolicyReceipt($request, $enrollment, PolicyReceipt::STATUS_DRAFT);

        session()->flash('success', 'Policy received entry added.');

        // Continue workflow to Step 4 after Step 3 policy receipt submission.
        return redirect()->route('admin.enrollment.step4', $enrollment);
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
        $doctors = Enrollment::query()->productionReady()->select('id', 'doctor_name', 'money_rc_no')->orderBy('doctor_name')->get();
        return view('admin.policy_receipt.edit', compact('policy', 'doctors'));
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
        $data = $request->validate([
            'policy_no' => 'nullable|string|max:255',
            'last_renewed_date' => 'nullable|date_format:d/m/Y',
            'rcv_date' => 'nullable|date_format:d/m/Y',
            'policy_start_date' => 'nullable|date_format:d/m/Y',
            'policy_end_date' => 'nullable|date_format:d/m/Y',
            'policy_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:10240',
        ]);

        $lastRenewed = !empty($data['last_renewed_date'])
            ? Carbon::createFromFormat('d/m/Y', $data['last_renewed_date'])->format('Y-m-d')
            : null;

        $receiveDate = !empty($data['rcv_date'])
            ? Carbon::createFromFormat('d/m/Y', $data['rcv_date'])->format('Y-m-d')
            : null;

        $policyStartDate = !empty($data['policy_start_date'])
            ? Carbon::createFromFormat('d/m/Y', $data['policy_start_date'])->format('Y-m-d')
            : null;

        $policyEndDate = !empty($data['policy_end_date'])
            ? Carbon::createFromFormat('d/m/Y', $data['policy_end_date'])->format('Y-m-d')
            : null;

        $filePath = null;
        if ($request->hasFile('policy_file')) {
            $filePath = $request->file('policy_file')->store('policy_files', 'public');
        }

        $policy = PolicyReceipt::create([
            'policy_no' => $data['policy_no'] ?? null,
            'enrollment_id' => $enrollment->id,
            'doctor_name' => $enrollment->doctor_name,
            'last_renewed_date' => $lastRenewed,
            'receive_date' => $receiveDate,
            'policy_start_date' => $policyStartDate,
            'policy_end_date' => $policyEndDate,
            'policy_file' => $filePath,
            'workflow_status' => $workflowStatus,
        ]);

        // Synchronize important dates back to the enrollment so main listing shows up-to-date data.
        // Use receive date first, then policy start date as fallback for `policy_date`.
        try {
            $enrollment->policy_date = $receiveDate ?? $policyStartDate ?? $enrollment->policy_date;
            if (!empty($lastRenewed)) {
                $enrollment->last_renewal_date = $lastRenewed;
            }
            $enrollment->save();
        } catch (\Exception $e) {
            // Do not break creation flow if enrollment update fails; log or ignore silently.
        }

        if ($filePath) {
            $this->doctorDocumentService->syncPolicyReceipt($policy);
        }

        return $policy;
    }
}
