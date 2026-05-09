<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Specialization;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class RenewalController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    /**
     * Show renewal enrollment form for a doctor
     */
    public function show(Enrollment $doctor)
    {
        $specializations = Specialization::all();
        // Provide office-use agent details (owner of the Enrollment Entry menu)
        $officeUseAgent = app(\App\Services\AdminAccessService::class)->sidebarAccessOwnerDetails('sidebar.doctor-management.enrollment-entry');
        $officeUseAgentName = $officeUseAgent['name'] ?? 'Super Admin';
        $officeUseAgentPhone = $officeUseAgent['phone'] ?? '';

        $subAdminName = Auth::check() ? (Auth::user()->name ?? '') : '';

        return view('admin.doctors.renewal-enrollment', [
            'doctor' => $doctor,
            'specializations' => $specializations,
            'officeUseAgentName' => $officeUseAgentName,
            'officeUseAgentPhone' => $officeUseAgentPhone,
            'subAdminName' => $subAdminName,
        ]);
    }

    /**
     * Store renewal enrollment
     */
    public function store(Request $request, Enrollment $doctor)
    {
        // Validate renewal data
        $validated = $request->validate([
            'policy_no' => 'nullable|string',
            'money_rc_no' => 'required|string',
            'agent_name' => 'required|string',
            'agent_phone_no' => 'required|string',
            'renewal_date_rn' => 'required|date',
            'policy_date' => 'required|date',
            'mobile1' => 'required|string',
            'mobile2' => 'nullable|string',
            'doctor_email' => 'required|email',
            'qualification' => 'required|string',
            'dob' => 'required|date',
            'medical_registration_no' => 'required|string',
            'year_of_reg' => 'required|integer',
            'clinic_address' => 'required|string',
            'speciliazition' => 'required|integer',
            'payment_mode' => 'required|string',
            'plan' => 'required|integer',
            'coverage' => 'required|numeric',
            'service_amount' => 'nullable|numeric',
            'payment_amount' => 'required|numeric',
            'total_amount' => 'nullable|numeric',
            'payment_method' => 'required|integer',
            'payment_cheque' => 'nullable|string',
            'payment_bank_name' => 'nullable|string',
            'payment_branch_name' => 'nullable|string',
            'upi_transaction_id' => 'nullable|string',
            'payment_cash_date' => 'required|date',
            'previous_bond' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:2048'
        ]);

        // Track the renewal date and update enrollment
        $renewalData = [
            'money_rc_no' => $validated['money_rc_no'],
            'agent_name' => $validated['agent_name'],
            'agent_phone_no' => $validated['agent_phone_no'],
            'mobile1' => $validated['mobile1'],
            'mobile2' => $validated['mobile2'],
            'doctor_email' => $validated['doctor_email'],
            'qualification' => $validated['qualification'],
            'dob' => $validated['dob'],
            'medical_registration_no' => $validated['medical_registration_no'],
            'year_of_reg' => $validated['year_of_reg'],
            'clinic_address' => $validated['clinic_address'],
            'specialization_id' => $validated['speciliazition'],
            'plan' => $validated['plan'],
            'coverage' => $validated['coverage'],
            'service_amount' => $validated['service_amount'],
            'payment_amount' => $validated['payment_amount'],
            'total_amount' => $validated['total_amount'],
            'payment_method' => $validated['payment_method'],
            'payment_cheque' => $validated['payment_cheque'] ?? null,
            'payment_bank_name' => $validated['payment_bank_name'] ?? null,
            'payment_branch_name' => $validated['payment_branch_name'] ?? null,
            'payment_upi_transaction_id' => $validated['upi_transaction_id'] ?? null,
            'payment_cash_date' => $validated['payment_cash_date'],
            'renewal_date' => $validated['renewal_date_rn'],
            'policy_date' => $validated['policy_date'],
            'last_renewal_date' => now()->format('Y-m-d'), // Track when renewal was submitted
            'payment_mode' => $validated['payment_mode'],
        ];

        // Update doctor with renewal information
        $doctor->update($renewalData);

        // Handle previous bond file upload
        if ($request->hasFile('previous_bond')) {
            $file = $request->file('previous_bond');
            $path = Storage::disk('public')->putFileAs(
                'renewal-bonds',
                $file,
                $doctor->id . '_' . time() . '.' . $file->getClientOriginalExtension()
            );
        }

        // Log the renewal activity
        $this->activityLogService->log(
            $request,
            'doctors',
            'edit',
            description: 'Renewal enrollment submitted for doctor: ' . $doctor->doctor_name,
            metadata: [
                'doctor_id' => $doctor->id,
                'renewal_date' => $validated['renewal_date_rn'],
                'policy_date' => $validated['policy_date']
            ]
        );

        return redirect()->route('admin.doctors.index')->with('success', 'Renewal enrollment submitted successfully');
    }
}
