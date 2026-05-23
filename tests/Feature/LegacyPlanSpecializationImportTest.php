<?php

namespace Tests\Feature;

use App\Models\ComboPlan;
use App\Models\InsurancePlan;
use App\Models\Specialization;
use App\Services\LegacyPlanImportService;
use App\Services\LegacyPlanSpecializationImportService;
use App\Support\PlanPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyPlanSpecializationImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Specialization::query()->insert([
            ['id' => 5, 'name' => 'Dentist', 'legacy_user_type' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'name' => 'Gynecologist', 'legacy_user_type' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 126, 'name' => 'General Physician', 'legacy_user_type' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        app(LegacyPlanImportService::class)->loadComboPlanSqlFile(base_path('tests/Fixtures/legacy_combo_plan_sample.sql'));
        app(LegacyPlanImportService::class)->syncComboPlans(replaceExisting: true);
    }

    public function test_combo_plan_specialization_links_filter_by_id(): void
    {
        $service = app(LegacyPlanSpecializationImportService::class);
        $service->loadComboPlanSpecializationSqlFile(base_path('tests/Fixtures/legacy_tbl_combo_plan_specialization_sample.sql'));
        $stats = $service->syncComboPlanSpecializations(replaceExisting: true);

        $this->assertGreaterThanOrEqual(3, $stats['created']);
        $this->assertDatabaseHas('combo_plan_specialization', [
            'combo_plan_id' => 1,
            'specialization_id' => 5,
        ]);

        $plan = ComboPlan::query()->findOrFail(1);
        $this->assertTrue(PlanPricing::comboPlanMatchesSpecialization($plan, 5));
        $this->assertFalse(PlanPricing::comboPlanMatchesSpecialization($plan, 999));

        $general = ComboPlan::query()->findOrFail(8);
        $this->assertTrue(PlanPricing::comboPlanMatchesSpecialization($general, 126));
    }

    public function test_insurance_plan_specialization_links(): void
    {
        $service = app(LegacyPlanSpecializationImportService::class);
        $service->loadInsurancePlanSqlFile(base_path('tests/Fixtures/legacy_tbl_insurence_sample.sql'));
        $service->syncInsurancePlans(replaceExisting: true);
        $service->loadInsurancePlanSpecializationSqlFile(base_path('tests/Fixtures/legacy_tbl_insurence_plan_specialization_sample.sql'));
        $stats = $service->syncInsurancePlanSpecializations();

        $this->assertSame(1, $stats['plans_updated']);

        $plan = InsurancePlan::query()->findOrFail(15);
        $specs = $plan->specializations;
        $this->assertContains(5, $specs);
        $this->assertContains(10, $specs);
        $this->assertSame(472.0, PlanPricing::amountForPaymentMode($plan, 'Two Year'));
    }

    public function test_artisan_command_imports_plan_specializations(): void
    {
        $this->artisan('legacy:import-plan-specializations', [
            '--combo-file' => base_path('tests/Fixtures/legacy_tbl_combo_plan_specialization_sample.sql'),
            '--insurance-file' => base_path('tests/Fixtures/legacy_tbl_insurence_plan_specialization_sample.sql'),
            '--insurance-plans-file' => base_path('tests/Fixtures/legacy_tbl_insurence_sample.sql'),
            '--replace' => true,
        ])->assertSuccessful();

        $this->assertGreaterThan(0, DB::table('combo_plan_specialization')->count());
        $this->assertTrue(InsurancePlan::query()->whereKey(15)->exists());
    }
}
