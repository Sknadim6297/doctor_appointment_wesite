<?php

namespace Tests\Feature;

use App\Models\AdminPrivilege;
use App\Models\AdminRole;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\AdminSecurityAlertNotification;
use App\Services\AdminAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_sub_admin_with_multiple_roles_and_permissions(): void
    {
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $salesRole = AdminRole::create([
            'role_title' => 'Sales',
            'role_key' => 'sales',
        ]);

        $supportRole = AdminRole::create([
            'role_title' => 'Support',
            'role_key' => 'support',
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.admin-management.store'), [
            'first_name' => 'Ava',
            'last_name' => 'Stone',
            'email' => 'ava@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'status' => 'active',
            'role_keys' => [$salesRole->role_key, $supportRole->role_key],
            'sidebar_keys' => ['sidebar.doctor-management.doctor-list'],
        ]);

        $response->assertRedirect(route('admin.admin-management.index'));

        $user = User::where('email', 'ava@example.com')->firstOrFail();

        $this->assertSame('sales', $user->role);
        $this->assertEqualsCanonicalizing(
            ['sales', 'support'],
            $user->roles()->pluck('role_key')->all()
        );

        $this->assertDatabaseHas('admin_privileges', [
            'user_id' => $user->id,
            'group_key' => 'sidebar',
            'page_key' => 'sidebar.doctor-management.doctor-list',
            'action_key' => 'view',
            'is_allowed' => true,
        ]);

        $this->assertDatabaseHas('admin_privileges', [
            'user_id' => $user->id,
            'page_key' => 'enrollment',
            'action_key' => 'view',
            'is_allowed' => true,
        ]);
    }

    public function test_sensitive_doctor_view_creates_activity_log_and_security_alert(): void
    {
        Notification::fake();

        $role = AdminRole::create([
            'role_title' => 'Executive',
            'role_key' => 'executive',
        ]);

        $owner = User::factory()->create([
            'name' => 'Owner User',
            'first_name' => 'Owner',
            'last_name' => 'User',
            'role' => $role->role_key,
            'is_active' => true,
        ]);

        $actor = User::factory()->create([
            'name' => 'Actor User',
            'first_name' => 'Actor',
            'last_name' => 'User',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $accessService = app(AdminAccessService::class);
        $accessService->syncRoles($owner, [$role->role_key]);
        $accessService->syncPrivilegeCatalogForUser($actor);
        AdminPrivilege::query()->updateOrCreate(
            ['user_id' => $actor->id, 'page_key' => 'doctors', 'action_key' => 'view'],
            [
                'group_key' => 'back_office_management',
                'group_title' => 'Back Office Management',
                'page_title' => 'Doctor Records',
                'action_title' => 'View',
                'is_allowed' => true,
            ]
        );

        $doctor = Enrollment::create([
            'doctor_name' => 'Dr. Asha',
            'customer_id_no' => 'MEM-1001',
            'created_by' => $owner->id,
        ]);

        $response = $this->actingAs($actor)->get(route('admin.doctors.show', $doctor));

        $response->assertOk();

        $this->assertDatabaseHas('admin_activity_logs', [
            'actor_user_id' => $actor->id,
            'owner_user_id' => $owner->id,
            'subject_id' => $doctor->id,
            'module_key' => 'doctors',
            'action' => 'view',
        ]);

        $this->assertDatabaseHas('admin_security_notifications', [
            'owner_user_id' => $owner->id,
            'actor_user_id' => $actor->id,
            'subject_id' => $doctor->id,
            'module_key' => 'doctors',
            'action' => 'view',
        ]);

        Notification::assertSentTo($owner, AdminSecurityAlertNotification::class);
    }

    public function test_employee_only_sees_own_enrollments_and_cannot_open_other_users_records(): void
    {
        $employee = User::factory()->create([
            'name' => 'Employee One',
            'first_name' => 'Employee',
            'last_name' => 'One',
            'role' => 'employee',
            'phone' => '9000000001',
            'is_active' => true,
        ]);

        $otherEmployee = User::factory()->create([
            'name' => 'Employee Two',
            'first_name' => 'Employee',
            'last_name' => 'Two',
            'role' => 'employee',
            'phone' => '9000000002',
            'is_active' => true,
        ]);

        AdminPrivilege::query()->updateOrCreate(
            ['user_id' => $employee->id, 'page_key' => 'enrollment', 'action_key' => 'view'],
            [
                'group_key' => 'back_office_management',
                'group_title' => 'Back Office Management',
                'page_title' => 'Enrollment Records',
                'action_title' => 'View',
                'is_allowed' => true,
            ]
        );

        $ownEnrollment = Enrollment::create([
            'customer_id_no' => 'MEM-2001',
            'doctor_name' => 'Dr. Own',
            'mobile1' => '9991111111',
            'agent_name' => 'Employee One',
            'agent_phone_no' => '9000000001',
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'status' => 'approved',
            'approved_by' => $employee->id,
            'approved_at' => now(),
        ]);

        $pendingOwnEnrollment = Enrollment::create([
            'customer_id_no' => 'MEM-2003',
            'doctor_name' => 'Dr. Pending Own',
            'mobile1' => '9993333333',
            'agent_name' => 'Employee One',
            'agent_phone_no' => '9000000001',
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'status' => 'pending',
        ]);

        $rejectedOwnEnrollment = Enrollment::create([
            'customer_id_no' => 'MEM-2004',
            'doctor_name' => 'Dr. Rejected Own',
            'mobile1' => '9994444444',
            'agent_name' => 'Employee One',
            'agent_phone_no' => '9000000001',
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'status' => 'rejected',
            'rejection_reason' => 'Incomplete documents.',
        ]);

        $otherEnrollment = Enrollment::create([
            'customer_id_no' => 'MEM-2002',
            'doctor_name' => 'Dr. Other',
            'mobile1' => '9992222222',
            'agent_name' => 'Employee Two',
            'agent_phone_no' => '9000000002',
            'created_by' => $otherEmployee->id,
            'agent_id' => $otherEmployee->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($employee)->get(route('admin.my-enrollments.index'));

        $response->assertOk();
        $response->assertSee('My Enrollments');
        $response->assertSee('MEM-2001');
        $response->assertSee('Approved');
        $response->assertDontSee('MEM-2002');
        $response->assertDontSee('Doctor List');
        $response->assertSeeHtml('title="View"');
        $response->assertDontSeeHtml('title="Edit"');

        $this->actingAs($employee)
            ->get(route('admin.my-enrollments.show', $ownEnrollment->id))
            ->assertOk()
            ->assertSee('Admin has approved your enrollment')
            ->assertSee('Proceed to Step 2');

        $this->actingAs($employee)
            ->get(route('admin.my-enrollments.show', $pendingOwnEnrollment->id))
            ->assertOk()
            ->assertSee('Waiting for Approval')
            ->assertSee('Step 2 is locked until approval')
            ->assertDontSee('Proceed to Step 2');

        $this->actingAs($employee)
            ->get(route('admin.my-enrollments.show', $rejectedOwnEnrollment->id))
            ->assertOk()
            ->assertSee('Rejected:')
            ->assertSee('This enrollment was rejected and remains locked')
            ->assertSee('Incomplete documents.')
            ->assertDontSee('Proceed to Step 2');

        $this->actingAs($employee)
            ->get(route('admin.enrollment.details', $otherEnrollment->id))
            ->assertForbidden();

        $this->assertDatabaseHas('enrollments', [
            'id' => $ownEnrollment->id,
            'status' => 'approved',
            'created_by' => $employee->id,
        ]);
    }

    public function test_admin_details_page_shows_review_actions_and_edit_link(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Reviewer',
            'first_name' => 'Admin',
            'last_name' => 'Reviewer',
            'role' => 'admin',
            'phone' => '9000000707',
            'is_active' => true,
        ]);

        $employee = User::factory()->create([
            'name' => 'Employee Reviewer',
            'first_name' => 'Employee',
            'last_name' => 'Reviewer',
            'role' => 'employee',
            'phone' => '9000000808',
            'is_active' => true,
        ]);

        $pendingEnrollment = Enrollment::create([
            'customer_id_no' => 'CID-ADMIN-9001',
            'doctor_name' => 'Admin Pending Doctor',
            'mobile1' => '9999009001',
            'agent_name' => $employee->name,
            'agent_phone_no' => $employee->phone,
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.enrollment.details', $pendingEnrollment->id));

        $response->assertOk();
        $response->assertSee('Approval Decision Required');
        $response->assertSee('Approve Enrollment');
        $response->assertSee('Reject Enrollment');
        $response->assertSee('Edit Enrollment');
        $response->assertSee('Waiting for Approval');
    }

    public function test_super_admin_enrollment_store_auto_approves_and_uses_logged_in_agent_details(): void
    {
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'role' => 'super_admin',
            'phone' => '9999999999',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.enrollment.store'), [
            'doctor_name' => 'Dr. Auto Approved',
            'mobile2' => '8888888888',
            'aadhar_card_no' => '123412341234',
            'pan_card_no' => 'ABCDE1234F',
            'agent_name' => 'Should Be Overridden',
            'agent_phone_no' => '0000000000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $enrollment = Enrollment::where('doctor_name', 'Dr. Auto Approved')->firstOrFail();

        $this->assertSame('approved', $enrollment->status);
        $this->assertSame($superAdmin->id, (int) $enrollment->approved_by);
        $this->assertSame('Super Admin', $enrollment->agent_name);
        $this->assertSame('9999999999', $enrollment->agent_phone_no);
        $this->assertSame($superAdmin->id, (int) $enrollment->created_by);
    }

    public function test_pending_approvals_filters_by_status_employee_month_date_and_search(): void
    {
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'role' => 'super_admin',
            'phone' => '9999999999',
            'is_active' => true,
        ]);

        $employeeOne = User::factory()->create([
            'name' => 'Filter Employee One',
            'first_name' => 'Filter',
            'last_name' => 'One',
            'role' => 'employee',
            'phone' => '9000000101',
            'is_active' => true,
        ]);

        $employeeTwo = User::factory()->create([
            'name' => 'Filter Employee Two',
            'first_name' => 'Filter',
            'last_name' => 'Two',
            'role' => 'employee',
            'phone' => '9000000202',
            'is_active' => true,
        ]);

        $approvedRecord = Enrollment::create([
            'customer_id_no' => 'CID-APP-1001',
            'doctor_name' => 'Alpha Doctor',
            'mobile1' => '9991110001',
            'agent_name' => $employeeOne->name,
            'agent_phone_no' => $employeeOne->phone,
            'created_by' => $employeeOne->id,
            'agent_id' => $employeeOne->id,
            'status' => 'approved',
            'approved_by' => $superAdmin->id,
            'approved_at' => now(),
        ]);

        $pendingRecord = Enrollment::create([
            'customer_id_no' => 'CID-PEN-2002',
            'doctor_name' => 'Beta Doctor',
            'mobile1' => '9992220002',
            'agent_name' => $employeeTwo->name,
            'agent_phone_no' => $employeeTwo->phone,
            'created_by' => $employeeTwo->id,
            'agent_id' => $employeeTwo->id,
            'status' => 'pending',
        ]);

        DB::table('enrollments')->where('id', $approvedRecord->id)->update([
            'created_at' => now()->subDays(6),
            'updated_at' => now()->subDays(6),
        ]);

        DB::table('enrollments')->where('id', $pendingRecord->id)->update([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $approvedResponse = $this->actingAs($superAdmin)->get(route('admin.enrollment.pending', [
            'status' => 'approved',
            'employee_id' => $employeeOne->id,
            'search' => $employeeOne->phone,
        ]));

        $approvedResponse->assertOk();
        $approvedResponse->assertSee('CID-APP-1001');
        $approvedResponse->assertDontSee('CID-PEN-2002');

        $rangeResponse = $this->actingAs($superAdmin)->get(route('admin.enrollment.pending', [
            'status' => 'approved',
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->subDays(5)->toDateString(),
        ]));

        $rangeResponse->assertOk();
        $rangeResponse->assertSee('CID-APP-1001');
        $rangeResponse->assertDontSee('CID-PEN-2002');

        $monthResponse = $this->actingAs($superAdmin)->get(route('admin.enrollment.pending', [
            'status' => 'approved',
            'search_month' => now()->format('F'),
            'search_year' => now()->format('Y'),
            'search' => 'Alpha',
        ]));

        $monthResponse->assertOk();
        $monthResponse->assertSee('CID-APP-1001');
        $monthResponse->assertDontSee('CID-PEN-2002');
    }

    public function test_step_two_stays_locked_until_approval_then_unlocks(): void
    {
        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'role' => 'super_admin',
            'phone' => '9999999999',
            'is_active' => true,
        ]);

        $employee = User::factory()->create([
            'name' => 'Workflow Employee',
            'first_name' => 'Workflow',
            'last_name' => 'Employee',
            'role' => 'employee',
            'phone' => '9000000303',
            'is_active' => true,
        ]);

        AdminPrivilege::query()->updateOrCreate(
            ['user_id' => $employee->id, 'page_key' => 'enrollment', 'action_key' => 'view'],
            [
                'group_key' => 'back_office_management',
                'group_title' => 'Back Office Management',
                'page_title' => 'Enrollment Records',
                'action_title' => 'View',
                'is_allowed' => true,
            ]
        );

        AdminPrivilege::query()->updateOrCreate(
            ['user_id' => $employee->id, 'page_key' => 'enrollment', 'action_key' => 'edit'],
            [
                'group_key' => 'back_office_management',
                'group_title' => 'Back Office Management',
                'page_title' => 'Enrollment Records',
                'action_title' => 'Edit',
                'is_allowed' => true,
            ]
        );

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-LOCK-3003',
            'doctor_name' => 'Locked Doctor',
            'mobile1' => '9993330003',
            'agent_name' => $employee->name,
            'agent_phone_no' => $employee->phone,
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'created_by_role' => 'employee',
            'status' => 'pending',
            'workflow_status' => 'pending_approval',
            'current_step' => 1,
            'is_step_incomplete' => true,
            'completed_steps' => [],
        ]);

        $pendingStepResponse = $this->actingAs($employee)->get(route('admin.enrollment.step2', $enrollment));
        $pendingStepResponse->assertRedirect(route('admin.enrollment.details', $enrollment));
        $pendingStepResponse->assertSessionHas('info', 'This enrollment is pending approval and is available in read-only mode.');

        $this->actingAs($superAdmin)->post(route('admin.enrollment.approve', $enrollment->id), [
            'approval_remarks' => 'Approved for testing.',
        ])->assertRedirect(route('admin.enrollment.details', $enrollment->id));

        $approvedStepResponse = $this->actingAs($employee)->get(route('admin.enrollment.step2', $enrollment));
        $approvedStepResponse->assertOk();
        $approvedStepResponse->assertSee('Step 2 of doctor enrollment');
        $approvedStepResponse->assertSee('Continue to Step 3');

        $enrollment->refresh();
        $this->assertSame(2, (int) $enrollment->current_step);

        $continueResponse = $this->actingAs($employee)->post(route('admin.enrollment.step2.continue', $enrollment));
        $continueResponse->assertRedirect(route('admin.enrollment.step3', $enrollment));

        $enrollment->refresh();
        $this->assertSame(3, (int) $enrollment->current_step);

        $stepThreeResponse = $this->actingAs($employee)->get(route('admin.enrollment.step3', $enrollment));
        $stepThreeResponse->assertOk();
        $stepThreeResponse->assertSee('Step 3', false);
    }

    public function test_pending_actions_render_strictly_by_role_flags(): void
    {
        $employee = User::factory()->create([
            'name' => 'Employee User',
            'first_name' => 'Employee',
            'last_name' => 'User',
            'role' => 'employee',
            'phone' => '9000000404',
            'is_active' => true,
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
            'phone' => '9000000505',
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'role' => 'super_admin',
            'phone' => '9000000606',
            'is_active' => true,
        ]);

        $pendingEnrollment = Enrollment::create([
            'customer_id_no' => 'CID-PEND-5001',
            'doctor_name' => 'Pending Doctor',
            'mobile1' => '9995005001',
            'agent_name' => $employee->name,
            'agent_phone_no' => $employee->phone,
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'status' => 'pending',
        ]);

        $approvedEnrollment = Enrollment::create([
            'customer_id_no' => 'CID-APP-5002',
            'doctor_name' => 'Approved Doctor',
            'mobile1' => '9995005002',
            'agent_name' => $employee->name,
            'agent_phone_no' => $employee->phone,
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'status' => 'approved',
            'approved_by' => $superAdmin->id,
            'approved_at' => now()->subHour(),
        ]);

        $pendingPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            collect([$pendingEnrollment->load(['creator', 'approver'])]),
            1,
            25,
            1,
            ['path' => route('admin.enrollment.pending')]
        );

        $approvedPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            collect([$approvedEnrollment->load(['creator', 'approver'])]),
            1,
            25,
            1,
            ['path' => route('admin.enrollment.pending')]
        );

        $this->actingAs($employee);
        $employeeHtml = view('admin.enrollment.pending', [
            'enrollments' => $pendingPaginator,
            'employees' => collect(),
            'canApprove' => false,
            'canReject' => false,
            'canEdit' => true,
            'isSuperAdmin' => false,
            'isAdmin' => false,
        ])->render();

        $this->assertStringContainsString('View Details', $employeeHtml);
        $this->assertStringContainsString('title="Edit"', $employeeHtml);
        $this->assertStringNotContainsString('title="Approve"', $employeeHtml);
        $this->assertStringNotContainsString('title="Reject"', $employeeHtml);

        $noActionHtml = view('admin.enrollment.pending', [
            'enrollments' => $pendingPaginator,
            'employees' => collect(),
            'canApprove' => false,
            'canReject' => false,
            'canEdit' => false,
            'isSuperAdmin' => false,
            'isAdmin' => false,
        ])->render();

        $this->assertStringNotContainsString('Actions', $noActionHtml);
        $this->assertStringNotContainsString('title="Edit"', $noActionHtml);
        $this->assertStringNotContainsString('title="Approve"', $noActionHtml);
        $this->assertStringNotContainsString('title="Reject"', $noActionHtml);

        $this->actingAs($admin);
        $adminHtml = view('admin.enrollment.pending', [
            'enrollments' => $pendingPaginator,
            'employees' => collect(),
            'canApprove' => true,
            'canReject' => true,
            'canEdit' => true,
            'isSuperAdmin' => false,
            'isAdmin' => true,
        ])->render();

        $this->assertStringContainsString('View Details', $adminHtml);
        $this->assertStringContainsString('title="Edit"', $adminHtml);
        $this->assertStringContainsString('title="Approve"', $adminHtml);
        $this->assertStringContainsString('title="Reject"', $adminHtml);

        $this->actingAs($superAdmin);
        $superAdminHtml = view('admin.enrollment.pending', [
            'enrollments' => $approvedPaginator,
            'employees' => collect(),
            'canApprove' => true,
            'canReject' => true,
            'canEdit' => true,
            'isSuperAdmin' => true,
            'isAdmin' => true,
        ])->render();

        $this->assertStringContainsString('Approved', $superAdminHtml);
        $this->assertStringContainsString('By Super Admin', $superAdminHtml);
        $this->assertStringContainsString('Approved Doctor', $superAdminHtml);
        $this->assertStringNotContainsString('CID-PEND-5001', $superAdminHtml);
    }
}