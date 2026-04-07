<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PolicyReceipt;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PolicyReceiptController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $policies = PolicyReceipt::with('enrollment')
            ->when($search, function ($q) use ($search) {
                $q->where('policy_no', 'like', "%{$search}%")
                  ->orWhere('doctor_name', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $doctors = Enrollment::query()
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
            ->paginate(15)
            ->withQueryString();

        $years = range((int) date('Y') + 10, 2006);

        return view('admin.policy_receipt.doctors', compact('policies', 'years', 'searchYear', 'searchText'));
    }

    public function create()
    {
        $doctors = Enrollment::query()
            ->select('id', 'doctor_name', 'money_rc_no', 'customer_id_no')
            ->orderBy('doctor_name')
            ->get();

        return view('admin.policy_receipt.create', compact('doctors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'policy_no' => 'nullable|string|max:255',
            'doctor' => 'nullable|integer|exists:enrollments,id',
            'last_renewed_date' => 'nullable|date_format:d/m/Y',
            'rcv_date' => 'nullable|date_format:d/m/Y',
            'policy_file' => 'nullable|file|mimes:pdf,jpeg,png,jpg,doc,docx|max:10240',
        ]);

        $enrollment = null;
        $doctorName = null;
        if (!empty($data['doctor'])) {
            $enrollment = Enrollment::find($data['doctor']);
            $doctorName = $enrollment?->doctor_name;
        }

        $lastRenewed = null;
        if (!empty($data['last_renewed_date'])) {
            try {
                $lastRenewed = Carbon::createFromFormat('d/m/Y', $data['last_renewed_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $lastRenewed = null;
            }
        }

        $receiveDate = null;
        if (!empty($data['rcv_date'])) {
            try {
                $receiveDate = Carbon::createFromFormat('d/m/Y', $data['rcv_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $receiveDate = null;
            }
        }

        $filePath = null;
        if ($request->hasFile('policy_file')) {
            $filePath = $request->file('policy_file')->store('policy_files', 'public');
        }

        PolicyReceipt::create([
            'policy_no' => $data['policy_no'] ?? null,
            'enrollment_id' => $enrollment?->id,
            'doctor_name' => $doctorName,
            'last_renewed_date' => $lastRenewed,
            'receive_date' => $receiveDate,
            'policy_file' => $filePath,
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
        $doctors = Enrollment::select('id', 'doctor_name', 'money_rc_no')->orderBy('doctor_name')->get();
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
            $enrollment = Enrollment::find($data['doctor']);
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
}
