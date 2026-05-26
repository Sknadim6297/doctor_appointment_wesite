<?php

namespace Tests\Feature;

use App\Models\AdminPrivilege;
use App\Models\Enrollment;
use App\Models\Specialization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end smoke coverage for the admin enrollment and account modules.
 */
class ApplicationEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_login_and_dashboard(): void
    {
        $superAdmin = User::factory()->create([
            'email' => 'superadmin@mediforum.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->post(route('admin.login.post'), [
            'email' => $superAdmin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_employee_enrollment_submit_admin_approve_and_step_two_unlock(): void
    {
        $spec = Specialization::query()->create(['name' => 'Cardiology']);

        $employee = User::factory()->create(['role' => 'employee', 'is_active' => true]);
        $this->grantEnrollmentPrivileges($employee, ['view', 'edit']);

        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $store = $this->actingAs($superAdmin)->post(route('admin.enrollment.store'), [
            'doctor_name' => 'Dr. E2E Workflow',
            'mobile2' => '9876543210',
            'aadhar_card_no' => '234567890123',
            'pan_card_no' => 'ABCDE1234F',
            'medical_registration_no' => 'REG-E2E-1',
            'specialization_id' => $spec->id,
            'plan' => 1,
            'payment_amount' => 5000,
            'service_amount' => 2500,
        ]);

        $store->assertRedirect(route('admin.enrollment.step2', Enrollment::query()->where('doctor_name', 'Dr. E2E Workflow')->value('id')));
        $enrollment = Enrollment::query()->where('doctor_name', 'Dr. E2E Workflow')->firstOrFail();
        $this->assertSame('approved', $enrollment->status);
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'doctor_name' => 'Dr. E2E Workflow',
            'workflow_status' => 'in_progress',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.enrollment.step2', $enrollment))
            ->assertOk();
    }

    public function test_sub_admin_sees_only_assigned_sidebar_modules(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $role = \App\Models\AdminRole::query()->create([
            'role_title' => 'Sales',
            'role_key' => 'sales',
        ]);

        $subAdminEmail = 'limited-' . uniqid() . '@example.com';

        $this->actingAs($superAdmin)->post(route('admin.admin-management.store'), [
            'first_name' => 'Limited',
            'last_name' => 'User',
            'email' => $subAdminEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'role_keys' => [$role->role_key],
            'sidebar_keys' => ['sidebar.doctor-management.doctor-list'],
        ])->assertRedirect(route('admin.admin-management.index'));

        $subAdmin = User::query()->where('email', $subAdminEmail)->firstOrFail();

        $this->assertDatabaseHas('admin_privileges', [
            'user_id' => $subAdmin->id,
            'page_key' => 'doctors',
            'action_key' => 'view',
            'is_allowed' => true,
        ]);

        $this->actingAs($subAdmin)
            ->get(route('admin.doctors.index'))
            ->assertOk();

        $this->actingAs($subAdmin)
            ->get(route('admin.enrollment.monitoring'))
            ->assertForbidden();
    }

    public function test_premium_and_cheque_deposit_listings_include_legacy_enrollments(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        Enrollment::query()->create([
            'legacy_user_id' => 99001,
            'customer_id_no' => 'E2E-PREMIUM-1',
            'doctor_name' => 'Dr. Premium E2E',
            'status' => 'approved',
            'workflow_status' => 'completed',
            'is_step_incomplete' => false,
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
            'policy_no' => 'POL-E2E-001',
            'payment_amount' => 8999,
            'doctor_money_reciept_no' => 1234,
            'doctor_money_reciept_year' => '2024',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.premium-amount.legacy-index'))
            ->assertOk()
            ->assertSee('Dr. Premium E2E')
            ->assertSee('POL-E2E-001');

        $this->actingAs($superAdmin)
            ->get(route('admin.receipts.enrollment-cheque-deposit'))
            ->assertOk()
            ->assertSee('Dr. Premium E2E')
            ->assertSee('1234');
    }

    /**
     * @param  list<string>  $actions
     */
    private function grantEnrollmentPrivileges(User $user, array $actions): void
    {
        foreach ($actions as $action) {
            AdminPrivilege::query()->updateOrCreate(
                ['user_id' => $user->id, 'page_key' => 'enrollment', 'action_key' => $action],
                [
                    'group_key' => 'back_office_management',
                    'group_title' => 'Back Office Management',
                    'page_title' => 'Enrollment Records',
                    'action_title' => ucfirst($action),
                    'is_allowed' => true,
                ]
            );
        }

        AdminPrivilege::query()->updateOrCreate(
            ['user_id' => $user->id, 'page_key' => 'enrollment-entry', 'action_key' => 'view'],
            [
                'group_key' => 'doctor_management',
                'group_title' => 'Doctor Management',
                'page_title' => 'Enrollment Entry',
                'action_title' => 'View',
                'is_allowed' => true,
            ]
        );
    }
}
