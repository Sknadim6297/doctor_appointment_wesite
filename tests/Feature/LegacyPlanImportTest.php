<?php

namespace Tests\Feature;

use App\Models\ComboPlan;
use App\Models\HighRiskPlan;
use App\Models\NormalPlan;
use App\Services\LegacyPlanImportService;
use App\Support\PlanPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyPlanImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_plan_import_preserves_legacy_ids_and_pricing(): void
    {
        $service = app(LegacyPlanImportService::class);
        $service->loadNormalPlanSqlFile(base_path('tests/Fixtures/legacy_normal_plan_sample.sql'));
        $stats = $service->syncNormalPlans(replaceExisting: true);

        $this->assertSame(2, $stats['created']);
        $this->assertDatabaseHas('normal_plans', [
            'id' => 27,
            'coverage_lakh' => 3,
            'yearly_amount' => 9000,
            'two_year_amount' => 17100,
        ]);

        $plan = NormalPlan::query()->findOrFail(27);
        $this->assertSame(17100.0, PlanPricing::amountForPaymentMode($plan, 'Two Year'));
    }

    public function test_high_risk_plan_import_preserves_legacy_ids(): void
    {
        $service = app(LegacyPlanImportService::class);
        $service->loadHighPlanSqlFile(base_path('tests/Fixtures/legacy_high_plan_sample.sql'));
        $stats = $service->syncHighRiskPlans(replaceExisting: true);

        $this->assertSame(2, $stats['created']);
        $this->assertDatabaseHas('high_risk_plans', [
            'id' => 6,
            'coverage_lakh' => 1,
            'yearly_amount' => 5000,
        ]);
    }

    public function test_combo_plan_import_parses_specializations(): void
    {
        $service = app(LegacyPlanImportService::class);
        $service->loadComboPlanSqlFile(base_path('tests/Fixtures/legacy_combo_plan_sample.sql'));
        $stats = $service->syncComboPlans(replaceExisting: true);

        $this->assertSame(2, $stats['created']);

        $plan = ComboPlan::query()->findOrFail(1);
        $this->assertCount(2, $plan->specializations);
        $this->assertTrue(PlanPricing::comboPlanMatchesSpecialization($plan, 0, 'Dentist'));
        $this->assertFalse(PlanPricing::comboPlanMatchesSpecialization($plan, 0, 'Cardiologist'));
    }

    public function test_artisan_command_imports_all_plan_types(): void
    {
        $this->artisan('legacy:import-plans', [
            '--normal-file' => base_path('tests/Fixtures/legacy_normal_plan_sample.sql'),
            '--high-file' => base_path('tests/Fixtures/legacy_high_plan_sample.sql'),
            '--combo-file' => base_path('tests/Fixtures/legacy_combo_plan_sample.sql'),
            '--replace' => true,
        ])->assertSuccessful();

        $this->assertSame(2, DB::table('legacy_normal_plan')->count());
        $this->assertSame(2, NormalPlan::count());
        $this->assertSame(2, HighRiskPlan::count());
        $this->assertSame(2, ComboPlan::count());
    }
}
