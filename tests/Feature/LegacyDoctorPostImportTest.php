<?php

namespace Tests\Feature;

use App\Models\DoctorPost;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\LegacyDoctorPostImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyDoctorPostImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'super_admin']);
    }

    public function test_sync_links_posts_to_enrollment_by_legacy_user_id(): void
    {
        $enrollment = Enrollment::query()->create([
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'doctor_name' => 'DR TEST ONE',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $service = app(LegacyDoctorPostImportService::class);
        $service->loadSqlFile(base_path('tests/Fixtures/legacy_doctor_post_sample.sql'));
        $stats = $service->syncFromStaging();

        $this->assertSame(2, $stats['created']);
        $this->assertSame(1, $stats['linked']);
        $this->assertSame(0, $stats['skipped']);

        $linked = DoctorPost::query()->where('legacy_post_id', 90001)->first();
        $this->assertNotNull($linked);
        $this->assertSame($enrollment->id, $linked->enrollment_id);
        $this->assertSame('DR TEST ONE', $linked->doctor_name);
        $this->assertSame('2016-04-27', $linked->post_doc_date?->format('Y-m-d'));
        $this->assertSame('K87735856', $linked->post_doc_consignment_no);
        $this->assertSame('DTDC', $linked->post_doc_by);
        $this->assertSame('Imported remark', $linked->post_doc_remark);

        $orphan = DoctorPost::query()->where('legacy_post_id', 90002)->first();
        $this->assertNotNull($orphan);
        $this->assertNull($orphan->enrollment_id);
        $this->assertSame('https://example.com/track/90002', $orphan->tracking_link);
    }

    public function test_reimport_updates_existing_legacy_post(): void
    {
        Enrollment::query()->create([
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'doctor_name' => 'DR TEST ONE',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $service = app(LegacyDoctorPostImportService::class);
        $service->loadSqlFile(base_path('tests/Fixtures/legacy_doctor_post_sample.sql'));
        $service->syncFromStaging();
        $stats = $service->syncFromStaging();

        $this->assertSame(0, $stats['created']);
        $this->assertSame(2, $stats['updated']);
        $this->assertSame(2, DoctorPost::query()->count());
    }

    public function test_artisan_command_imports_doctor_posts(): void
    {
        Enrollment::query()->create([
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'doctor_name' => 'DR TEST ONE',
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $this->artisan('legacy:import-doctor-posts', [
            '--file' => base_path('tests/Fixtures/legacy_doctor_post_sample.sql'),
        ])->assertSuccessful();

        $this->assertTrue(
            DoctorPost::query()->where('legacy_post_id', 90001)->where('enrollment_id', '>', 0)->exists()
        );
    }
}
