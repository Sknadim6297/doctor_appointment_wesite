<?php

namespace Tests\Unit;

use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentAccountListingScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_with_account_listing_includes_policy_and_payment_without_money_rc_no(): void
    {
        $withPolicy = Enrollment::query()->create([
            'doctor_name' => 'Dr Policy',
            'customer_id_no' => 'POLICY-1',
            'policy_no' => 'POL-12345',
            'money_rc_no' => null,
            'payment_amount' => 0,
        ]);

        $withPayment = Enrollment::query()->create([
            'doctor_name' => 'Dr Payment',
            'customer_id_no' => 'PAYMENT-1',
            'policy_no' => null,
            'money_rc_no' => null,
            'payment_amount' => 1500,
        ]);

        $excluded = Enrollment::query()->create([
            'doctor_name' => 'Dr Empty',
            'customer_id_no' => 'EMPTY-1',
            'policy_no' => null,
            'money_rc_no' => null,
            'payment_amount' => 0,
            'doctor_money_reciept_no' => null,
        ]);

        $ids = Enrollment::query()->withAccountListing()->pluck('id')->all();

        $this->assertContains($withPolicy->id, $ids);
        $this->assertContains($withPayment->id, $ids);
        $this->assertNotContains($excluded->id, $ids);
    }
}
