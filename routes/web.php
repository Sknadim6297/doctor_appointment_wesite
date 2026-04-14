<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\PolicyReceiptController;
use App\Http\Controllers\Admin\SpecializationController;
use App\Http\Controllers\Admin\NormalPlanController;
use App\Http\Controllers\Admin\HighRiskPlanController;
use App\Http\Controllers\Admin\ComboPlanController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\InsurancePlanController;
use App\Http\Controllers\Admin\BulkUploadController;
use App\Http\Controllers\Admin\DoctorPostController;
use App\Http\Controllers\Admin\CallSheetController;

Route::get('/', function () {
    return redirect()->route('admin.login');
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
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        
        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Enrollment Management
        Route::get('enrollment', [EnrollmentController::class, 'index'])->middleware('admin.privilege:enrollment,view')->name('enrollment');
        Route::get('enrollment/create', [EnrollmentController::class, 'create'])->middleware('admin.privilege:enrollment,edit')->name('enrollment.create');
        Route::post('enrollment', [EnrollmentController::class, 'store'])->middleware('admin.privilege:enrollment,edit')->name('enrollment.store');
        // Edit / Update enrollment (doctor)
        Route::get('enrollment/{enrollment}/edit', [EnrollmentController::class, 'edit'])->middleware('admin.privilege:enrollment,edit')->name('enrollment.edit');
        Route::put('enrollment/{enrollment}', [EnrollmentController::class, 'update'])->middleware('admin.privilege:enrollment,edit')->name('enrollment.update');
        Route::get('enrollment/{enrollment}/step-2', [EnrollmentController::class, 'stepTwo'])->middleware('admin.privilege:enrollment,edit')->name('enrollment.step2');
        Route::get('enrollment/{enrollment}/step-3', [EnrollmentController::class, 'stepThree'])->middleware('admin.privilege:posts,edit')->name('enrollment.step3');

        // Doctor Management
        Route::get('doctors', [DoctorController::class, 'index'])->middleware('admin.privilege:doctors,view')->name('doctors.index');
        Route::get('doctors/incomplete-documents', [DoctorController::class, 'incompleteDocuments'])->middleware('admin.privilege:doctors,view')->name('doctors.incomplete-documents');
        Route::get('doctors/csv-report', [DoctorController::class, 'csvReport'])->middleware('admin.privilege:doctors,view')->name('doctors.csv-report');
        Route::get('doctors/{doctor}', [DoctorController::class, 'show'])->middleware('admin.privilege:doctors,view')->name('doctors.show');
        Route::post('doctors/{doctor}/send-mail', [DoctorController::class, 'sendMail'])->middleware('admin.privilege:doctors,edit')->name('doctors.send-mail');
        Route::post('doctors/{doctor}/send-sms', [DoctorController::class, 'sendSms'])->middleware('admin.privilege:doctors,edit')->name('doctors.send-sms');
        Route::post('doctors/{doctor}/resend-bond', [DoctorController::class, 'resendBond'])->middleware('admin.privilege:doctors,edit')->name('doctors.resend-bond');
        Route::post('doctors/{doctor}/resend-receipt', [DoctorController::class, 'resendMoneyReceipt'])->middleware('admin.privilege:doctors,edit')->name('doctors.resend-receipt');
        Route::post('doctors/{doctor}/toggle-auto-email', [DoctorController::class, 'toggleAutoEmail'])->middleware('admin.privilege:doctors,edit')->name('doctors.toggle-auto-email');
        Route::post('doctors/{doctor}/toggle-auto-sms', [DoctorController::class, 'toggleAutoSms'])->middleware('admin.privilege:doctors,edit')->name('doctors.toggle-auto-sms');

        // AJAX: location lookups
        Route::get('ajax/states/{countryId}', [EnrollmentController::class, 'getStates'])->middleware('admin.privilege:enrollment,edit')->name('ajax.states');
        Route::get('ajax/cities/{stateId}', [EnrollmentController::class, 'getCities'])->middleware('admin.privilege:enrollment,edit')->name('ajax.cities');
        Route::get('ajax/coverage', [EnrollmentController::class, 'getCoverage'])->middleware('admin.privilege:enrollment,edit')->name('ajax.coverage');
        
        // Policy Receipt
        Route::get('policy-receipt', [PolicyReceiptController::class, 'index'])->middleware('admin.privilege:policy_receipt,view')->name('policy-receipt.index');
        Route::get('policy-receipt/create', [PolicyReceiptController::class, 'create'])->middleware('admin.privilege:policy_receipt,edit')->name('policy-receipt.create');
        Route::post('policy-receipt', [PolicyReceiptController::class, 'store'])->middleware('admin.privilege:policy_receipt,edit')->name('policy-receipt.store');
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
        Route::get('receipts/{receipt}/view', [DoctorController::class, 'receiptsView'])->middleware('admin.privilege:receipts,view')->name('receipts.view');
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
        
        // Doctor Cases
        Route::get('cases', function () {
            return view('admin.cases.index');
        })->middleware('admin.privilege:cases,view')->name('cases');
        
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

