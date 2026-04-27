<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Expense;
use App\Models\SalaryRecord;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfficeExpenseController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = (string) $request->input('search_month', '0');
        $selectedYear = (string) $request->input('search_year', '0');

        $monthMap = $this->monthMap();
        $monthNumber = isset($monthMap[$selectedMonth]) ? $monthMap[$selectedMonth] : null;
        $yearNumber = is_numeric($selectedYear) ? (int) $selectedYear : null;

        $officeExpenseQuery = Expense::query();
        if ($monthNumber) {
            $officeExpenseQuery->whereMonth('expense_date', $monthNumber);
        }
        if ($yearNumber && $yearNumber >= 2000 && $yearNumber <= 2100) {
            $officeExpenseQuery->whereYear('expense_date', $yearNumber);
        }
        $totalOfficeExpense = (float) $officeExpenseQuery->sum('amount');

        $salaryExpenseQuery = SalaryRecord::query();
        if ($monthNumber) {
            $salaryExpenseQuery->where('salary_month', $selectedMonth);
        }
        if ($yearNumber && $yearNumber >= 2000 && $yearNumber <= 2100) {
            $salaryExpenseQuery->where('salary_year', $yearNumber);
        }
        $totalSalaryExpense = (float) $salaryExpenseQuery->sum('net_salary');

        $incomeQuery = Enrollment::query()
            ->whereNotNull('money_rc_no')
            ->where('money_rc_no', '!=', '');

        if ($monthNumber) {
            $incomeQuery->whereMonth('created_at', $monthNumber);
        }
        if ($yearNumber && $yearNumber >= 2000 && $yearNumber <= 2100) {
            $incomeQuery->whereYear('created_at', $yearNumber);
        }

        $incomeSummary = $incomeQuery
            ->toBase()
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_income, COALESCE(SUM(payment_amount), 0) as payment_income, COALESCE(SUM(service_amount), 0) as service_income')
            ->first();

        $totalIncome = (float) ($incomeSummary->total_income ?? 0);
        if ($totalIncome <= 0) {
            $totalIncome = (float) (($incomeSummary->payment_income ?? 0) + ($incomeSummary->service_income ?? 0));
        }

        $totalExpense = $totalOfficeExpense + $totalSalaryExpense;

        return view('admin.account-management.office-expensions', [
            'months' => array_keys($monthMap),
            'years' => range(2000, ((int) date('Y')) + 10),
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'totalOfficeExpense' => $totalOfficeExpense,
            'totalSalaryExpense' => $totalSalaryExpense,
            'totalExpense' => $totalExpense,
            'totalIncome' => $totalIncome,
        ]);
    }

    public function csvReport(Request $request): StreamedResponse
    {
        $payload = $this->buildSummary($request);

        $fileName = 'office-expensions-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($payload) {
            $output = fopen('php://output', 'w');
            fputcsv($output, [
                'Month',
                'Year',
                'Total office expense',
                'Total salary expense',
                'Total expense',
                'Total income',
            ]);

            fputcsv($output, [
                $payload['selectedMonth'] !== '0' ? $payload['selectedMonth'] : 'All',
                $payload['selectedYear'] !== '0' ? $payload['selectedYear'] : 'All',
                number_format($payload['totalOfficeExpense'], 2, '.', ''),
                number_format($payload['totalSalaryExpense'], 2, '.', ''),
                number_format($payload['totalExpense'], 2, '.', ''),
                number_format($payload['totalIncome'], 2, '.', ''),
            ]);

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function buildSummary(Request $request): array
    {
        $selectedMonth = (string) $request->input('search_month', '0');
        $selectedYear = (string) $request->input('search_year', '0');

        $monthMap = $this->monthMap();
        $monthNumber = isset($monthMap[$selectedMonth]) ? $monthMap[$selectedMonth] : null;
        $yearNumber = is_numeric($selectedYear) ? (int) $selectedYear : null;

        $officeExpenseQuery = Expense::query();
        if ($monthNumber) {
            $officeExpenseQuery->whereMonth('expense_date', $monthNumber);
        }
        if ($yearNumber && $yearNumber >= 2000 && $yearNumber <= 2100) {
            $officeExpenseQuery->whereYear('expense_date', $yearNumber);
        }
        $totalOfficeExpense = (float) $officeExpenseQuery->sum('amount');

        $salaryExpenseQuery = SalaryRecord::query();
        if ($monthNumber) {
            $salaryExpenseQuery->where('salary_month', $selectedMonth);
        }
        if ($yearNumber && $yearNumber >= 2000 && $yearNumber <= 2100) {
            $salaryExpenseQuery->where('salary_year', $yearNumber);
        }
        $totalSalaryExpense = (float) $salaryExpenseQuery->sum('net_salary');

        $incomeQuery = Enrollment::query()
            ->whereNotNull('money_rc_no')
            ->where('money_rc_no', '!=', '');

        if ($monthNumber) {
            $incomeQuery->whereMonth('created_at', $monthNumber);
        }
        if ($yearNumber && $yearNumber >= 2000 && $yearNumber <= 2100) {
            $incomeQuery->whereYear('created_at', $yearNumber);
        }

        $incomeSummary = $incomeQuery
            ->toBase()
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_income, COALESCE(SUM(payment_amount), 0) as payment_income, COALESCE(SUM(service_amount), 0) as service_income')
            ->first();

        $totalIncome = (float) ($incomeSummary->total_income ?? 0);
        if ($totalIncome <= 0) {
            $totalIncome = (float) (($incomeSummary->payment_income ?? 0) + ($incomeSummary->service_income ?? 0));
        }

        return [
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'totalOfficeExpense' => $totalOfficeExpense,
            'totalSalaryExpense' => $totalSalaryExpense,
            'totalExpense' => $totalOfficeExpense + $totalSalaryExpense,
            'totalIncome' => $totalIncome,
        ];
    }

    private function monthMap(): array
    {
        return [
            'January' => 1,
            'February' => 2,
            'March' => 3,
            'April' => 4,
            'May' => 5,
            'June' => 6,
            'July' => 7,
            'August' => 8,
            'September' => 9,
            'October' => 10,
            'November' => 11,
            'December' => 12,
        ];
    }
}
