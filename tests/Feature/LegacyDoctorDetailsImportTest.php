<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Services\LegacyDoctorDetailsImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyDoctorDetailsImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();
        DB::table('specializations')->insert([
            ['id' => 13, 'name' => 'Medicine', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 46, 'name' => 'Ophthalmology', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function test_sync_merges_details_into_matching_enrollment(): void
    {
        Enrollment::query()->create([
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'doctor_name' => 'DR TEST ONE',
            'clinic_address' => 'Old clinic from user table',
            'specialization_id' => 126,
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        Enrollment::query()->create([
            'legacy_user_id' => 9664,
            'customer_id_no' => 'DR.TEST9664',
            'doctor_name' => 'DR TEST TWO',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $service = app(LegacyDoctorDetailsImportService::class);
        $service->loadSqlFile(base_path('tests/Fixtures/legacy_doctor_details_sample.sql'));
        $stats = $service->syncDetailsToEnrollments();

        $this->assertSame(2, $stats['updated']);
        $this->assertSame(0, $stats['skipped']);

        $first = Enrollment::query()->where('legacy_user_id', 9663)->first();
        $this->assertSame(['MBBS/DOMS/MS'], $first->qualification);
        $this->assertSame(['1985', '1987', '1994'], $first->qualification_year);
        $this->assertSame('45420', $first->medical_registration_no);
        $this->assertSame('1987', $first->year_of_reg);
        $this->assertSame('146A, USTAD ENAYAT KHAN AVENUE', $first->clinic_address);
        $this->assertSame('SUPARNA BISWAS', $first->agent_name);
        $this->assertSame('9681203303', $first->agent_phone_no);
        $this->assertSame(46, $first->specialization_id);

        $second = Enrollment::query()->where('legacy_user_id', 9664)->first();
        $this->assertSame(['MBBS', 'MD'], $second->qualification);
        $this->assertSame(13, $second->specialization_id);
    }

    public function test_artisan_command_imports_doctor_details(): void
    {
        Enrollment::query()->create([
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'doctor_name' => 'DR TEST ONE',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $this->artisan('legacy:import-doctor-details', [
            '--file' => base_path('tests/Fixtures/legacy_doctor_details_sample.sql'),
        ])->assertSuccessful();

        $enrollment = Enrollment::query()->where('legacy_user_id', 9663)->first();
        $this->assertSame('45420', $enrollment->medical_registration_no);
        $this->assertSame('SUPARNA BISWAS', $enrollment->agent_name);
    }
}
