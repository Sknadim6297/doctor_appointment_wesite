<?php

namespace Tests\Feature;

use App\Models\AdminPrivilege;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function employeeWithEnrollmentPrivilege(): User
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        foreach (['view', 'edit'] as $action) {
            AdminPrivilege::query()->updateOrCreate(
                ['user_id' => $employee->id, 'page_key' => 'enrollment', 'action_key' => $action],
                [
                    'group_key' => 'back_office_management',
                    'group_title' => 'Back Office Management',
                    'page_title' => 'Enrollment Records',
                    'action_title' => ucfirst($action),
                    'is_allowed' => true,
                ]
            );
        }

        return $employee;
    }

    private function adminWithApprove(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        AdminPrivilege::query()->updateOrCreate(
            ['user_id' => $admin->id, 'page_key' => 'enrollment', 'action_key' => 'approve'],
            [
                'group_key' => 'back_office_management',
                'group_title' => 'Back Office Management',
                'page_title' => 'Enrollment Records',
                'action_title' => 'Approve',
                'is_allowed' => true,
            ]
        );

        return $admin;
    }

    public function test_reject_requires_reason(): void
    {
        $admin = $this->adminWithApprove();
        $employee = $this->employeeWithEnrollmentPrivilege();

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-REJ-001',
            'doctor_name' => 'Reject Test',
            'mobile1' => '9000000001',
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'created_by_role' => 'employee',
            'status' => 'pending',
            'workflow_status' => 'pending_approval',
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.enrollment.reject', $enrollment->id), [
                'rejection_reason' => '',
            ])
            ->assertSessionHasErrors('rejection_reason');

        $this->actingAs($admin)
            ->post(route('admin.enrollment.reject', $enrollment->id), [
                'rejection_reason' => 'Incomplete documents provided.',
            ])
            ->assertRedirect(route('admin.enrollment.pending'));

        $enrollment->refresh();
        $this->assertSame('rejected', $enrollment->status);
        $this->assertSame('Incomplete documents provided.', $enrollment->rejection_reason);
    }

    public function test_employee_can_resubmit_after_rejection(): void
    {
        $employee = $this->employeeWithEnrollmentPrivilege();

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-RES-001',
            'doctor_name' => 'Resubmit Test',
            'mobile1' => '9000000002',
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'created_by_role' => 'employee',
            'status' => 'rejected',
            'workflow_status' => 'rejected',
            'rejection_reason' => 'Fix PAN copy',
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $this->actingAs($employee)
            ->post(route('admin.enrollment.resubmit', $enrollment))
            ->assertRedirect(route('admin.my-enrollments.show', $enrollment->id));

        $enrollment->refresh();
        $this->assertSame('pending', $enrollment->status);
        $this->assertSame('pending_approval', $enrollment->workflow_status);
        $this->assertNotNull($enrollment->resubmitted_at);
        $this->assertSame('Fix PAN copy', $enrollment->rejection_reason);
    }

    public function test_admin_can_hold_and_release(): void
    {
        $admin = $this->adminWithApprove();
        $employee = $this->employeeWithEnrollmentPrivilege();

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-HOLD-001',
            'doctor_name' => 'Hold Test',
            'mobile1' => '9000000003',
            'created_by' => $employee->id,
            'status' => 'pending',
            'workflow_status' => 'pending_approval',
            'current_step' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.enrollment.hold', $enrollment->id), [
                'hold_reason' => 'Awaiting clarification from agent.',
            ])
            ->assertRedirect(route('admin.enrollment.details', $enrollment->id));

        $enrollment->refresh();
        $this->assertSame('hold', $enrollment->workflow_status);

        $this->actingAs($admin)
            ->post(route('admin.enrollment.release-hold', $enrollment->id))
            ->assertRedirect(route('admin.enrollment.details', $enrollment->id));

        $enrollment->refresh();
        $this->assertSame('pending_approval', $enrollment->workflow_status);
    }

    public function test_employee_cannot_access_other_employee_enrollment(): void
    {
        $employeeA = $this->employeeWithEnrollmentPrivilege();
        $employeeB = $this->employeeWithEnrollmentPrivilege();

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-ISO-001',
            'doctor_name' => 'Private',
            'created_by' => $employeeA->id,
            'agent_id' => $employeeA->id,
            'status' => 'pending',
            'workflow_status' => 'pending_approval',
        ]);

        $this->actingAs($employeeB)
            ->get(route('admin.my-enrollments.show', $enrollment->id))
            ->assertForbidden();
    }
}
