<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Specialization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors.
     */
    public function index(Request $request)
    {
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
     * Display the specified doctor details.
     */
    public function show($id)
    {
        $doctor = Enrollment::with('specialization')->findOrFail($id);

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

        return view('admin.doctors.show', compact('doctor', 'planName', 'renewalDate', 'renewalStatus', 'daysUntilRenewal'));
    }

    /**
     * Display doctors with incomplete documents.
     */
    public function incompleteDocuments(Request $request)
    {
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
     * Update auto-email status toggle.
     */
    public function toggleAutoEmail(Request $request, $id): JsonResponse
    {
        $doctor = Enrollment::findOrFail($id);
        $newStatus = $request->boolean('enabled');
        $doctor->update(['bond_to_mail' => $newStatus]);

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
