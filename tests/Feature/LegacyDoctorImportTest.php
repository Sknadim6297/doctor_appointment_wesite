<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\LegacyDoctorImportService;
use App\Support\EnrollmentWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyDoctorImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_doctor_import_creates_and_updates_enrollments(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        $fixture = base_path('tests/Fixtures/legacy_doctors_sample.sql');
        $service = app(LegacyDoctorImportService::class);

        $service->loadSqlFile($fixture);

        $activeStats = $service->syncDoctorsFromStaging(onlyActive: true);

        $this->assertSame(1, $activeStats['created']);
        $this->assertSame(0, $activeStats['updated']);

        $enrollment = Enrollment::query()->where('legacy_user_id', 9663)->first();

        $this->assertNotNull($enrollment);
        $this->assertSame('DR.IMTIAZAHMED9663', $enrollment->customer_id_no);
        $this->assertSame('DR.IMTIAZ AHMED', $enrollment->doctor_name);
        $this->assertSame('approved', $enrollment->status);
        $this->assertSame(EnrollmentWorkflow::COMPLETED, $enrollment->workflow_status);
        $this->assertSame($admin->id, $enrollment->created_by);
        $this->assertSame($admin->id, $enrollment->approved_by);
        $this->assertNotNull($enrollment->approved_at);
        $this->assertTrue($enrollment->isProductionActive());
        $this->assertTrue(
            Enrollment::query()->whereKey($enrollment->id)->productionReady()->exists()
        );

        $service->loadSqlFile($fixture);
        $secondRun = $service->syncDoctorsFromStaging(onlyActive: true);

        $this->assertSame(0, $secondRun['created']);
        $this->assertSame(1, $secondRun['updated']);
        $this->assertSame(1, Enrollment::query()->where('legacy_user_id', 9663)->count());

        $this->assertSame(
            0,
            Enrollment::query()->where('legacy_user_id', 10078)->count()
        );
        $this->assertSame(
            1,
            DB::table('legacy_tbl_user')->where('user_type_id', 3)->where('status', 'inactive')->count()
        );
    }
}
