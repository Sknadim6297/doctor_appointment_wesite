<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\LegalCase;
use App\Models\Specialization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaseController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $specializationId = $request->input('specialization_id');
        $plan = $request->input('plan');
        $stage = $request->input('stage');

        $cases = LegalCase::query()
            ->with(['doctor:id,specialization_id,plan', 'creator:id,name,email'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('doctor_name', 'like', '%' . $search . '%')
                        ->orWhere('doctor_phone', 'like', '%' . $search . '%')
                        ->orWhere('doctor_mail', 'like', '%' . $search . '%')
                        ->orWhere('case_number', 'like', '%' . $search . '%')
                        ->orWhere('complainant_name', 'like', '%' . $search . '%');
                });
            })
            ->when($specializationId !== null && $specializationId !== '', function ($query) use ($specializationId) {
                $query->whereHas('doctor', function ($doctorQuery) use ($specializationId) {
                    $doctorQuery->where('specialization_id', $specializationId);
                });
            })
            ->when($plan !== null && $plan !== '', function ($query) use ($plan) {
                $query->whereHas('doctor', function ($doctorQuery) use ($plan) {
                    $doctorQuery->where('plan', $plan);
                });
            })
            ->when($stage !== null && $stage !== '', fn ($query) => $query->where('stage', 'like', '%' . $stage . '%'))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->appends($request->query());

        $specializations = Specialization::query()->orderBy('name')->get(['id', 'name']);
        $doctors = Enrollment::query()->select('id', 'doctor_name', 'mobile1', 'doctor_email', 'customer_id_no')->orderBy('doctor_name')->get();
        $plans = [
            1 => 'Normal',
            2 => 'High Risk',
            3 => 'Combo',
        ];

        return view('admin.cases.index', compact('cases', 'search', 'specializations', 'plans', 'stage', 'doctors'));
    }

    public function showJson(LegalCase $legalCase): JsonResponse
    {
        return response()->json([
            'success' => true,
            'case' => $legalCase,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCase($request);

        $doctor = !empty($validated['doctor_name_add']) ? Enrollment::find($validated['doctor_name_add']) : null;

        $legalCase = LegalCase::create([
            'enrollment_id' => $doctor?->id,
            'doctor_name' => $doctor?->doctor_name ?? $validated['doctor_name_add_text'],
            'doctor_phone' => $validated['doctor_phone'] ?? $doctor?->mobile1,
            'doctor_mail' => $validated['doctor_mail'] ?? $doctor?->doctor_email,
            'case_number' => $validated['case_number'] ?? null,
            'court_year' => $validated['court_year'] ?: null,
            'court' => $validated['court'] ?? null,
            'court_address' => $validated['court_address'] ?? null,
            'case_cat' => $validated['case_cat'] ?? null,
            'stage' => $validated['stage'] ?? null,
            'case_details' => $validated['case_details'] ?? null,
            'advocat_mobile' => $validated['advocat_mobile'] ?? null,
            'advocat_mail' => $validated['advocat_mail'] ?? null,
            'appear_date' => $validated['appear_date'] ?: null,
            'next_date' => $validated['next_date'] ?: null,
            'filling_date' => $validated['filling_date'] ?: null,
            'complainant_name' => $validated['complainant_name'] ?? null,
            'mail_link' => $validated['mail_link'] ?? null,
            'direct_payment' => !empty($validated['direct_payment']),
            'money_reciept_no' => $validated['money_reciept_no'] ?? null,
            'payment_cheque_no' => $validated['payment_cheque_no'] ?? null,
            'direct_payment_bank' => $validated['direct_payment_bank'] ?? null,
            'bank_branch' => $validated['bank_branch'] ?? null,
            'direct_payment_amount' => $validated['direct_payment_amount'] ?: null,
            'check_date' => $validated['check_date'] ?: null,
            'case_link' => $validated['case_link'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.cases')->with('success', 'Case submitted successfully.');
    }

    public function update(Request $request, LegalCase $legalCase)
    {
        $validated = $this->validateCase($request);

        $doctor = !empty($validated['doctor_name_add']) ? Enrollment::find($validated['doctor_name_add']) : null;

        $legalCase->update([
            'enrollment_id' => $doctor?->id,
            'doctor_name' => $doctor?->doctor_name ?? $validated['doctor_name_add_text'],
            'doctor_phone' => $validated['doctor_phone'] ?? $doctor?->mobile1,
            'doctor_mail' => $validated['doctor_mail'] ?? $doctor?->doctor_email,
            'case_number' => $validated['case_number'] ?? null,
            'court_year' => $validated['court_year'] ?: null,
            'court' => $validated['court'] ?? null,
            'court_address' => $validated['court_address'] ?? null,
            'case_cat' => $validated['case_cat'] ?? null,
            'stage' => $validated['stage'] ?? null,
            'case_details' => $validated['case_details'] ?? null,
            'advocat_mobile' => $validated['advocat_mobile'] ?? null,
            'advocat_mail' => $validated['advocat_mail'] ?? null,
            'appear_date' => $validated['appear_date'] ?: null,
            'next_date' => $validated['next_date'] ?: null,
            'filling_date' => $validated['filling_date'] ?: null,
            'complainant_name' => $validated['complainant_name'] ?? null,
            'mail_link' => $validated['mail_link'] ?? null,
            'direct_payment' => !empty($validated['direct_payment']),
            'money_reciept_no' => $validated['money_reciept_no'] ?? null,
            'payment_cheque_no' => $validated['payment_cheque_no'] ?? null,
            'direct_payment_bank' => $validated['direct_payment_bank'] ?? null,
            'bank_branch' => $validated['bank_branch'] ?? null,
            'direct_payment_amount' => $validated['direct_payment_amount'] ?: null,
            'check_date' => $validated['check_date'] ?: null,
            'case_link' => $validated['case_link'] ?? null,
        ]);

        return redirect()->route('admin.cases')->with('success', 'Case updated successfully.');
    }

    public function destroy(LegalCase $legalCase)
    {
        $legalCase->delete();

        return redirect()->route('admin.cases')->with('success', 'Case deleted successfully.');
    }

    private function validateCase(Request $request): array
    {
        return $request->validate([
            'doctor_name_add' => 'nullable|integer|exists:enrollments,id',
            'doctor_name_add_text' => 'nullable|string|max:255',
            'doctor_phone' => 'nullable|string|max:30',
            'doctor_mail' => 'nullable|email|max:255',
            'case_number' => 'nullable|string|max:100',
            'court_year' => 'nullable|integer|min:1900|max:2100',
            'court' => 'nullable|string|max:50',
            'court_address' => 'nullable|string',
            'case_cat' => 'nullable|string|max:50',
            'stage' => 'nullable|string',
            'case_details' => 'nullable|string',
            'advocat_mobile' => 'nullable|string|max:30',
            'advocat_mail' => 'nullable|email|max:255',
            'appear_date' => 'nullable|date',
            'next_date' => 'nullable|date',
            'filling_date' => 'nullable|date',
            'complainant_name' => 'nullable|string|max:255',
            'mail_link' => 'nullable|string|max:255',
            'direct_payment' => 'nullable|in:direct_payment',
            'money_reciept_no' => 'nullable|string|max:100',
            'payment_cheque_no' => 'nullable|string|max:100',
            'direct_payment_bank' => 'nullable|string|max:200',
            'bank_branch' => 'nullable|string|max:200',
            'direct_payment_amount' => 'nullable|numeric|min:0',
            'check_date' => 'nullable|date',
            'case_link' => 'nullable|string|max:255',
        ]);
    }
}