<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\DoctorDocumentController;
use App\Http\Controllers\Admin\PolicyReceiptController;
use App\Http\Controllers\Admin\SpecializationController;
use App\Http\Controllers\Admin\NormalPlanController;
use App\Http\Controllers\Admin\HighRiskPlanController;
use App\Http\Controllers\Admin\ComboPlanController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\EnrollmentEditAccessController;
use App\Http\Controllers\Admin\InsurancePlanController;
use App\Http\Controllers\Admin\BulkUploadController;
use App\Http\Controllers\Admin\DoctorPostController;
use App\Http\Controllers\Admin\CallSheetController;
use App\Http\Controllers\Admin\CaseController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\SalaryController;
use App\Http\Controllers\Admin\OfficeExpenseController;
use App\Http\Controllers\Admin\JobApplicationController;
use App\Http\Controllers\Admin\SensitiveAccessOtpController;

use App\Http\Controllers\Admin\RenewalController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});


Route::get('/run-migrations', function () {
    Artisan::call('migrate', ['--force' => true]);
    return "Migrations ran successfully!";
});

Route::get('/linkstorage', function () {
    Artisan::call('storage:link');
    return 'Storage link created!';
});


// Admin Authentication Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes
    Route::middleware('guest')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');
    });

    // Authenticated admin routes
    Route::middleware('admin')->group(function () {
        // Temporary debug endpoints to inspect auth/permission state for troubleshooting
        Route::get('debug/user-state', function () {
            $user = request()->user() ?? \Illuminate\Support\Facades\Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Not logged in']);
            }

            $allRoleKeys = [];
            if (!empty($user->role)) {
                $allRoleKeys[] = $user->role;
            }
            foreach ($user->roles as $role) {
                $allRoleKeys[] = $role->role_key;
            }
            $allRoleKeys = array_values(array_unique($allRoleKeys));

            return response()->json([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_field' => $user->role ?? null,
                'is_active' => $user->is_active,
                'all_role_keys' => $allRoleKeys,
                'has_super_admin' => in_array('super_admin', $allRoleKeys, true),
            ]);
        })->name('admin.debug.user-state');

        Route::get('debug/permissions', function () {
            $user = request()->user() ?? \Illuminate\Support\Facades\Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Not logged in']);
            }

            $privileges = \App\Models\AdminPrivilege::where('user_id', $user->id)->get();

            return response()->json([
                'user_id' => $user->id,
                'total_permissions' => $privileges->count(),
                'permissions' => $privileges->map(fn($p) => [
                    'page_key' => $p->page_key,
                    'action_key' => $p->action_key,
                    'is_allowed' => $p->is_allowed,
                ])->all(),
            ]);
        })->name('admin.debug.permissions');

        Route::get('debug/pending-access', function () {
            $user = request()->user() ?? \Illuminate\Support\Facades\Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Not logged in']);
            }

            $allRoleKeys = [];
            if (!empty($user->role)) {
                $allRoleKeys[] = $user->role;
            }
            foreach ($user->roles as $role) {
                $allRoleKeys[] = $role->role_key;
            }
            $allRoleKeys = array_values(array_unique($allRoleKeys));

            $hasEnrollmentApprove = \App\Models\AdminPrivilege::where('user_id', $user->id)
                ->where('page_key', 'enrollment')
                ->where('action_key', 'approve')
                ->where('is_allowed', true)
                ->exists();

            return response()->json([
                'user_id' => $user->id,
                'role_field' => $user->role,
                'has_super_admin_role' => in_array('super_admin', $allRoleKeys, true),
                'has_enrollment_approve_permission' => $hasEnrollmentApprove,
            ]);
        })->name('admin.debug.pending-access');

        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('unread-count', [\App\Http\Controllers\Admin\WorkflowNotificationController::class, 'unreadCount'])->name('unread-count');
            Route::get('/', [\App\Http\Controllers\Admin\WorkflowNotificationController::class, 'index'])->name('index');
            Route::post('{notification}/read', [\App\Http\Controllers\Admin\WorkflowNotificationController::class, 'markRead'])->name('read');
            Route::post('read-all', [\App\Http\Controllers\Admin\WorkflowNotificationController::class, 'markAllRead'])->name('read-all');
        });

        Route::get('job-applications', [JobApplicationController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('job-applications.index');
        Route::delete('job-applications/{jobApplication}', [JobApplicationController::class, 'destroy'])->middleware('admin.privilege:receipts,delete')->name('job-applications.destroy');
        
        // Enrollment Management
        Route::get('enrollment', [EnrollmentController::class, 'index'])->middleware('admin.privilege:enrollment,view')->name('enrollment');
        // Employee-only enrollment tracking page
        Route::get('my-enrollments', [EnrollmentController::class, 'myEnrollments'])->middleware('admin.privilege:enrollment,view')->name('my-enrollments.index');
        Route::get('my-enrollments/{id}', [EnrollmentController::class, 'myEnrollmentDetails'])->middleware(['admin.privilege:enrollment,view', 'enrollment.access:id'])->name('my-enrollments.show');
        Route::get('my-enrollments/{id}/pdf', [EnrollmentController::class, 'myEnrollmentPdf'])->middleware('admin.privilege:enrollment,view')->name('my-enrollments.pdf');
        // NEW ENROLLMENT ENTRY - Protected for assigned sub-admins only
        Route::get('enrollment/create', [EnrollmentController::class, 'create'])
            ->middleware(['admin.privilege:enrollment,edit', 'sub-admin.access-control:enrollment-entry'])
            ->name('enrollment.create');
        Route::post('enrollment', [EnrollmentController::class, 'store'])
            ->middleware(['admin.privilege:enrollment,edit', 'sub-admin.access-control:enrollment-entry'])
            ->name('enrollment.store');
        Route::post('enrollment/autosave', [EnrollmentController::class, 'autosave'])
            ->middleware('admin.privilege:enrollment,edit')
            ->name('enrollment.autosave');
        // AJAX: field-level validation for enrollment form
        Route::post('enrollment/validate-field', [EnrollmentController::class, 'validateField'])
            ->middleware('admin.privilege:enrollment,edit')
            ->name('enrollment.validate-field');
        Route::post('enrollment/{enrollment}/edit-access/request', [EnrollmentEditAccessController::class, 'request'])
            ->middleware(['admin.privilege:enrollment,view', 'enrollment.access:enrollment'])
            ->name('enrollment.edit-access.request');
        Route::post('enrollment/{enrollment}/edit-access/verify', [EnrollmentEditAccessController::class, 'verify'])
            ->middleware(['admin.privilege:enrollment,view', 'enrollment.access:enrollment'])
            ->name('enrollment.edit-access.verify');
        Route::get('enrollment/{enrollment}/edit-access/status', [EnrollmentEditAccessController::class, 'status'])
            ->middleware(['admin.privilege:enrollment,view', 'enrollment.access:enrollment'])
            ->name('enrollment.edit-access.status');
        Route::get('enrollment/{enrollment}/resume', [EnrollmentController::class, 'resume'])
            ->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment'])
            ->name('enrollment.resume');
        // Edit / Update enrollment (doctor)
        Route::get('enrollment/{enrollment}/edit', [EnrollmentController::class, 'edit'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment', 'sensitive.otp:enrollment,enrollment,edit'])->name('enrollment.edit');
        Route::put('enrollment/{enrollment}', [EnrollmentController::class, 'update'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment'])->name('enrollment.update');
        Route::get('enrollment/{enrollment}/doctor-money-receipt/edit', [PolicyReceiptController::class, 'doctorMoneyReceiptEdit'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment'])->name('enrollment.doctor-money-receipt.edit');
        Route::post('enrollment/{enrollment}/doctor-money-receipt', [PolicyReceiptController::class, 'updateDoctorMoneyReceiptFromEnrollment'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment'])->name('enrollment.doctor-money-receipt.update');
        Route::get('admin/doctor/{doctor}/money-receipt/edit', [PolicyReceiptController::class, 'doctorMoneyReceiptEditForDoctor'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:doctor'])->name('admin.doctor.money-receipt.edit');
        Route::post('admin/doctor/{doctor}/money-receipt/legacy-update', [PolicyReceiptController::class, 'legacyUpdateDoctorMoneyReceipt'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:doctor'])->name('admin.doctor.money-receipt.legacy-update');
        Route::post('doctor_list/doctor_edit_action/{doctor}', [EnrollmentController::class, 'updateLegacy'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:doctor'])->name('enrollment.legacy-update');
        Route::post('index.php/doctor_list/doctor_edit_action/{doctor}', [EnrollmentController::class, 'updateLegacy'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:doctor']);
        Route::get('enrollment/{enrollment}/step-2', [EnrollmentController::class, 'stepTwo'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment', 'enrollment.workflow.approved:enrollment'])->name('enrollment.step2');
        Route::post('enrollment/{enrollment}/step-2/continue', [EnrollmentController::class, 'continueFromStepTwo'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment', 'enrollment.workflow.approved:enrollment'])->name('enrollment.step2.continue');
        Route::get('enrollment/{enrollment}/step-2/pdf', [EnrollmentController::class, 'downloadStepTwoPdf'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment', 'enrollment.workflow.approved:enrollment'])->name('enrollment.step2.pdf');
        Route::get('enrollment/{enrollment}/step-3', [EnrollmentController::class, 'stepThree'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment', 'enrollment.workflow.approved:enrollment'])->name('enrollment.step3');
        Route::post('enrollment/{enrollment}/policy-receipt', [PolicyReceiptController::class, 'storeForEnrollment'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment', 'enrollment.workflow.approved:enrollment'])->name('enrollment.policy-receipt.store');
        Route::get('enrollment/{enrollment}/step-4', [EnrollmentController::class, 'stepFour'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment', 'enrollment.workflow.approved:enrollment'])->name('enrollment.step4');
        Route::post('enrollment/{enrollment}/workflow-post', [DoctorPostController::class, 'storeForEnrollment'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment', 'enrollment.workflow.approved:enrollment'])->name('enrollment.workflow-post.store');
        Route::get('enrollment/{enrollment}/success', [EnrollmentController::class, 'success'])->middleware(['admin.privilege:enrollment,view', 'enrollment.access:enrollment'])->name('enrollment.success');

        // Approval workflow routes - use permission middleware instead of hardcoded super_admin role
        Route::middleware('admin.privilege:enrollment,approve')->group(function () {
            Route::get('enrollments/pending', [EnrollmentController::class, 'pending'])->name('enrollment.pending');
            Route::post('enrollments/{id}/approve', [EnrollmentController::class, 'approve'])->name('enrollment.approve');
            Route::post('enrollments/{id}/reject', [EnrollmentController::class, 'reject'])->name('enrollment.reject');
            Route::post('enrollments/{id}/return-for-correction', [EnrollmentController::class, 'returnForCorrection'])->name('enrollment.return-for-correction');
            Route::post('enrollments/{id}/hold', [EnrollmentController::class, 'hold'])->name('enrollment.hold');
            Route::post('enrollments/{id}/release-hold', [EnrollmentController::class, 'releaseHold'])->name('enrollment.release-hold');
        });
        Route::post('enrollment/{enrollment}/resubmit', [EnrollmentController::class, 'resubmit'])
            ->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:enrollment'])
            ->name('enrollment.resubmit');
        Route::get('enrollments/monitoring/{bucket?}', [EnrollmentController::class, 'monitoring'])
            ->middleware('admin.privilege:enrollment,view')
            ->name('enrollment.monitoring');
        Route::get('enrollments/{id}/details', [EnrollmentController::class, 'showDetails'])
            ->middleware(['admin.privilege:enrollment,view', 'enrollment.access:id'])
            ->name('enrollment.details');

        // Doctor Management
        Route::get('doctors', [DoctorController::class, 'index'])->middleware('admin.privilege:doctors,view')->name('doctors.index');
        Route::get('doctors/membership-nos', [DoctorController::class, 'membershipNumbers'])->middleware('admin.privilege:doctors,view')->name('doctors.membership-nos');
        Route::get('doctors/incomplete-documents', [DoctorController::class, 'incompleteDocuments'])->middleware('admin.privilege:doctors,view')->name('doctors.incomplete-documents');
        Route::get('doctors/csv-report', [DoctorController::class, 'csvReport'])->middleware('admin.privilege:doctors,view')->name('doctors.csv-report');
        Route::get('index.php/doctor_list/membership_nos', [DoctorController::class, 'membershipNumbers'])->middleware('admin.privilege:doctors,view')->name('doctors.membership-nos.legacy');
        Route::match(['get', 'post'], 'index.php/doctor_list/doctor_search', [DoctorController::class, 'membershipNumbers'])->middleware('admin.privilege:doctors,view')->name('doctors.membership-search.legacy');
        Route::get('doctors/{doctor}', [DoctorController::class, 'show'])->middleware(['admin.privilege:doctors,view', 'enrollment.access:doctor', 'sensitive.otp:enrollment,doctor,view'])->name('doctors.show');
        Route::post('doctors/{doctor}/documents', [DoctorDocumentController::class, 'storeForDoctor'])->middleware(['admin.privilege:doctors,edit', 'enrollment.access:doctor'])->name('doctors.documents.store');
        Route::get('doctors/{doctor}/documents/{document}/view', [DoctorDocumentController::class, 'view'])->middleware(['admin.privilege:doctors,view', 'enrollment.access:doctor', 'sensitive.otp:enrollment,doctor,documents'])->name('doctors.documents.view');
        Route::get('doctors/{doctor}/documents/{document}/download', [DoctorDocumentController::class, 'download'])->middleware(['admin.privilege:doctors,view', 'enrollment.access:doctor', 'sensitive.otp:enrollment,doctor,documents'])->name('doctors.documents.download');
        Route::post('doctors/{doctor}/documents/{document}/approve', [DoctorDocumentController::class, 'approve'])->middleware('admin.privilege:doctors,edit')->name('doctors.documents.approve');
        Route::post('doctors/{doctor}/documents/{document}/reject', [DoctorDocumentController::class, 'reject'])->middleware('admin.privilege:doctors,edit')->name('doctors.documents.reject');
        Route::post('doctors/{doctor}/documents/{document}/reupload', [DoctorDocumentController::class, 'reupload'])->middleware('admin.privilege:doctors,edit')->name('doctors.documents.reupload');
        Route::post('doctors/{doctor}/send-mail', [DoctorController::class, 'sendMail'])->middleware(['admin.privilege:doctors,edit', 'enrollment.access:doctor'])->name('doctors.send-mail');
        Route::post('doctors/{doctor}/send-sms', [DoctorController::class, 'sendSms'])->middleware(['admin.privilege:doctors,edit', 'enrollment.access:doctor'])->name('doctors.send-sms');
        Route::post('doctors/{doctor}/resend-bond', [DoctorController::class, 'resendBond'])->middleware(['admin.privilege:doctors,edit', 'enrollment.access:doctor'])->name('doctors.resend-bond');
        Route::post('doctors/{doctor}/resend-receipt', [DoctorController::class, 'resendMoneyReceipt'])->middleware(['admin.privilege:doctors,edit', 'enrollment.access:doctor'])->name('doctors.resend-receipt');
        Route::post('doctors/{doctor}/toggle-auto-email', [DoctorController::class, 'toggleAutoEmail'])->middleware(['admin.privilege:doctors,edit', 'enrollment.access:doctor'])->name('doctors.toggle-auto-email');
        Route::post('doctors/{doctor}/toggle-auto-sms', [DoctorController::class, 'toggleAutoSms'])->middleware(['admin.privilege:doctors,edit', 'enrollment.access:doctor'])->name('doctors.toggle-auto-sms');
        Route::post('sensitive-access/otp/request', [SensitiveAccessOtpController::class, 'requestOtp'])->name('sensitive-otp.request');
        Route::post('sensitive-access/otp/verify', [SensitiveAccessOtpController::class, 'verifyOtp'])->name('sensitive-otp.verify');

            // Renewal Enrollment
        Route::get('doctors/{doctor}/renewal', [RenewalController::class, 'show'])->middleware('admin.privilege:doctors,edit')->name('doctors.renewal');
        Route::post('doctors/{doctor}/renewal', [RenewalController::class, 'store'])->middleware('admin.privilege:doctors,edit')->name('doctors.renewal-store');
        // Legacy renewal enrollment URL pattern
        Route::get('index.php/renewal_list/renewal/{doctor}/{type}', [RenewalController::class, 'show'])->middleware('admin.privilege:doctors,edit')->name('doctors.renewal.legacy');

        // AJAX: location lookups
        Route::get('ajax/states/{countryId}', [EnrollmentController::class, 'getStates'])->middleware('admin.privilege:enrollment,edit')->name('ajax.states');
        Route::get('ajax/cities/{stateId}', [EnrollmentController::class, 'getCities'])->middleware('admin.privilege:enrollment,edit')->name('ajax.cities');
        Route::get('ajax/coverage', [EnrollmentController::class, 'getCoverage'])->middleware('admin.privilege:enrollment,edit')->name('ajax.coverage');
        
        // Policy Receipt
        Route::get('policy-receipt', [PolicyReceiptController::class, 'index'])->middleware('admin.privilege:policy_receipt,view')->name('policy-receipt.index');
        Route::get('policy-receipt/create', [PolicyReceiptController::class, 'create'])->middleware('admin.privilege:policy_receipt,edit')->name('policy-receipt.create');
        Route::get('index.php/premium_policy/add_policy_received/{doctor}', [PolicyReceiptController::class, 'createForDoctor'])->middleware('admin.privilege:policy_receipt,edit')->name('policy-receipt.legacy-create');
        Route::post('policy-receipt', [PolicyReceiptController::class, 'store'])->middleware('admin.privilege:policy_receipt,edit')->name('policy-receipt.store');
        Route::post('index.php/premium_policy/submit_receive_from_page/{doctor}', [PolicyReceiptController::class, 'storeForDoctor'])->middleware('admin.privilege:policy_receipt,edit')->name('policy-receipt.legacy-store');
        Route::get('policy-receipt/doctors', [PolicyReceiptController::class, 'doctors'])->middleware('admin.privilege:policy_receipt,view')->name('policy-receipt.doctors');
        Route::get('policy-receipt/{id}', [PolicyReceiptController::class, 'show'])->middleware('admin.privilege:policy_receipt,view')->name('policy-receipt.show');
        Route::get('policy-receipt/{id}/edit', [PolicyReceiptController::class, 'edit'])->middleware('admin.privilege:policy_receipt,edit')->name('policy-receipt.edit');
        Route::put('policy-receipt/{id}', [PolicyReceiptController::class, 'update'])->middleware('admin.privilege:policy_receipt,edit')->name('policy-receipt.update');
        Route::delete('policy-receipt/{id}', [PolicyReceiptController::class, 'destroy'])->middleware('admin.privilege:policy_receipt,delete')->name('policy-receipt.destroy');

        // Bulk Upload
        Route::get('bulk-upload', [BulkUploadController::class, 'index'])->middleware('admin.privilege:doctors,edit')->name('bulk-upload.index');
        Route::get('bulk-upload/template', [BulkUploadController::class, 'template'])->middleware('admin.privilege:doctors,edit')->name('bulk-upload.template');
        Route::post('bulk-upload', [BulkUploadController::class, 'store'])->middleware('admin.privilege:doctors,edit')->name('bulk-upload.store');
        Route::post('index.php/doctor_list/bulk_upload_action', [BulkUploadController::class, 'store'])->middleware('admin.privilege:doctors,edit')->name('bulk-upload.legacy-store');
        
        // Money Receipts
        Route::get('receipts', [DoctorController::class, 'receipts'])->middleware('admin.privilege:receipts,view')->name('receipts');
        Route::get('receipts/csv-report', [DoctorController::class, 'receiptsCsv'])->middleware('admin.privilege:receipts,view')->name('receipts.csv-report');
        Route::get('receipts/{receipt}/view', [DoctorController::class, 'receiptsView'])->middleware(['admin.privilege:receipts,view', 'sensitive.otp:enrollment,receipt'])->name('receipts.view');
        Route::get('receipts/{receipt}/json', [DoctorController::class, 'receiptsShowJson'])->middleware('admin.privilege:receipts,view')->name('receipts.json');
        Route::get('receipts/{receipt}/edit', [DoctorController::class, 'receiptsEdit'])->middleware('admin.privilege:receipts,edit')->name('receipts.edit');
        Route::put('receipts/{receipt}', [DoctorController::class, 'receiptsUpdate'])->middleware('admin.privilege:receipts,edit')->name('receipts.update');
        Route::post('receipts', [DoctorController::class, 'receiptsStore'])->middleware('admin.privilege:receipts,edit')->name('receipts.store');
        Route::get('receipts/doctor/{doctor}', [DoctorController::class, 'receiptDoctorDetails'])->middleware('admin.privilege:receipts,view')->name('receipts.doctor');
        Route::get('index.php/money_reciept', [DoctorController::class, 'receipts'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-index');
        Route::post('index.php/money_reciept/money_reciept_search', [DoctorController::class, 'receipts'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-search');
        Route::get('index.php/money_reciept/money_reciept_csv_report', [DoctorController::class, 'receiptsCsv'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-csv');
        Route::get('index.php/money_reciept/edit_money_reciept/{receipt}', [DoctorController::class, 'receiptsEdit'])->middleware('admin.privilege:receipts,edit')->name('receipts.legacy-edit');
        Route::post('index.php/money_reciept/edit_money_reciept_submit', [DoctorController::class, 'receiptsLegacyUpdate'])->middleware('admin.privilege:receipts,edit')->name('receipts.legacy-update');

        // Enrollment cheque deposit (legacy) - modernized view + CSV
        Route::get('receipts/enrollment-cheque-deposit', [DoctorController::class, 'enrollmentChequeDeposit'])->middleware('admin.privilege:receipts,view')->name('receipts.enrollment-cheque-deposit');
        Route::get('receipts/enrollment-cheque-deposit/csv', [DoctorController::class, 'enrollmentChequeDepositCsv'])->middleware('admin.privilege:receipts,view')->name('receipts.enrollment-cheque-deposit.csv');
        Route::get('index.php/money_reciept/enrollment_cheque_deposit', [DoctorController::class, 'enrollmentChequeDeposit'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-enrollment-cheque-deposit');
        Route::match(['get', 'post'], 'index.php/money_reciept/enrollment_cheque_deposit_search', [DoctorController::class, 'enrollmentChequeDeposit'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-enrollment-cheque-deposit-search');
        Route::get('index.php/money_reciept/enrollment_cheque_deposit_csv_report', [DoctorController::class, 'enrollmentChequeDepositCsv'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-enrollment-cheque-deposit-csv');

        // Renewal cheque deposit (legacy) - modernized view + CSV
        Route::get('receipts/renewal-cheque-deposit', [DoctorController::class, 'renewalChequeDeposit'])->middleware('admin.privilege:receipts,view')->name('receipts.renewal-cheque-deposit');
        Route::get('receipts/renewal-cheque-deposit/csv', [DoctorController::class, 'renewalChequeDepositCsv'])->middleware('admin.privilege:receipts,view')->name('receipts.renewal-cheque-deposit.csv');
        Route::post('receipts/renewal-cheque-deposit', [DoctorController::class, 'renewalChequeDepositStore'])->middleware('admin.privilege:receipts,edit')->name('receipts.renewal-cheque-deposit.store');
        Route::put('receipts/renewal-cheque-deposit/{receipt}', [DoctorController::class, 'renewalChequeDepositUpdate'])->middleware('admin.privilege:receipts,edit')->name('receipts.renewal-cheque-deposit.update');
        Route::delete('receipts/renewal-cheque-deposit/{receipt}', [DoctorController::class, 'renewalChequeDepositDestroy'])->middleware('admin.privilege:receipts,delete')->name('receipts.renewal-cheque-deposit.destroy');
        Route::get('receipts/renewal-cheque-deposit/{receipt}/json', [DoctorController::class, 'renewalChequeDepositShowJson'])->middleware('admin.privilege:receipts,view')->name('receipts.renewal-cheque-deposit.json');
        Route::get('receipts/renewal-cheque-deposit/get-membership-no', [DoctorController::class, 'getMembershipNo'])->middleware('admin.privilege:receipts,view')->name('receipts.renewal-cheque-deposit.membership-no');
        Route::get('index.php/money_reciept/renewal_cheque_deposit', [DoctorController::class, 'renewalChequeDeposit'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-renewal-cheque-deposit');
        Route::match(['get', 'post'], 'index.php/money_reciept/renewal_cheque_deposit_search', [DoctorController::class, 'renewalChequeDeposit'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-renewal-cheque-deposit-search');
        Route::get('index.php/money_reciept/renewal_cheque_deposit_csv_report', [DoctorController::class, 'renewalChequeDepositCsv'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-renewal-cheque-deposit-csv');
        Route::post('index.php/money_reciept/cheque_deposite_submit', [DoctorController::class, 'renewalChequeDepositStore'])->middleware('admin.privilege:receipts,edit')->name('receipts.legacy-renewal-cheque-deposit-store');
        Route::post('index.php/money_reciept/cheque_deposite_update/{receipt}', [DoctorController::class, 'renewalChequeDepositUpdate'])->middleware('admin.privilege:receipts,edit')->name('receipts.legacy-renewal-cheque-deposit-update');
        Route::post('index.php/money_reciept/delete_cheque_deposite/{receipt}', [DoctorController::class, 'renewalChequeDepositDestroy'])->middleware('admin.privilege:receipts,delete')->name('receipts.legacy-renewal-cheque-deposit-destroy');
        Route::get('index.php/money_reciept/get_membership_no', [DoctorController::class, 'getMembershipNo'])->middleware('admin.privilege:receipts,view')->name('receipts.legacy-renewal-cheque-deposit-membership-no');

        // Account Management
        Route::get('premium-amount', [DoctorController::class, 'premiumAmountIndex'])->middleware('admin.privilege:receipts,view')->name('premium-amount.index');
        Route::get('premium-amount/csv-report', [DoctorController::class, 'premiumAmountCsvReport'])->middleware('admin.privilege:receipts,view')->name('premium-amount.csv');
        Route::get('index.php/premium_amount', [DoctorController::class, 'premiumAmountIndex'])->middleware('admin.privilege:receipts,view')->name('premium-amount.legacy-index');
        Route::get('index.php/premium_amount/premium_amount_csv_report', [DoctorController::class, 'premiumAmountCsvReport'])->middleware('admin.privilege:receipts,view')->name('premium-amount.legacy-csv');
        Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('expense-categories.index');
        Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->middleware('admin.privilege:receipts,edit')->name('expense-categories.store');
        Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->middleware('admin.privilege:receipts,edit')->name('expense-categories.update');
        Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->middleware('admin.privilege:receipts,delete')->name('expense-categories.destroy');
        Route::get('index.php/money_reciept/manage_expense_category', [ExpenseCategoryController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('expense-categories.legacy-index');
        Route::post('index.php/money_reciept/add_expense_cat_action', [ExpenseCategoryController::class, 'store'])->middleware('admin.privilege:receipts,edit')->name('expense-categories.legacy-store');
        Route::post('index.php/money_reciept/edit_expense_cat_action/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->middleware('admin.privilege:receipts,edit')->name('expense-categories.legacy-update');
        Route::post('index.php/money_reciept/delete_expense_cat/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->middleware('admin.privilege:receipts,delete')->name('expense-categories.legacy-destroy');
        Route::get('manage-expense', [ExpenseController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('manage-expense.index');
        Route::get('manage-expense/csv-report', [ExpenseController::class, 'csvReport'])->middleware('admin.privilege:receipts,view')->name('manage-expense.csv');
        Route::post('manage-expense', [ExpenseController::class, 'store'])->middleware('admin.privilege:receipts,edit')->name('manage-expense.store');
        Route::put('manage-expense/{expense}', [ExpenseController::class, 'update'])->middleware('admin.privilege:receipts,edit')->name('manage-expense.update');
        Route::delete('manage-expense/{expense}', [ExpenseController::class, 'destroy'])->middleware('admin.privilege:receipts,delete')->name('manage-expense.destroy');
        Route::get('manage-expense/{expense}/json', [ExpenseController::class, 'showJson'])->middleware('admin.privilege:receipts,view')->name('manage-expense.json');
        Route::get('index.php/money_reciept/manage_expense', [ExpenseController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('manage-expense.legacy-index');
        Route::get('index.php/money_reciept/manage_expense_csv_report', [ExpenseController::class, 'csvReport'])->middleware('admin.privilege:receipts,view')->name('manage-expense.legacy-csv');
        Route::post('index.php/money_reciept/add_expense_action', [ExpenseController::class, 'store'])->middleware('admin.privilege:receipts,edit')->name('manage-expense.legacy-store');
        Route::post('index.php/money_reciept/edit_expense_action/{expense}', [ExpenseController::class, 'update'])->middleware('admin.privilege:receipts,edit')->name('manage-expense.legacy-update');
        Route::post('index.php/money_reciept/delete_expense/{expense}', [ExpenseController::class, 'destroy'])->middleware('admin.privilege:receipts,delete')->name('manage-expense.legacy-destroy');
        Route::get('manage-salary', [SalaryController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('manage-salary.index');
        Route::get('manage-salary/csv-report', [SalaryController::class, 'csvReport'])->middleware('admin.privilege:receipts,view')->name('manage-salary.csv');
        Route::post('manage-salary', [SalaryController::class, 'store'])->middleware('admin.privilege:receipts,edit')->name('manage-salary.store');
        Route::put('manage-salary/{salaryRecord}', [SalaryController::class, 'update'])->middleware('admin.privilege:receipts,edit')->name('manage-salary.update');
        Route::delete('manage-salary/{salaryRecord}', [SalaryController::class, 'destroy'])->middleware('admin.privilege:receipts,delete')->name('manage-salary.destroy');
        Route::get('manage-salary/{salaryRecord}/json', [SalaryController::class, 'showJson'])->middleware('admin.privilege:receipts,view')->name('manage-salary.json');
        Route::get('manage-salary/{salaryRecord}/view', [SalaryController::class, 'show'])->middleware('admin.privilege:receipts,view')->name('manage-salary.show');
        Route::get('manage-salary/{salaryRecord}/slip', [SalaryController::class, 'slip'])->middleware('admin.privilege:receipts,view')->name('manage-salary.slip');
        Route::get('index.php/manage_salary', [SalaryController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('manage-salary.legacy-index');
        Route::get('index.php/manage_salary/add_salary', [SalaryController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('manage-salary.legacy-add');
        Route::get('index.php/manage_salary/manage_salary_csv', [SalaryController::class, 'csvReport'])->middleware('admin.privilege:receipts,view')->name('manage-salary.legacy-csv');
        Route::post('index.php/manage_salary/add_salary_action', [SalaryController::class, 'store'])->middleware('admin.privilege:receipts,edit')->name('manage-salary.legacy-store');
        Route::post('index.php/manage_salary/edit_salary_action/{salaryRecord}', [SalaryController::class, 'update'])->middleware('admin.privilege:receipts,edit')->name('manage-salary.legacy-update');
        Route::post('index.php/manage_salary/delete_salary/{salaryRecord}', [SalaryController::class, 'destroy'])->middleware('admin.privilege:receipts,delete')->name('manage-salary.legacy-destroy');
        Route::get('index.php/manage_salary/edit_salary/{salaryRecord}', [SalaryController::class, 'show'])->middleware('admin.privilege:receipts,view')->name('manage-salary.legacy-edit');
        Route::get('index.php/manage_salary/salary_slip/{salaryRecord}', [SalaryController::class, 'slip'])->middleware('admin.privilege:receipts,view')->name('manage-salary.legacy-slip');
        Route::get('index.php/manage_salary/view_single_salary/{salaryRecord}', [SalaryController::class, 'show'])->middleware('admin.privilege:receipts,view')->name('manage-salary.legacy-view');
        Route::get('office-expensions', [OfficeExpenseController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('office-expensions.index');
        Route::get('office-expensions/csv-report', [OfficeExpenseController::class, 'csvReport'])->middleware('admin.privilege:receipts,view')->name('office-expensions.csv');
        Route::get('index.php/money_reciept/office_expensions', [OfficeExpenseController::class, 'index'])->middleware('admin.privilege:receipts,view')->name('office-expensions.legacy-index');
        Route::get('index.php/money_reciept/office_expensions_csv', [OfficeExpenseController::class, 'csvReport'])->middleware('admin.privilege:receipts,view')->name('office-expensions.legacy-csv');
        Route::get('doctor_list/edit_doctor/{doctor}', [EnrollmentController::class, 'edit'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:doctor', 'sensitive.otp:enrollment,doctor,edit'])->name('enrollment.legacy-edit');
        Route::get('index.php/doctor_list/edit_doctor/{doctor}', [EnrollmentController::class, 'edit'])->middleware(['admin.privilege:enrollment,edit', 'enrollment.access:doctor', 'sensitive.otp:enrollment,doctor,edit']);
        Route::get('index.php/renewal_list/renewal/{doctor}/{renewType?}', [EnrollmentController::class, 'edit'])->middleware('admin.privilege:enrollment,edit')->name('enrollment.legacy-renewal');
        
        // Doctor Cases
        Route::get('cases', [CaseController::class, 'index'])->middleware('admin.privilege:cases,view')->name('cases');
        Route::get('index.php/case_list', [CaseController::class, 'index'])->middleware('admin.privilege:cases,view')->name('cases.legacy-index');
        Route::post('cases', [CaseController::class, 'store'])->middleware('admin.privilege:cases,edit')->name('cases.store');
        Route::put('cases/{legalCase}', [CaseController::class, 'update'])->middleware('admin.privilege:cases,edit')->name('cases.update');
        Route::delete('cases/{legalCase}', [CaseController::class, 'destroy'])->middleware('admin.privilege:cases,delete')->name('cases.destroy');
        Route::get('cases/{legalCase}/json', [CaseController::class, 'showJson'])->middleware('admin.privilege:cases,view')->name('cases.json');
        Route::get('index.php/case_list/edit_case/{legalCase}', [CaseController::class, 'showJson'])->middleware('admin.privilege:cases,view')->name('cases.legacy-edit');
        Route::post('index.php/case_list/submit_case', [CaseController::class, 'store'])->middleware('admin.privilege:cases,edit')->name('cases.legacy-store');
        Route::post('index.php/case_list/edit_case_submit/{legalCase}', [CaseController::class, 'update'])->middleware('admin.privilege:cases,edit')->name('cases.legacy-update');
        Route::post('index.php/case_list/delete_case/{legalCase}', [CaseController::class, 'destroy'])->middleware('admin.privilege:cases,delete')->name('cases.legacy-destroy');
        
        // Lapse List
        Route::get('lapse', function () {
            return view('admin.lapse.index');
        })->middleware('admin.privilege:lapse,view')->name('lapse');
        
        // Normal Plans
        Route::get('plans', [NormalPlanController::class, 'index'])->middleware('admin.privilege:normal_plans,view')->name('plans');
        Route::post('plans', [NormalPlanController::class, 'store'])->middleware('admin.privilege:normal_plans,edit')->name('plans.store');
        Route::put('plans/{plan}', [NormalPlanController::class, 'update'])->middleware('admin.privilege:normal_plans,edit')->name('plans.update');
        Route::delete('plans/{plan}', [NormalPlanController::class, 'destroy'])->middleware('admin.privilege:normal_plans,delete')->name('plans.destroy');

        // High Risk Plans
        Route::get('high-risk-plans', [HighRiskPlanController::class, 'index'])->middleware('admin.privilege:high_risk_plans,view')->name('high-risk-plans');
        Route::post('high-risk-plans', [HighRiskPlanController::class, 'store'])->middleware('admin.privilege:high_risk_plans,edit')->name('high-risk-plans.store');
        Route::put('high-risk-plans/{highRiskPlan}', [HighRiskPlanController::class, 'update'])->middleware('admin.privilege:high_risk_plans,edit')->name('high-risk-plans.update');
        Route::delete('high-risk-plans/{highRiskPlan}', [HighRiskPlanController::class, 'destroy'])->middleware('admin.privilege:high_risk_plans,delete')->name('high-risk-plans.destroy');

        // Combo Plans
        Route::get('combo-plans', [ComboPlanController::class, 'index'])->middleware('admin.privilege:combo_plans,view')->name('combo-plans');
        Route::post('combo-plans', [ComboPlanController::class, 'store'])->middleware('admin.privilege:combo_plans,edit')->name('combo-plans.store');
        Route::put('combo-plans/{comboPlan}', [ComboPlanController::class, 'update'])->middleware('admin.privilege:combo_plans,edit')->name('combo-plans.update');
        Route::delete('combo-plans/{comboPlan}', [ComboPlanController::class, 'destroy'])->middleware('admin.privilege:combo_plans,delete')->name('combo-plans.destroy');

        // Insurance Plans
        Route::get('insurance-plans', [InsurancePlanController::class, 'index'])->middleware('admin.privilege:insurance_plans,view')->name('insurance-plans');
        Route::post('insurance-plans', [InsurancePlanController::class, 'store'])->middleware('admin.privilege:insurance_plans,edit')->name('insurance-plans.store');
        Route::put('insurance-plans/{insurancePlan}', [InsurancePlanController::class, 'update'])->middleware('admin.privilege:insurance_plans,edit')->name('insurance-plans.update');
        Route::delete('insurance-plans/{insurancePlan}', [InsurancePlanController::class, 'destroy'])->middleware('admin.privilege:insurance_plans,delete')->name('insurance-plans.destroy');

        // Specialization
        Route::get('specialization', [SpecializationController::class, 'index'])->middleware('admin.privilege:specializations,view')->name('specialization');
        Route::post('specialization', [SpecializationController::class, 'store'])->middleware('admin.privilege:specializations,edit')->name('specialization.store');
        Route::put('specialization/{specialization}', [SpecializationController::class, 'update'])->middleware('admin.privilege:specializations,edit')->name('specialization.update');
        Route::delete('specialization/{specialization}', [SpecializationController::class, 'destroy'])->middleware('admin.privilege:specializations,delete')->name('specialization.destroy');
        
        // Doctor Posts
        Route::get('posts', [DoctorPostController::class, 'index'])->middleware('admin.privilege:posts,view')->name('posts');
        Route::get('posts/{post}/edit', [DoctorPostController::class, 'edit'])->middleware('admin.privilege:posts,edit')->name('posts.edit');
        Route::post('posts', [DoctorPostController::class, 'store'])->middleware('admin.privilege:posts,edit')->name('posts.store');
        Route::put('posts/{post}', [DoctorPostController::class, 'update'])->middleware('admin.privilege:posts,edit')->name('posts.update');
        Route::delete('posts/{post}', [DoctorPostController::class, 'destroy'])->middleware('admin.privilege:posts,delete')->name('posts.destroy');

        // Marketing Call Sheet
        Route::get('call-sheet', [CallSheetController::class, 'index'])->middleware('admin.privilege:doctors,view')->name('call-sheet.index');
        Route::post('call-sheet', [CallSheetController::class, 'store'])->middleware('admin.privilege:doctors,edit')->name('call-sheet.store');
        Route::get('call-sheet/{callSheet}/edit', [CallSheetController::class, 'edit'])->middleware('admin.privilege:doctors,edit')->name('call-sheet.edit');
        Route::put('call-sheet/{callSheet}', [CallSheetController::class, 'update'])->middleware('admin.privilege:doctors,edit')->name('call-sheet.update');
        Route::delete('call-sheet/{callSheet}', [CallSheetController::class, 'destroy'])->middleware('admin.privilege:doctors,delete')->name('call-sheet.destroy');
        Route::get('call-sheet/{callSheet}/pdf', [CallSheetController::class, 'pdf'])->middleware('admin.privilege:doctors,view')->name('call-sheet.pdf');
        Route::get('call-sheet/{callSheet}/sms', [CallSheetController::class, 'sms'])->middleware('admin.privilege:doctors,edit')->name('call-sheet.sms');
        Route::get('call-sheet/csv', [CallSheetController::class, 'csv'])->middleware('admin.privilege:doctors,view')->name('call-sheet.csv');
        
        // Reports
        Route::get('reports', function () {
            return view('admin.reports.index');
        })->middleware('admin.privilege:reports,view')->name('reports');
        
        // Super Admin Only Routes
        Route::middleware('super_admin')->group(function () {
            Route::get('admin-management', [AdminManagementController::class, 'index'])->name('admin-management.index');
            Route::get('admin-management/roles', [AdminManagementController::class, 'roles'])->name('admin-management.roles');
            Route::post('admin-management/roles', [AdminManagementController::class, 'storeRole'])->name('admin-management.roles.store');
            Route::put('admin-management/roles/{role}', [AdminManagementController::class, 'updateRole'])->name('admin-management.roles.update');
            Route::get('admin-management/create', [AdminManagementController::class, 'create'])->name('admin-management.create');
            Route::post('admin-management', [AdminManagementController::class, 'store'])->name('admin-management.store');
            Route::get('admin-management/{admin}/edit', [AdminManagementController::class, 'edit'])->name('admin-management.edit');
            Route::get('admin-management/{admin}/privileges', [AdminManagementController::class, 'privileges'])->name('admin-management.privileges');
            Route::post('admin-management/{admin}/privileges', [AdminManagementController::class, 'updatePrivileges'])->name('admin-management.privileges.update');
            Route::get('admin-management/{admin}/login-log', [AdminManagementController::class, 'loginLog'])->name('admin-management.login-log');
            Route::get('admin-management/{admin}/activity-log', [AdminManagementController::class, 'activityLog'])->name('admin-management.activity-log');
            Route::put('admin-management/{admin}', [AdminManagementController::class, 'update'])->name('admin-management.update');
            Route::post('admin-management/{admin}/reset-password', [AdminManagementController::class, 'resetPassword'])->name('admin-management.reset-password');
            Route::delete('admin-management/{admin}', [AdminManagementController::class, 'destroy'])->name('admin-management.destroy');
        });
    });
});

// Debug routes for permission inspection and testing
require __DIR__ . '/admin-debug.php';

