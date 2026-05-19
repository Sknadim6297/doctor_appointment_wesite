<?php

namespace Tests\Feature;

use App\Models\AdminPrivilege;
use App\Models\Enrollment;
use App\Models\Specialization;
use App\Models\User;
use App\Support\EnrollmentFormValidation;
use App\Support\EnrollmentWorkflow;
use App\Support\SecureFileUpload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EnrollmentFormSecurityTest extends TestCase
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

        AdminPrivilege::query()->updateOrCreate(
            ['user_id' => $employee->id, 'page_key' => 'enrollment-entry', 'action_key' => 'view'],
            [
                'group_key' => 'doctor_management',
                'group_title' => 'Doctor Management',
                'page_title' => 'Enrollment Entry',
                'action_title' => 'View',
                'is_allowed' => true,
            ]
        );

        return $employee;
    }

    public function test_aadhaar_must_be_twelve_digits(): void
    {
        $request = Request::create('/', 'POST', [
            'doctor_name' => 'Test Doctor',
            'mobile2' => '9876543210',
            'aadhar_card_no' => '12345',
            'pan_card_no' => 'ABCDE1234F',
            'medical_registration_no' => 'MED-001',
            'specialization_id' => 1,
            'plan' => 1,
        ]);

        $validator = EnrollmentFormValidation::make($request, null, false);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('aadhar_card_no', $validator->errors()->toArray());
    }

    public function test_pan_format_is_validated(): void
    {
        $request = Request::create('/', 'POST', [
            'doctor_name' => 'Test Doctor',
            'mobile2' => '9876543210',
            'aadhar_card_no' => '123456789012',
            'pan_card_no' => 'INVALIDPAN',
            'medical_registration_no' => 'MED-002',
            'specialization_id' => 1,
            'plan' => 1,
        ]);

        $validator = EnrollmentFormValidation::make($request, null, false);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('pan_card_no', $validator->errors()->toArray());
    }

    public function test_duplicate_aadhaar_is_rejected(): void
    {
        Enrollment::create([
            'customer_id_no' => 'CID-DUP-001',
            'doctor_name' => 'Existing',
            'aadhar_card_no' => '123456789012',
            'pan_card_no' => 'ABCDE1234F',
            'medical_registration_no' => 'MED-X',
            'status' => 'pending',
            'workflow_status' => EnrollmentWorkflow::DRAFT,
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $request = Request::create('/', 'POST', [
            'doctor_name' => 'New Doctor',
            'mobile2' => '9876543210',
            'aadhar_card_no' => '123456789012',
            'pan_card_no' => 'FGHIJ5678K',
            'medical_registration_no' => 'MED-Y',
            'specialization_id' => 1,
            'plan' => 1,
        ]);

        $validator = EnrollmentFormValidation::make($request, null, false);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('aadhar_card_no', $validator->errors()->toArray());
    }

    public function test_payment_proof_required_for_upi_not_cash(): void
    {
        $request = Request::create('/', 'POST', [
            'doctor_name' => 'Pay Test',
            'mobile2' => '9876543210',
            'aadhar_card_no' => '234567890123',
            'pan_card_no' => 'ABCDE1234F',
            'medical_registration_no' => 'MED-PAY',
            'specialization_id' => 1,
            'plan' => 1,
            'add_payment_details' => '1',
            'payment_method' => '3',
        ]);

        $validator = EnrollmentFormValidation::make($request, null, false);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('doc_payment_document', $validator->errors()->toArray());
    }

    public function test_dangerous_upload_extension_is_blocked(): void
    {
        $file = UploadedFile::fake()->create('malware.php', 100, 'application/x-php');

        $this->expectException(\InvalidArgumentException::class);
        SecureFileUpload::assertValid($file);
    }

    public function test_employee_cannot_access_another_users_enrollment(): void
    {
        $owner = $this->employeeWithEnrollmentPrivilege();
        $other = $this->employeeWithEnrollmentPrivilege();

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-ISO-001',
            'doctor_name' => 'Private',
            'created_by' => $owner->id,
            'agent_id' => $owner->id,
            'created_by_role' => 'employee',
            'status' => 'pending',
            'workflow_status' => EnrollmentWorkflow::PENDING_APPROVAL,
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $this->actingAs($other)
            ->get(route('admin.enrollment.details', $enrollment->id))
            ->assertForbidden();
    }

    public function test_employee_can_edit_own_draft_without_sensitive_otp(): void
    {
        $employee = $this->employeeWithEnrollmentPrivilege();

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-DRAFT-001',
            'doctor_name' => 'Draft Doctor',
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'created_by_role' => 'employee',
            'status' => 'pending',
            'workflow_status' => EnrollmentWorkflow::DRAFT,
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $this->actingAs($employee)
            ->get(route('admin.enrollment.edit', $enrollment->id))
            ->assertOk();

        $this->assertFalse(session()->has('sensitive_otp'));
    }

    public function test_rejected_enrollment_edit_requires_sensitive_otp_for_employee(): void
    {
        $employee = $this->employeeWithEnrollmentPrivilege();

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-OTP-001',
            'doctor_name' => 'Rejected Doctor',
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'created_by_role' => 'employee',
            'status' => 'rejected',
            'workflow_status' => EnrollmentWorkflow::REJECTED,
            'rejection_reason' => 'Invalid documents',
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $this->actingAs($employee)
            ->get(route('admin.enrollment.edit', $enrollment->id))
            ->assertRedirect();

        $this->assertTrue(session()->has('sensitive_otp'));
    }

    public function test_autosave_creates_draft_without_doctor_name(): void
    {
        $employee = $this->employeeWithEnrollmentPrivilege();

        $response = $this->actingAs($employee)
            ->postJson(route('admin.enrollment.autosave'), [
                'workflow_step' => 1,
                'workflow_enrollment_id' => '',
                'customer_id_no' => 'IND-AUTOSAVE-001',
                'country' => 101,
                'country_name' => 'India',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $enrollment = Enrollment::query()->find($response->json('enrollment_id'));
        $this->assertNotNull($enrollment);
        $this->assertSame('Draft enrollment', $enrollment->doctor_name);
        $this->assertSame(EnrollmentWorkflow::DRAFT, $enrollment->workflow_status);
    }

    public function test_duplicate_submission_is_blocked(): void
    {
        $employee = $this->employeeWithEnrollmentPrivilege();
        $spec = Specialization::query()->first() ?? Specialization::create(['name' => 'General']);

        $enrollment = Enrollment::create([
            'customer_id_no' => 'CID-SUB-001',
            'doctor_name' => 'Already Submitted',
            'mobile2' => '9876543210',
            'aadhar_card_no' => '345678901234',
            'pan_card_no' => 'ABCDE1234F',
            'medical_registration_no' => 'MED-SUB',
            'specialization_id' => $spec->id,
            'plan' => 1,
            'created_by' => $employee->id,
            'agent_id' => $employee->id,
            'created_by_role' => 'employee',
            'status' => 'pending',
            'workflow_status' => EnrollmentWorkflow::PENDING_APPROVAL,
            'submitted_at' => now(),
            'current_step' => 1,
            'is_step_incomplete' => true,
        ]);

        $this->actingAs($employee)
            ->post(route('admin.enrollment.store'), [
                'workflow_enrollment_id' => $enrollment->id,
                'doctor_name' => 'Already Submitted',
                'mobile2' => '9876543210',
                'aadhar_card_no' => '345678901234',
                'pan_card_no' => 'ABCDE1234F',
                'medical_registration_no' => 'MED-SUB',
                'specialization_id' => $spec->id,
                'plan' => 1,
            ])
            ->assertRedirect(route('admin.my-enrollments.show', $enrollment->id));
    }
}
