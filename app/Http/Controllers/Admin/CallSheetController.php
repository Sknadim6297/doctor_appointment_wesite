<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Specialization;
use App\Services\ActivityLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallSheetController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService
    ) {
    }

    /**
     * Display the marketing call sheet list.
     */
    public function index(Request $request)
    {
        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        $selectedMonth = $request->input('search_month');
        $selectedYear = $request->input('search_year');

        $this->activityLogService->log(
            $request,
            'doctors',
            'view',
            description: 'Viewed marketing call sheet listing.',
            metadata: $request->only(['search_month', 'search_year'])
        );

        $query = Enrollment::query()
            ->with('specialization')
            ->where('hide_from_call_sheet', false)
            ->orderByDesc('created_at');

        if (in_array($selectedMonth, $monthNames, true)) {
            $monthNumber = array_search($selectedMonth, $monthNames, true) + 1;
            $query->whereMonth('created_at', $monthNumber);
        }

        if (is_numeric($selectedYear) && (int) $selectedYear >= 2000 && (int) $selectedYear <= 2100) {
            $query->whereYear('created_at', (int) $selectedYear);
        }

        $callSheets = $query->paginate(5)->appends($request->query());
        $years = range(2000, ((int) now()->format('Y')) + 10);
        $specializations = Specialization::query()->orderBy('name')->get(['id', 'name']);
        $specializationMap = $specializations->pluck('name', 'id');

        return view('admin.marketing.call-sheet.index', [
            'callSheets' => $callSheets,
            'months' => $monthNames,
            'years' => $years,
            'specializations' => $specializations,
            'specializationMap' => $specializationMap,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
        ]);
    }

    public function edit(Enrollment $callSheet)
    {
        return response()->json([
            'success' => true,
            'callSheet' => [
                'id' => $callSheet->id,
                'doctor_name' => $callSheet->doctor_name,
                'doctor_email' => $callSheet->doctor_email,
                'mobile1' => $callSheet->mobile1,
                'specialization_id' => $callSheet->specialization_id,
                'specialization_ids' => !empty($callSheet->call_sheet_specialization_ids)
                    ? array_values(array_map('intval', $callSheet->call_sheet_specialization_ids))
                    : array_values(array_filter([(int) $callSheet->specialization_id])),
                'specialization_name' => $callSheet->specialization?->name,
                'pdf_url' => route('admin.call-sheet.pdf', $callSheet),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_name' => 'required|string|max:200',
            'doctor_email' => 'nullable|email|max:200',
            'mobile1' => 'nullable|string|max:20',
            'specialization_id' => 'nullable|integer|exists:specializations,id',
            'specialization_ids' => 'nullable|array',
            'specialization_ids.*' => 'nullable|integer|exists:specializations,id',
        ]);

        $specializationIds = collect($validated['specialization_ids'] ?? [])
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($specializationIds->isEmpty() && !empty($validated['specialization_id'])) {
            $specializationIds = collect([(int) $validated['specialization_id']]);
        }

        $primarySpecializationId = $specializationIds->first() ?: ($validated['specialization_id'] ?? null);

        $callSheet = Enrollment::create([
            'doctor_name' => $validated['doctor_name'],
            'doctor_email' => $validated['doctor_email'] ?? null,
            'mobile1' => $validated['mobile1'] ?? null,
            'specialization_id' => $primarySpecializationId,
            'call_sheet_specialization_ids' => $specializationIds->isEmpty() ? null : $specializationIds->all(),
            'hide_from_call_sheet' => false,
            'created_by' => Auth::id(),
        ]);

        $this->activityLogService->log(
            $request,
            'doctors',
            'create',
            $callSheet,
            Auth::user(),
            'Created a new marketing call sheet entry.',
            [
                'doctor_name' => $validated['doctor_name'],
                'doctor_email' => $validated['doctor_email'] ?? null,
                'mobile1' => $validated['mobile1'] ?? null,
                'specialization_ids' => $specializationIds->all(),
            ]
        );

        return redirect()
            ->route('admin.call-sheet.index', $request->only(['search_month', 'search_year']))
            ->with('success', 'Call sheet added successfully.');
    }

    public function update(Request $request, Enrollment $callSheet)
    {
        $validated = $request->validate([
            'doctor_name' => 'required|string|max:200',
            'doctor_email' => 'nullable|email|max:200',
            'mobile1' => 'nullable|string|max:20',
            'specialization_id' => 'nullable|integer|exists:specializations,id',
            'specialization_ids' => 'nullable|array',
            'specialization_ids.*' => 'nullable|integer|exists:specializations,id',
        ]);

        $specializationIds = collect($validated['specialization_ids'] ?? [])
            ->filter(fn ($id) => !empty($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($specializationIds->isEmpty() && !empty($validated['specialization_id'])) {
            $specializationIds = collect([(int) $validated['specialization_id']]);
        }

        $primarySpecializationId = $specializationIds->first() ?: ($validated['specialization_id'] ?? null);

        $callSheet->update([
            'doctor_name' => $validated['doctor_name'],
            'doctor_email' => $validated['doctor_email'] ?? null,
            'mobile1' => $validated['mobile1'] ?? null,
            'specialization_id' => $primarySpecializationId,
            'call_sheet_specialization_ids' => $specializationIds->isEmpty() ? null : $specializationIds->all(),
        ]);

        $this->activityLogService->log(
            $request,
            'doctors',
            'edit',
            $callSheet,
            Auth::user(),
            'Updated marketing call sheet entry.',
            [
                'doctor_name' => $validated['doctor_name'],
                'doctor_email' => $validated['doctor_email'] ?? null,
                'mobile1' => $validated['mobile1'] ?? null,
                'specialization_ids' => $specializationIds->all(),
            ]
        );

        return redirect()
            ->route('admin.call-sheet.index', $request->only(['search_month', 'search_year']))
            ->with('success', 'Call sheet updated successfully.');
    }

    public function pdf(Enrollment $callSheet)
    {
        $callSheet->load('specialization');

        $pdf = Pdf::loadView('admin.marketing.call-sheet.pdf', [
            'callSheet' => $callSheet,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('call-sheet-' . $callSheet->id . '.pdf');
    }

    public function destroy(Request $request, Enrollment $callSheet)
    {
        $callSheet->update(['hide_from_call_sheet' => true]);

        $this->activityLogService->log(
            $request,
            'doctors',
            'delete',
            $callSheet,
            Auth::user(),
            'Archived marketing call sheet entry.',
            ['call_sheet_id' => $callSheet->id]
        );

        return redirect()
            ->route('admin.call-sheet.index', $request->only(['search_month', 'search_year']))
            ->with('success', 'Call sheet entry removed from the list.');
    }

    public function sms(Enrollment $callSheet)
    {
        $phone = preg_replace('/\D+/', '', (string) $callSheet->mobile1);
        $message = rawurlencode('Hello ' . $callSheet->doctor_name . ', this is a marketing call sheet update from MediForum.');

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'sms_url' => $phone !== '' ? 'sms:' . $phone . '?body=' . $message : null,
        ]);
    }

    /**
     * Export marketing call sheet as CSV.
     */
    public function csv(Request $request)
    {
        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        $selectedMonth = $request->input('search_month');
        $selectedYear = $request->input('search_year');

        $query = Enrollment::query()
            ->with('specialization')
            ->where('hide_from_call_sheet', false)
            ->orderByDesc('created_at');

        if (in_array($selectedMonth, $monthNames, true)) {
            $monthNumber = array_search($selectedMonth, $monthNames, true) + 1;
            $query->whereMonth('created_at', $monthNumber);
        }

        if (is_numeric($selectedYear) && (int) $selectedYear >= 2000 && (int) $selectedYear <= 2100) {
            $query->whereYear('created_at', (int) $selectedYear);
        }

        $rows = $query->get();

        $filename = 'call_sheet_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = static function () use ($rows): void {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['SL No.', 'Name', 'Specialization', 'Email', 'Phone']);

            foreach ($rows as $index => $row) {
                fputcsv($file, [
                    $index + 1,
                    $row->doctor_name,
                    $row->specialization?->name ?? 'N/A',
                    $row->doctor_email,
                    $row->mobile1,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
