<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Specialization;
use App\Services\LegacyCallSheetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyCallSheetImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();
        DB::table('specializations')->insert([
            ['id' => 2, 'name' => 'Consultant Physician', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 126, 'name' => 'General Physician(MBBS)(PLAN-A)', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function test_import_maps_legacy_fields_and_specialization_ids(): void
    {
        $service = app(LegacyCallSheetImportService::class);
        $service->loadSqlFile(base_path('tests/Fixtures/legacy_call_sheet_sample.sql'));
        $stats = $service->syncFromStaging();

        $this->assertSame(2, $stats['created']);

        $first = Enrollment::query()->where('legacy_call_sheet_id', 4)->first();
        $this->assertNotNull($first);
        $this->assertSame('DR. ARIF IQBAL HOSSAIN', $first->doctor_name);
        $this->assertSame('arifdrhossain@gmail.com', $first->doctor_email);
        $this->assertSame(126, $first->specialization_id);
        $this->assertSame([126], $first->call_sheet_specialization_ids);
        $this->assertSame('5c63d3971c541-DR.-ARIF-IQBAL-HOSSAIN', $first->call_sheet_card_slug);
        $this->assertSame('February', $first->call_sheet_month);
        $this->assertSame('2019', $first->call_sheet_year);
        $this->assertFalse($first->hide_from_call_sheet);

        $second = Enrollment::query()->where('legacy_call_sheet_id', 5)->first();
        $this->assertSame(2, $second->specialization_id);
        $this->assertNull($second->doctor_email);
    }

    public function test_visible_on_call_sheet_excludes_legacy_doctors(): void
    {
        Enrollment::query()->create([
            'doctor_name' => 'LEGACY DOC',
            'legacy_user_id' => 9663,
            'customer_id_no' => 'DR.TEST9663',
            'hide_from_call_sheet' => false,
            'status' => 'approved',
            'workflow_status' => 'completed',
        ]);

        $service = app(LegacyCallSheetImportService::class);
        $service->loadSqlFile(base_path('tests/Fixtures/legacy_call_sheet_sample.sql'));
        $service->syncFromStaging();
        $service->hideLegacyDoctorsFromCallSheet();

        $visible = Enrollment::query()->visibleOnCallSheet()->count();
        $this->assertSame(2, $visible);
    }

    public function test_artisan_command_imports_call_sheets(): void
    {
        $this->artisan('legacy:import-call-sheets', [
            '--file' => base_path('tests/Fixtures/legacy_call_sheet_sample.sql'),
        ])->assertSuccessful();

        $this->assertSame(2, Enrollment::query()->whereNotNull('legacy_call_sheet_id')->count());
    }
}
