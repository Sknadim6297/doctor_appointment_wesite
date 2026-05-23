<?php

namespace Tests\Feature;

use App\Models\Specialization;
use App\Services\LegacySpecializationImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacySpecializationImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_legacy_ids_and_names(): void
    {
        $fixture = base_path('tests/Fixtures/legacy_specialization_sample.sql');
        $service = app(LegacySpecializationImportService::class);

        $service->loadSqlFile($fixture);
        $stats = $service->syncToSpecializations(replaceExisting: true);

        $this->assertSame(2, $stats['created']);
        $this->assertDatabaseHas('specializations', [
            'id' => 2,
            'name' => 'Consultant Physician (General Medicine)(PL-B- SPL)',
            'legacy_user_type' => 2,
        ]);
        $this->assertDatabaseHas('specializations', [
            'id' => 4,
            'name' => 'Cardiologist',
            'legacy_user_type' => 2,
        ]);

        $this->assertSame(2, Specialization::count());
        $this->assertTrue($service->resolveLegacySpecializationId(4) === 4);
        $this->assertNull($service->resolveLegacySpecializationId(999));
    }

    public function test_replace_removes_non_legacy_seeded_rows(): void
    {
        Specialization::query()->create(['name' => 'Seeded Only']);

        $fixture = base_path('tests/Fixtures/legacy_specialization_sample.sql');
        $service = app(LegacySpecializationImportService::class);
        $service->loadSqlFile($fixture);
        $stats = $service->syncToSpecializations(replaceExisting: true);

        $this->assertGreaterThanOrEqual(1, $stats['removed']);
        $this->assertSame(0, Specialization::query()->where('name', 'Seeded Only')->count());
    }

    public function test_artisan_command_loads_and_syncs(): void
    {
        $fixture = base_path('tests/Fixtures/legacy_specialization_sample.sql');

        $this->artisan('legacy:import-specializations', [
            '--file' => $fixture,
            '--replace' => true,
        ])->assertSuccessful();

        $this->assertSame(2, DB::table('legacy_specialization')->count());
        $this->assertSame(2, Specialization::count());
    }
}
