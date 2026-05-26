<?php

namespace Tests\Feature;

use App\Models\AdminPrivilege;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentRecordAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_doctor_list_only_shows_own_production_ready_doctors(): void
    {
        $employee = $this->createEmployeeWithDoctorListAccess();
        $otherEmployee = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        $ownDoctor = $this->createProductionReadyDoctor($employee, 'MEM-OWN-1', 'Dr. Own Patient');
        $otherDoctor = $this->createProductionReadyDoctor($otherEmployee, 'MEM-OTHER-1', 'Dr. Other Patient');

        $response = $this->actingAs($employee)->get(route('admin.doctors.index'));

        $response->assertOk();
        $response->assertSee('MEM-OWN-1');
        $response->assertSee('Dr. Own Patient');
        $response->assertDontSee('MEM-OTHER-1');
        $response->assertDontSee('Dr. Other Patient');
    }

    public function test_employee_cannot_open_another_employees_doctor_profile(): void
    {
        $employee = $this->createEmployeeWithDoctorListAccess();
        $otherEmployee = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        $otherDoctor = $this->createProductionReadyDoctor($otherEmployee, 'MEM-BLOCKED', 'Dr. Blocked');

        $this->actingAs($employee)
            ->get(route('admin.doctors.show', $otherDoctor->id))
            ->assertForbidden();
    }

    public function test_employee_cannot_see_super_admin_created_enrollment_even_with_matching_agent(): void
    {
        $employee = $this->createEmployeeWithDoctorListAccess();

        $adminEnrollment = Enrollment::create([
            'customer_id_no' => 'MEM-ADMIN-1',
            'doctor_name' => 'Dr. Admin Created',
            'mobile1' => '9990000001',
            'agent_name' => 'Employee',
            'agent_phone_no' => '9000000001',
            'created_by' => User::factory()->create(['role' => 'super_admin', 'is_active' => true])->id,
            'agent_id' => $employee->id,
            'created_by_role' => 'super_admin',
            'status' => 'approved',
            'workflow_status' => 'completed',
            'is_step_incomplete' => false,
            'approved_by' => 1,
            'approved_at' => now(),
        ]);

        $this->actingAs($employee)
            ->get(route('admin.enrollment.details', $adminEnrollment->id))
            ->assertForbidden();
    }

    public function test_super_admin_sees_all_enrollments_in_monitoring(): void
    {
        $superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $employee = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        Enrollment::create([
            'customer_id_no' => 'MEM-MON-1',
            'doctor_name' => 'Dr. Monitoring One',
            'mobile1' => '9990000101',
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'status' => 'pending',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.enrollment.monitoring'))
            ->assertOk()
            ->assertSee('MEM-MON-1');
    }

    private function createEmployeeWithDoctorListAccess(): User
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_active' => true,
        ]);

        foreach (['enrollment' => 'view', 'doctors' => 'view'] as $page => $action) {
            AdminPrivilege::query()->updateOrCreate(
                ['user_id' => $employee->id, 'page_key' => $page, 'action_key' => $action],
                [
                    'group_key' => 'doctor_management',
                    'group_title' => 'Doctor Management',
                    'page_title' => ucfirst($page),
                    'action_title' => ucfirst($action),
                    'is_allowed' => true,
                ]
            );
        }

        return $employee;
    }

    private function createProductionReadyDoctor(User $owner, string $membershipNo, string $name): Enrollment
    {
        return Enrollment::create([
            'legacy_user_id' => random_int(10000, 99999),
            'customer_id_no' => $membershipNo,
            'doctor_name' => $name,
            'mobile1' => '9991111111',
            'agent_name' => $owner->name,
            'agent_phone_no' => $owner->phone,
            'created_by' => $owner->id,
            'agent_id' => $owner->id,
            'created_by_role' => 'employee',
            'status' => 'approved',
            'workflow_status' => 'completed',
            'is_step_incomplete' => false,
            'approved_by' => $owner->id,
            'approved_at' => now(),
        ]);
    }
}
