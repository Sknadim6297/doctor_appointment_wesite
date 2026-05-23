<?php

namespace Tests\Feature;

use App\Models\ComboPlan;
use App\Models\Enrollment;
use App\Models\Specialization;
use App\Services\LegacyDoctorInsuranceImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyDoctorInsuranceImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_populates_plan_coverage_and_amounts(): void
    {
        Specialization::query()->insert([
            'id' => 2,
            'name' => 'Consultant Physician',
            'legacy_user_type' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ComboPlan::query()->insert([
            'id' => 115,
            'specializations' => json_encode([2]),
            'coverage_lakh' => 50,
            'yearly_amount' => 14100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Illuminate\Support\Facades\DB::table('legacy_tbl_doctor_details')->insert([
            'doctor_detais_id' => 9775,
            'user_id' => 9807,
            'specilality_id' => 2,
            'qualification' => 'MBBS',
            'qualification_year' => '1997',
            'medical_reg_no' => '38113',
            'medical_reg_year' => '1980',
            'clinic_address' => 'Test',
            'agent_name' => 'Agent',
            'agent_phone' => '9999999999',
            'bulk_upload' => 'no',
        ]);

        $enrollment = Enrollment::query()->create([
            'legacy_user_id' => 9807,
            'doctor_name' => 'DR.SOMNATH MOOKERJEE',
            'customer_id_no' => 'DR.SOMNATHMOOKERJEE9807',
            'status' => 'approved',
            'workflow_status' => 'completed',
            'workflow_completed_at' => now(),
            'current_step' => 4,
        ]);

        $service = app(LegacyDoctorInsuranceImportService::class);
        $service->loadSqlFile(base_path('tests/Fixtures/legacy_tbl_doctor_insurance_sample.sql'));
        $stats = $service->syncToEnrollments();

        $this->assertSame(1, $stats['updated']);

        $enrollment->refresh();

        $this->assertSame(3, (int) $enrollment->plan);
        $this->assertSame(2, (int) $enrollment->specialization_id);
        $this->assertSame(50.0, (float) $enrollment->coverage);
        $this->assertSame(4720.0, (float) $enrollment->service_amount);
        $this->assertSame(8999.0, (float) $enrollment->payment_amount);
        $this->assertSame('50 Lakh', $enrollment->formattedCoverageLabel());
    }
}
