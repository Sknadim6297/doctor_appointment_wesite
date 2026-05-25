<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Models\RenewalHistory;
use App\Services\LegacyRenewalHistoryImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyRenewalHistoryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_links_renewal_history_and_updates_enrollment_dates(): void
    {
        $fixture = base_path('tests/Fixtures/legacy_tbl_renew_history_sample.sql');

        $this->assertFileExists($fixture);

        $enrollment = Enrollment::create([
            'legacy_user_id' => 9663,
            'doctor_name' => 'Renewal Test Doctor',
            'status' => 'active',
            'workflow_status' => 'completed',
            'current_step' => 4,
            'last_renewal_date' => null,
            'renewal_date' => null,
            'plan' => null,
            'coverage' => null,
            'service_amount' => null,
            'payment_amount' => null,
            'policy_no' => null,
        ]);

        $service = app(LegacyRenewalHistoryImportService::class);
        $service->loadSqlFile($fixture);
        $stats = $service->syncToApp();

        $this->assertSame(3, $stats['histories']);
        $this->assertSame(1, $stats['enrollments_updated']);
        $this->assertGreaterThanOrEqual(0, $stats['skipped']);

        $enrollment->refresh();

        $this->assertSame('2021-02-04', $enrollment->last_renewal_date?->format('Y-m-d'));
        $this->assertSame('2022-02-04', $enrollment->renewal_date?->format('Y-m-d'));
        $this->assertSame(3, $enrollment->plan);
        $this->assertEqualsWithDelta(9033.0, (float) $enrollment->payment_amount, 0.01);
        $this->assertEqualsWithDelta(5664.0, (float) $enrollment->service_amount, 0.01);

        $latest = RenewalHistory::query()
            ->where('enrollment_id', $enrollment->id)
            ->orderByDesc('renewed_date')
            ->first();

        $this->assertNotNull($latest);
        $this->assertSame(4107, $latest->legacy_renewal_id);
    }

    public function test_two_year_payment_mode_extends_next_renewal(): void
    {
        RenewalHistory::query()->delete();
        $this->seedRenewalStaging([
            [
                'id' => 1,
                'renew_doctor_id' => 9666,
                'renewed_date' => '2017-03-29',
                'renew_payment_mode' => 'Three Year',
                'renew_plan_id' => 3,
                'renew_legal_service' => '30',
            ],
            [
                'id' => 2,
                'renew_doctor_id' => 9666,
                'renewed_date' => '2020-06-30',
                'renew_payment_mode' => 'Two Year',
                'renew_plan_id' => 3,
                'renew_legal_service' => '30',
            ],
        ]);

        Enrollment::create([
            'legacy_user_id' => 9666,
            'doctor_name' => 'Two Year Plan Doctor',
            'status' => 'active',
            'workflow_status' => 'completed',
            'current_step' => 4,
        ]);

        app(LegacyRenewalHistoryImportService::class)->syncToApp(truncateHistories: true);

        $enrollment = Enrollment::query()->where('legacy_user_id', 9666)->first();

        $this->assertNotNull($enrollment);
        $this->assertSame('2020-06-30', $enrollment->last_renewal_date?->format('Y-m-d'));
        $this->assertSame('2022-06-30', $enrollment->renewal_date?->format('Y-m-d'));
        $this->assertSame('Two Year', $enrollment->payment_mode);
    }

    public function test_apply_uses_latest_renewal_per_doctor_when_chunk_order_is_mixed(): void
    {
        RenewalHistory::query()->delete();
        $this->seedRenewalStaging([
            [
                'renew_doctor_id' => 500,
                'renewed_date' => '2020-01-01',
                'renew_plan_id' => 1,
                'renew_legal_service' => '5',
                'renew_payment_mode' => 'One Year',
                'renew_policy_no' => 'OLD-POLICY',
            ],
            [
                'renew_doctor_id' => 500,
                'renewed_date' => '2024-06-20',
                'renew_plan_id' => 3,
                'renew_legal_service' => '20',
                'renew_payment_mode' => 'One Year',
                'renew_policy_no' => 'NEW-POLICY',
            ],
        ]);

        $enrollment = Enrollment::create([
            'legacy_user_id' => 500,
            'doctor_name' => 'Latest Renewal Doctor',
            'status' => 'active',
            'workflow_status' => 'completed',
            'current_step' => 4,
            'plan' => 3,
            'coverage' => 5,
            'last_renewal_date' => '2015-01-01',
        ]);

        PolicyReceipt::query()->create([
            'enrollment_id' => $enrollment->id,
            'policy_no' => 'STALE-POLICY',
            'last_renewed_date' => '2015-01-01',
        ]);

        $stats = app(LegacyRenewalHistoryImportService::class)->syncToApp(truncateHistories: true);

        $enrollment->refresh();

        $this->assertSame(1, $stats['enrollments_updated']);
        $this->assertSame(2, $stats['histories']);
        $this->assertSame(3, (int) $enrollment->plan);
        $this->assertSame('Combo - 20 Lakh', $enrollment->plan_name);
        $this->assertSame(20, (int) $enrollment->coverage);
        $this->assertSame('2024-06-20', $enrollment->last_renewal_date?->format('Y-m-d'));

        $policy = PolicyReceipt::query()->where('enrollment_id', $enrollment->id)->first();
        $this->assertNotNull($policy);
        $this->assertSame('NEW-POLICY', $policy->policy_no);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function seedRenewalStaging(array $rows): void
    {
        DB::statement('DROP TABLE IF EXISTS legacy_tbl_renew_history');
        DB::statement('CREATE TABLE legacy_tbl_renew_history (
            id INT NOT NULL,
            renew_doctor_id INT NOT NULL,
            renewed_date DATE NULL,
            renew_month TEXT NULL,
            renew_day TEXT NULL,
            renew_year TEXT NULL,
            renew_medeforum_amount VARCHAR(255) NULL,
            renew_insurance_amount VARCHAR(14) NULL,
            renew_insurance_coverage VARCHAR(15) NULL,
            renew_legal_service TEXT NULL,
            renew_payment_mode TEXT NULL,
            renew_plan_id INT NULL,
            renew_policy_no VARCHAR(255) NULL
        )');

        foreach ($rows as $row) {
            DB::table('legacy_tbl_renew_history')->insert(array_merge([
                'id' => (int) ($row['id'] ?? random_int(10000, 99999)),
            ], $row));
        }
    }
}
