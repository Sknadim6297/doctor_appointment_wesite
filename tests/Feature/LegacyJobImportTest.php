<?php

namespace Tests\Feature;

use App\Models\JobApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyJobImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preserves_legacy_ids(): void
    {
        $fixture = base_path('storage/app/legacy_tbl_job.sql');

        Artisan::call('legacy:import-jobs', [
            '--file' => $fixture,
            '--replace' => true,
        ]);

        $this->assertSame(8, DB::table('legacy_tbl_job')->count());
        $this->assertSame(8, JobApplication::query()->count());

        $application = JobApplication::query()->find(4);
        $this->assertNotNull($application);
        $this->assertSame('sweta gupta', $application->name);
        $this->assertEquals(10000.0, (float) $application->salary);
        $this->assertSame('023856500_1571910097.pdf', $application->document);
    }
}
