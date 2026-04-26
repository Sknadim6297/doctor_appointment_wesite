<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = $this->filteredQuery($request)
            ->with('category')
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($request->query());

        $totalExpense = (clone $this->filteredQuery($request))->sum('amount');

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        $years = range(2000, ((int) date('Y')) + 10);
        $categories = ExpenseCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.account-management.manage-expense', [
            'expenses' => $expenses,
            'categories' => $categories,
            'months' => $months,
            'years' => $years,
            'searchMonth' => (string) $request->input('search_month', '0'),
            'searchYear' => (string) $request->input('search_year', '0'),
            'expenseCatFilter' => (string) $request->input('expense_cat_filter', '0'),
            'totalExpense' => (float) $totalExpense,
        ]);
    }

    public function csvReport(Request $request): StreamedResponse
    {
        $fileName = 'manage-expense-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'SL No',
                'Expense type',
                'Date',
                'Amount',
                'Payment mode',
                'Cheque no',
                'Bank',
                'Customer',
            ]);

            $slNo = 1;
            foreach ($this->filteredQuery($request)->with('category')->orderByDesc('expense_date')->orderByDesc('id')->cursor() as $expense) {
                fputcsv($output, [
                    $slNo++,
                    $expense->category?->name ?? 'N/A',
                    optional($expense->expense_date)->format('d/m/Y') ?? 'N/A',
                    'Rs. ' . number_format((float) $expense->amount, 0) . '/-',
                    $expense->payment_mode,
                    $expense->cheque_no ?: 'N/A',
                    $expense->bank_name ?: 'N/A',
                    $expense->customer_name ?: 'N/A',
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_mode' => 'required|string|in:cash,cheque,online',
            'cheque_no' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'voucher_file' => 'nullable|file|max:10240',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $voucherPath = null;
        if ($request->hasFile('voucher_file')) {
            $voucherPath = $request->file('voucher_file')->store('voucher', 'public');
        }

        Expense::create([
            'expense_category_id' => $data['expense_category_id'],
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'payment_mode' => strtolower($data['payment_mode']),
            'cheque_no' => $data['cheque_no'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'voucher_file' => $voucherPath,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.manage-expense.index')->with('success', 'Expense added successfully.');
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'expense_category_id' => 'required|integer|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'amount' => 'required|numeric',
            'payment_mode' => 'required|string|in:cash,cheque,online',
            'cheque_no' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'voucher_file' => 'nullable|file|max:10240',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($request->hasFile('voucher_file')) {
            if ($expense->voucher_file) {
                Storage::disk('public')->delete($expense->voucher_file);
            }
            $expense->voucher_file = $request->file('voucher_file')->store('voucher', 'public');
        }

        $expense->update([
            'expense_category_id' => $data['expense_category_id'],
            'expense_date' => $data['expense_date'],
            'amount' => $data['amount'],
            'payment_mode' => strtolower($data['payment_mode']),
            'cheque_no' => $data['cheque_no'] ?? null,
            'bank_name' => $data['bank_name'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()->route('admin.manage-expense.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        if ($expense->voucher_file) {
            Storage::disk('public')->delete($expense->voucher_file);
        }

        $expense->delete();

        return redirect()->route('admin.manage-expense.index')->with('success', 'Expense deleted successfully.');
    }

    public function showJson(Expense $expense): JsonResponse
    {
        return response()->json([
            'success' => true,
            'expense' => [
                'id' => $expense->id,
                'expense_category_id' => $expense->expense_category_id,
                'expense_date' => optional($expense->expense_date)->format('Y-m-d'),
                'amount' => (float) $expense->amount,
                'payment_mode' => $expense->payment_mode,
                'cheque_no' => $expense->cheque_no,
                'bank_name' => $expense->bank_name,
                'customer_name' => $expense->customer_name,
                'remarks' => $expense->remarks,
                'voucher_file' => $expense->voucher_file,
            ],
        ]);
    }

    private function filteredQuery(Request $request)
    {
        $searchMonth = (string) $request->input('search_month', '0');
        $searchYear = (string) $request->input('search_year', '0');
        $expenseCatFilter = (string) $request->input('expense_cat_filter', '0');

        $query = Expense::query();

        if ($searchMonth !== '0' && $searchMonth !== '') {
            $monthMap = [
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
            if (isset($monthMap[$searchMonth])) {
                $query->whereMonth('expense_date', $monthMap[$searchMonth]);
            }
        }

        if ($searchYear !== '0' && is_numeric($searchYear)) {
            $query->whereYear('expense_date', (int) $searchYear);
        }

        if ($expenseCatFilter !== '0' && is_numeric($expenseCatFilter)) {
            $query->where('expense_category_id', (int) $expenseCatFilter);
        }

        return $query;
    }
}
