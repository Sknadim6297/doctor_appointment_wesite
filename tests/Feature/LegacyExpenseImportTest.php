<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\LegacyExpenseImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyExpenseImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_import_preserves_legacy_ids_and_names(): void
    {
        $service = app(LegacyExpenseImportService::class);
        $service->loadCategorySqlFile(base_path('tests/Fixtures/legacy_tbl_expensive_category_sample.sql'));
        $stats = $service->syncCategories(replaceExisting: true);

        $this->assertSame(2, $stats['created']);
        $this->assertDatabaseHas('expense_categories', [
            'id' => 17,
            'name' => 'A/C LEGAL EXPENSES',
        ]);
    }

    public function test_expense_import_links_category_and_skips_unknown_category(): void
    {
        $service = app(LegacyExpenseImportService::class);
        $service->loadCategorySqlFile(base_path('tests/Fixtures/legacy_tbl_expensive_category_sample.sql'));
        $service->syncCategories(replaceExisting: true);
        $service->loadExpenseSqlFile(base_path('tests/Fixtures/legacy_tbl_expensive_sample.sql'));
        $stats = $service->syncExpenses(replaceExisting: true);

        $this->assertSame(2, $stats['created']);
        $this->assertSame(1, $stats['skipped']);

        $expense = Expense::query()->findOrFail(1);
        $this->assertSame(17, $expense->expense_category_id);
        $this->assertSame(2500.0, (float) $expense->amount);
        $this->assertSame('cheque', $expense->payment_mode);
        $this->assertSame('Advocate payment', $expense->remarks);

        $category = ExpenseCategory::query()->findOrFail(17);
        $this->assertCount(1, $category->expenses);
    }

    public function test_artisan_command_imports_categories_then_expenses(): void
    {
        $this->artisan('legacy:import-expenses', [
            '--category-file' => base_path('tests/Fixtures/legacy_tbl_expensive_category_sample.sql'),
            '--expense-file' => base_path('tests/Fixtures/legacy_tbl_expensive_sample.sql'),
            '--replace' => true,
        ])->assertSuccessful();

        $this->assertSame(2, DB::table('legacy_tbl_expensive_category')->count());
        $this->assertSame(3, DB::table('legacy_tbl_expensive')->count());
        $this->assertSame(2, ExpenseCategory::count());
        $this->assertSame(2, Expense::count());
    }
}
