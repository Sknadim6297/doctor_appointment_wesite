<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Services\LegacyDoctorPolicyImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyDoctorPolicyImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_links_policies_to_enrollment_by_legacy_user_id(): void
    {
        $enrollment = Enrollment::query()->create([
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'doctor_name' => 'DR TEST ONE',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $service = app(LegacyDoctorPolicyImportService::class);
        $service->loadSqlFile(base_path('tests/Fixtures/legacy_doctor_policy_sample.sql'));
        $stats = $service->syncFromStaging();

        $this->assertSame(5, $stats['created']);
        $this->assertSame(2, $stats['linked']);
        $this->assertSame(0, $stats['skipped']);

        $first = PolicyReceipt::query()->where('legacy_policy_id', 90001)->first();
        $this->assertNotNull($first);
        $this->assertSame($enrollment->id, $first->enrollment_id);
        $this->assertSame('DR TEST ONE', $first->doctor_name);
        $this->assertSame('0310002714P109429551', $first->policy_no);
        $this->assertSame('2018-01-01', $first->receive_date?->format('Y-m-d'));
        $this->assertSame(PolicyReceipt::STATUS_COMPLETED, $first->workflow_status);

        $second = PolicyReceipt::query()->where('legacy_policy_id', 90002)->first();
        $this->assertSame($enrollment->id, $second->enrollment_id);
        $this->assertSame('2019-01-01', $second->receive_date?->format('Y-m-d'));

        $orphan = PolicyReceipt::query()->where('legacy_policy_id', 90003)->first();
        $this->assertNotNull($orphan);
        $this->assertNull($orphan->enrollment_id);
    }

    public function test_reimport_updates_existing_legacy_policy(): void
    {
        Enrollment::query()->create([
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'doctor_name' => 'DR TEST ONE',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $service = app(LegacyDoctorPolicyImportService::class);
        $service->loadSqlFile(base_path('tests/Fixtures/legacy_doctor_policy_sample.sql'));
        $service->syncFromStaging();
        $stats = $service->syncFromStaging();

        $this->assertSame(0, $stats['created']);
        $this->assertSame(5, $stats['updated']);
        $this->assertSame(5, PolicyReceipt::query()->count());
    }

    public function test_artisan_command_imports_doctor_policies(): void
    {
        Enrollment::query()->create([
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'doctor_name' => 'DR TEST ONE',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $this->artisan('legacy:import-doctor-policies', [
            '--file' => base_path('tests/Fixtures/legacy_doctor_policy_sample.sql'),
        ])->assertSuccessful();

        $this->assertTrue(
            PolicyReceipt::query()->where('legacy_policy_id', 90001)->where('enrollment_id', '>', 0)->exists()
        );
    }
}
