<?php

namespace Tests\Unit;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\EnrollmentEditAccessService;
use App\Support\EnrollmentWorkflow;
use Mockery;
use Tests\TestCase;

class EnrollmentEditAccessServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function service(): EnrollmentEditAccessService
    {
        return new EnrollmentEditAccessService(Mockery::mock(ActivityLogService::class));
    }

    public function test_creator_bypasses_otp_after_approval(): void
    {
        $service = $this->service();

        $owner = User::factory()->make(['role' => 'employee']);
        $owner->id = 10;
        $enrollment = new Enrollment([
            'created_by' => 10,
            'agent_id' => 10,
            'status' => 'approved',
            'workflow_status' => EnrollmentWorkflow::IN_PROGRESS,
            'current_step' => 2,
        ]);

        $this->assertTrue($service->requiresOtpGuard($enrollment));
        $this->assertFalse($service->requiresOtpGuardForUser($enrollment, $owner));
        $this->assertTrue($service->canBypassOtp($owner, $enrollment));
    }

    public function test_non_owner_still_requires_otp_when_approved(): void
    {
        $service = $this->service();

        $other = User::factory()->make(['role' => 'employee']);
        $other->id = 99;
        $enrollment = new Enrollment([
            'created_by' => 10,
            'status' => 'approved',
            'workflow_status' => EnrollmentWorkflow::IN_PROGRESS,
            'current_step' => 2,
        ]);

        $this->assertTrue($service->requiresOtpGuardForUser($enrollment, $other));
        $this->assertFalse($service->canBypassOtp($other, $enrollment));
    }

    public function test_super_admin_bypasses_otp(): void
    {
        $service = $this->service();

        $admin = new User(['id' => 1, 'role' => 'super_admin']);
        $enrollment = new Enrollment([
            'created_by' => 10,
            'status' => 'approved',
            'workflow_status' => EnrollmentWorkflow::COMPLETED,
            'is_step_incomplete' => false,
        ]);

        $this->assertFalse($service->requiresOtpGuardForUser($enrollment, $admin));
    }
}
