<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

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
            ->orderByDesc('created_at');

        if (in_array($selectedMonth, $monthNames, true)) {
            $monthNumber = array_search($selectedMonth, $monthNames, true) + 1;
            $query->whereMonth('created_at', $monthNumber);
        }

        if (is_numeric($selectedYear) && (int) $selectedYear >= 2000 && (int) $selectedYear <= 2100) {
            $query->whereYear('created_at', (int) $selectedYear);
        }

        $callSheets = $query->paginate(25)->appends($request->query());
        $years = range(2000, ((int) now()->format('Y')) + 10);

        return view('admin.marketing.call-sheet.index', [
            'callSheets' => $callSheets,
            'months' => $monthNames,
            'years' => $years,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
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
