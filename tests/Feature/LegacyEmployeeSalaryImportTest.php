<?php

namespace Tests\Feature;

use App\Models\SalaryRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyEmployeeSalaryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_links_legacy_employee_to_user_and_preserves_salary_id(): void
    {
        $employee = User::factory()->create([
            'legacy_user_id' => 10088,
            'salary' => 6000,
            'is_active' => true,
        ]);

        $fixture = base_path('tests/Fixtures/legacy_tbl_employee_salary_sample.sql');

        Artisan::call('legacy:import-employee-salary', [
            '--file' => $fixture,
            '--replace' => true,
        ]);

        $this->assertSame(2, DB::table('legacy_tbl_employee_salary')->count());

        $record = SalaryRecord::query()->find(4);
        $this->assertNotNull($record);
        $this->assertSame($employee->id, $record->user_id);
        $this->assertSame(2018, $record->salary_year);
        $this->assertSame('November', $record->salary_month);
        $this->assertEquals(6000.0, (float) $record->monthly_salary);
        $this->assertEquals(4500.0, (float) $record->net_salary);
        $this->assertSame('442792', $record->cheque_no);
    }

    public function test_import_skips_unknown_employee(): void
    {
        User::factory()->create(['legacy_user_id' => 10088]);

        $fixture = base_path('tests/Fixtures/legacy_tbl_employee_salary_sample.sql');

        Artisan::call('legacy:import-employee-salary', [
            '--file' => $fixture,
            '--replace' => true,
        ]);

        $this->assertNull(SalaryRecord::query()->find(9));
        $this->assertNotNull(SalaryRecord::query()->find(4));
    }
}
