<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $salaries = $this->filteredQuery($request)
            ->with('employee:id,name,salary')
            ->orderByDesc('salary_year')
            ->orderByRaw("FIELD(salary_month, 'December','November','October','September','August','July','June','May','April','March','February','January')")
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($request->query());

        $totalSalary = (clone $this->filteredQuery($request))->sum('net_salary');

        return view('admin.account-management.manage-salary', [
            'salaries' => $salaries,
            'months' => $this->months(),
            'years' => range(((int) date('Y')) + 1, 2011),
            'employees' => User::query()
                ->whereNotNull('salary')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'salary']),
            'searchMonth' => (string) $request->input('search_month', '0'),
            'searchYear' => (string) $request->input('search_year', '0'),
            'searchEmployee' => (string) $request->input('search_employee', '0'),
            'totalSalary' => (float) $totalSalary,
        ]);
    }

    public function csvReport(Request $request): StreamedResponse
    {
        $fileName = 'manage-salary-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'SL No',
                'Employee name',
                'Salary',
                'Month',
                'Year',
                'Cheque no',
                'Bank name',
            ]);

            $slNo = 1;
            foreach ($this->filteredQuery($request)->with('employee:id,name')->orderByDesc('salary_year')->orderByDesc('id')->cursor() as $salary) {
                fputcsv($output, [
                    $slNo++,
                    $salary->employee?->name ?? 'N/A',
                    'Rs ' . number_format((float) $salary->net_salary, 0) . '/-',
                    $salary->salary_month,
                    $salary->salary_year,
                    $salary->cheque_no ?: 'N/A',
                    $salary->bank_name ?: 'N/A',
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        SalaryRecord::create([
            'user_id' => $data['employee'],
            'salary_year' => (int) $data['year'],
            'salary_month' => $data['month'],
            'monthly_salary' => $this->num($data['monthly_salary'] ?? 0),
            'total_login_day' => $data['total_login_day'] ?? null,
            'total_absense' => (int) ($data['total_absense'] ?? 0),
            'absense_reason' => $data['absense_reason'] ?? null,
            'incentive' => $this->num($data['incentive'] ?? 0),
            'incentive_for' => $data['incentive_for'] ?? null,
            'advance' => $this->num($data['advance'] ?? 0),
            'additional_deduct' => $this->num($data['additional_deduct'] ?? 0),
            'additional_deduct_reason' => $data['additional_deduct_reason'] ?? null,
            'office_duty' => $this->num($data['office_duty'] ?? 0),
            'bonus' => $this->num($data['bonus'] ?? 0),
            'pf' => $this->num($data['pf'] ?? 0),
            'esi' => $this->num($data['esi'] ?? 0),
            'ptax' => $this->num($data['ptax'] ?? 0),
            'cheque_no' => $data['cheque_no'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'net_salary' => $this->calculateNetSalary($data),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.manage-salary.index')->with('success', 'Salary generated successfully.');
    }

    public function update(Request $request, SalaryRecord $salaryRecord)
    {
        $data = $this->validateData($request, $salaryRecord);

        $salaryRecord->update([
            'user_id' => $data['employee'],
            'salary_year' => (int) $data['year'],
            'salary_month' => $data['month'],
            'monthly_salary' => $this->num($data['monthly_salary'] ?? 0),
            'total_login_day' => $data['total_login_day'] ?? null,
            'total_absense' => (int) ($data['total_absense'] ?? 0),
            'absense_reason' => $data['absense_reason'] ?? null,
            'incentive' => $this->num($data['incentive'] ?? 0),
            'incentive_for' => $data['incentive_for'] ?? null,
            'advance' => $this->num($data['advance'] ?? 0),
            'additional_deduct' => $this->num($data['additional_deduct'] ?? 0),
            'additional_deduct_reason' => $data['additional_deduct_reason'] ?? null,
            'office_duty' => $this->num($data['office_duty'] ?? 0),
            'bonus' => $this->num($data['bonus'] ?? 0),
            'pf' => $this->num($data['pf'] ?? 0),
            'esi' => $this->num($data['esi'] ?? 0),
            'ptax' => $this->num($data['ptax'] ?? 0),
            'cheque_no' => $data['cheque_no'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'net_salary' => $this->calculateNetSalary($data),
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.manage-salary.index')->with('success', 'Salary updated successfully.');
    }

    public function destroy(SalaryRecord $salaryRecord)
    {
        $salaryRecord->delete();

        return redirect()->route('admin.manage-salary.index')->with('success', 'Salary deleted successfully.');
    }

    public function show(SalaryRecord $salaryRecord)
    {
        $salaryRecord->load('employee:id,name,employee_no');

        return view('admin.account-management.salary-view', [
            'salary' => $salaryRecord,
        ]);
    }

    public function slip(SalaryRecord $salaryRecord)
    {
        $salaryRecord->load('employee:id,name,employee_no');

        return view('admin.account-management.salary-slip', [
            'salary' => $salaryRecord,
        ]);
    }

    public function showJson(SalaryRecord $salaryRecord): JsonResponse
    {
        return response()->json([
            'success' => true,
            'salary' => [
                'id' => $salaryRecord->id,
                'user_id' => $salaryRecord->user_id,
                'salary_year' => $salaryRecord->salary_year,
                'salary_month' => $salaryRecord->salary_month,
                'monthly_salary' => (float) $salaryRecord->monthly_salary,
                'total_login_day' => $salaryRecord->total_login_day,
                'total_absense' => $salaryRecord->total_absense,
                'absense_reason' => $salaryRecord->absense_reason,
                'incentive' => (float) $salaryRecord->incentive,
                'incentive_for' => $salaryRecord->incentive_for,
                'advance' => (float) $salaryRecord->advance,
                'additional_deduct' => (float) $salaryRecord->additional_deduct,
                'additional_deduct_reason' => $salaryRecord->additional_deduct_reason,
                'office_duty' => (float) $salaryRecord->office_duty,
                'bonus' => (float) $salaryRecord->bonus,
                'pf' => (float) $salaryRecord->pf,
                'esi' => (float) $salaryRecord->esi,
                'ptax' => (float) $salaryRecord->ptax,
                'cheque_no' => $salaryRecord->cheque_no,
                'bank_name' => $salaryRecord->bank_name,
            ],
        ]);
    }

    private function validateData(Request $request, ?SalaryRecord $salaryRecord = null): array
    {
        $uniqueRule = Rule::unique('salary_records', 'user_id')
            ->where(fn ($query) => $query
                ->where('salary_year', $request->input('year'))
                ->where('salary_month', $request->input('month')));

        if ($salaryRecord) {
            $uniqueRule->ignore($salaryRecord->id);
        }

        return $request->validate([
            'year' => 'required|integer|min:2011|max:2100',
            'month' => ['required', 'string', Rule::in($this->months())],
            'employee' => ['required', 'integer', Rule::exists('users', 'id'), $uniqueRule],
            'monthly_salary' => 'required|numeric|min:0',
            'total_login_day' => 'nullable|integer|min:0|max:31',
            'total_absense' => 'nullable|integer|min:0|max:31',
            'absense_reason' => 'nullable|string|max:1000',
            'incentive' => 'nullable|numeric|min:0',
            'incentive_for' => 'nullable|string|max:1000',
            'advance' => 'nullable|numeric|min:0',
            'additional_deduct' => 'nullable|numeric|min:0',
            'additional_deduct_reason' => 'nullable|string|max:255',
            'office_duty' => 'nullable|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'pf' => 'nullable|numeric|min:0',
            'esi' => 'nullable|numeric|min:0',
            'ptax' => 'nullable|numeric|min:0',
            'cheque_no' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
        ], [
            'employee.unique' => 'Salary for this employee and month/year already exists.',
        ]);
    }

    private function calculateNetSalary(array $data): float
    {
        $monthly = $this->num($data['monthly_salary'] ?? 0);
        $additions = $this->num($data['incentive'] ?? 0)
            + $this->num($data['office_duty'] ?? 0)
            + $this->num($data['bonus'] ?? 0);

        $deductions = $this->num($data['advance'] ?? 0)
            + $this->num($data['additional_deduct'] ?? 0)
            + $this->num($data['pf'] ?? 0)
            + $this->num($data['esi'] ?? 0)
            + $this->num($data['ptax'] ?? 0);

        return max(0, $monthly + $additions - $deductions);
    }

    private function num(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (float) $value;
    }

    private function filteredQuery(Request $request)
    {
        $searchMonth = (string) $request->input('search_month', '0');
        $searchYear = (string) $request->input('search_year', '0');
        $searchEmployee = (string) $request->input('search_employee', '0');

        $query = SalaryRecord::query();

        if ($searchMonth !== '0' && in_array($searchMonth, $this->months(), true)) {
            $query->where('salary_month', $searchMonth);
        }

        if ($searchYear !== '0' && is_numeric($searchYear)) {
            $query->where('salary_year', (int) $searchYear);
        }

        if ($searchEmployee !== '0' && is_numeric($searchEmployee)) {
            $query->where('user_id', (int) $searchEmployee);
        }

        return $query;
    }

    private function months(): array
    {
        return [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];
    }
}
