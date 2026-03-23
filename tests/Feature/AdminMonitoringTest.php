<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\AdminSecurityAlertNotification;
use App\Services\AdminAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'privilege_keys' => ['enrollment:view', 'doctors:view', 'doctors:edit'],
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
            'page_key' => 'doctors',
            'action_key' => 'view',
            'is_allowed' => true,
        ]);

        $this->assertDatabaseHas('admin_privileges', [
            'user_id' => $user->id,
            'page_key' => 'doctors',
            'action_key' => 'delete',
            'is_allowed' => false,
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
            'role' => $role->role_key,
            'is_active' => true,
        ]);

        $accessService = app(AdminAccessService::class);
        $accessService->syncRoles($owner, [$role->role_key]);
        $accessService->syncRoles($actor, [$role->role_key]);
        $accessService->syncPrivilegeCatalogForUser($actor);

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
}