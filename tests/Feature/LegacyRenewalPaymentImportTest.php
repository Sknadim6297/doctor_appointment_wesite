<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Models\RenewalHistory;
use App\Services\LegacyRenewalPaymentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyRenewalPaymentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_and_sync_updates_renewal_and_policy(): void
    {
        $fixture = base_path('tests/fixtures/legacy_renewal_payment_sample.sql');
        $this->assertFileExists($fixture);

        $this->createLegacyTableIfNeeded();

        \Illuminate\Support\Facades\DB::table('legacy_tbl_renewal_payment')->truncate();
        $load = app(LegacyRenewalPaymentImportService::class)->loadSqlFile($fixture, true);
        $this->assertSame(2, $load['loaded']);

        $enrollment = Enrollment::create([
            'legacy_user_id' => 9853,
            'customer_id_no' => 'CUST-9853',
            'doctor_name' => 'Test Doctor',
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

        $sync = app(LegacyRenewalPaymentImportService::class)->sync(false, false, false);
        $this->assertSame(1, $sync['synced']);
        $this->assertSame(0, $sync['skipped']);

        $enrollment->refresh();
        $this->assertSame('2023-03-30', $enrollment->last_renewal_date?->format('Y-m-d'));
        $this->assertSame('2023-03-30', $enrollment->renewal_date?->format('Y-m-d'));

        $history = RenewalHistory::where('enrollment_id', $enrollment->id)->first();
        $this->assertNotNull($history);
        $this->assertSame('2023-03-30', $history->renewed_date->format('Y-m-d'));
        $this->assertSame('yearly', $history->plan_type);

        $receipt = PolicyReceipt::where('enrollment_id', $enrollment->id)->first();
        $this->assertNotNull($receipt);
        $this->assertSame('2023-03-30', $receipt->last_renewed_date?->format('Y-m-d'));
    }

    public function test_dry_run_does_not_persist(): void
    {
        $fixture = base_path('tests/fixtures/legacy_renewal_payment_sample.sql');
        $this->createLegacyTableIfNeeded();

        $before = \Illuminate\Support\Facades\DB::table('legacy_tbl_renewal_payment')->count();

        app(LegacyRenewalPaymentImportService::class)->loadSqlFile($fixture, false);

        $this->assertSame($before, DB::table('legacy_tbl_renewal_payment')->count());
    }

    public function test_plan_id_maps_to_plan_types(): void
    {
        $service = app(LegacyRenewalPaymentImportService::class);
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('normalizePlanType');
        $method->setAccessible(true);

        $this->assertSame('insurance', $method->invokeArgs($service, [1, null]));
        $this->assertSame('combo', $method->invokeArgs($service, [2, null]));
        $this->assertSame('yearly', $method->invokeArgs($service, [3, null]));
    }

    private function createLegacyTableIfNeeded(bool $truncate = false): void
    {
        if (! Schema::hasTable('legacy_tbl_renewal_payment')) {
            Schema::create('legacy_tbl_renewal_payment', function ($table) {
                $table->id('id');
                $table->unsignedBigInteger('doctor_id');
                $table->string('payment_type', 50)->nullable();
                $table->string('cheque_no')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_branch')->nullable();
                $table->string('transaction_no')->nullable();
                $table->string('payment_date')->nullable();
                $table->string('payment_amount')->nullable();
                $table->text('payment_mode')->nullable();
                $table->string('money_reciept_no')->nullable();
                $table->string('checque_file')->nullable();
                $table->string('policy_no')->nullable();
                $table->text('remarks')->nullable();
                $table->integer('plan_id')->nullable();
                $table->string('from_old', 10)->default('no');
            });
        }
    }
}
