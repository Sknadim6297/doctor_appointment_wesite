<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\User;
use App\Support\EnrollmentWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardWorkflowCountersTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_gate_excludes_draft_but_includes_submitted(): void
    {
        Enrollment::create([
            'customer_id_no' => 'DRAFT-1',
            'doctor_name' => 'Draft enrollment',
            'status' => 'pending',
            'workflow_status' => EnrollmentWorkflow::DRAFT,
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        Enrollment::create([
            'customer_id_no' => 'SUB-1',
            'doctor_name' => 'Dr Pending',
            'status' => 'pending',
            'workflow_status' => EnrollmentWorkflow::PENDING_APPROVAL,
            'submitted_at' => now(),
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $pending = Enrollment::query()->tap(fn ($q) => EnrollmentWorkflow::scopePendingAdminGate($q))->count();
        $incomplete = Enrollment::query()->tap(fn ($q) => EnrollmentWorkflow::scopeIncompletePipeline($q))->count();

        $this->assertSame(1, $pending);
        $this->assertSame(2, $incomplete);
    }

    public function test_draft_can_continue_entry_but_not_submitted_pending(): void
    {
        $draft = Enrollment::create([
            'customer_id_no' => 'DRAFT-CONT',
            'doctor_name' => 'Dr Draft',
            'status' => 'pending',
            'workflow_status' => EnrollmentWorkflow::DRAFT,
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $submitted = Enrollment::create([
            'customer_id_no' => 'SUB-CONT',
            'doctor_name' => 'Dr Submitted',
            'status' => 'pending',
            'workflow_status' => EnrollmentWorkflow::PENDING_APPROVAL,
            'submitted_at' => now(),
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $this->assertTrue(EnrollmentWorkflow::canContinueDraftEntry($draft));
        $this->assertTrue(EnrollmentWorkflow::canResumeFromDashboard($draft));
        $this->assertSame('Continue', EnrollmentWorkflow::dashboardResumeLabel($draft));

        $this->assertFalse(EnrollmentWorkflow::canContinueDraftEntry($submitted));
        $this->assertFalse(EnrollmentWorkflow::canResumeFromDashboard($submitted));
    }
}
