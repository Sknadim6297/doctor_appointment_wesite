<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $categories = ExpenseCategory::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->appends($request->query());

        return view('admin.account-management.manage-expense-category', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'expensive_cat' => 'required|string|max:255|unique:expense_categories,name',
        ]);

        ExpenseCategory::create([
            'name' => trim($data['expensive_cat']),
            'created_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.expense-categories.index')
            ->with('success', 'Expense category added successfully.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $data = $request->validate([
            'expensive_cat' => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id,
        ]);

        $expenseCategory->update([
            'name' => trim($data['expensive_cat']),
            'updated_by' => $request->user()?->id,
        ]);

        return redirect()
            ->route('admin.expense-categories.index')
            ->with('success', 'Expense category updated successfully.');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();

        return redirect()
            ->route('admin.expense-categories.index')
            ->with('success', 'Expense category deleted successfully.');
    }
}
