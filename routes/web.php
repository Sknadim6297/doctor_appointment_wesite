<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\SpecializationController;
use App\Http\Controllers\Admin\NormalPlanController;
use App\Http\Controllers\Admin\HighRiskPlanController;
use App\Http\Controllers\Admin\ComboPlanController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\InsurancePlanController;

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
        Route::get('enrollment', [EnrollmentController::class, 'index'])->name('enrollment');
        Route::get('enrollment/create', [EnrollmentController::class, 'create'])->name('enrollment.create');
        Route::post('enrollment', [EnrollmentController::class, 'store'])->name('enrollment.store');

        // Doctor Management
        Route::get('doctors', [DoctorController::class, 'index'])->name('doctors.index');
        Route::get('doctors/incomplete-documents', [DoctorController::class, 'incompleteDocuments'])->name('doctors.incomplete-documents');
        Route::get('doctors/csv-report', [DoctorController::class, 'csvReport'])->name('doctors.csv-report');
        Route::get('doctors/{doctor}', [DoctorController::class, 'show'])->name('doctors.show');
        Route::post('doctors/{doctor}/send-mail', [DoctorController::class, 'sendMail'])->name('doctors.send-mail');
        Route::post('doctors/{doctor}/send-sms', [DoctorController::class, 'sendSms'])->name('doctors.send-sms');
        Route::post('doctors/{doctor}/resend-bond', [DoctorController::class, 'resendBond'])->name('doctors.resend-bond');
        Route::post('doctors/{doctor}/resend-receipt', [DoctorController::class, 'resendMoneyReceipt'])->name('doctors.resend-receipt');
        Route::post('doctors/{doctor}/toggle-auto-email', [DoctorController::class, 'toggleAutoEmail'])->name('doctors.toggle-auto-email');
        Route::post('doctors/{doctor}/toggle-auto-sms', [DoctorController::class, 'toggleAutoSms'])->name('doctors.toggle-auto-sms');

        // AJAX: location lookups
        Route::get('ajax/states/{countryId}', [EnrollmentController::class, 'getStates'])->name('ajax.states');
        Route::get('ajax/cities/{stateId}', [EnrollmentController::class, 'getCities'])->name('ajax.cities');
        Route::get('ajax/coverage', [EnrollmentController::class, 'getCoverage'])->name('ajax.coverage');
        
        // Money Receipts
        Route::get('receipts', function () {
            return view('admin.receipts.index');
        })->name('receipts');
        
        // Doctor Cases
        Route::get('cases', function () {
            return view('admin.cases.index');
        })->name('cases');
        
        // Lapse List
        Route::get('lapse', function () {
            return view('admin.lapse.index');
        })->name('lapse');
        
        // Normal Plans
        Route::get('plans', [NormalPlanController::class, 'index'])->name('plans');
        Route::post('plans', [NormalPlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [NormalPlanController::class, 'update'])->name('plans.update');
        Route::delete('plans/{plan}', [NormalPlanController::class, 'destroy'])->name('plans.destroy');

        // High Risk Plans
        Route::get('high-risk-plans', [HighRiskPlanController::class, 'index'])->name('high-risk-plans');
        Route::post('high-risk-plans', [HighRiskPlanController::class, 'store'])->name('high-risk-plans.store');
        Route::put('high-risk-plans/{highRiskPlan}', [HighRiskPlanController::class, 'update'])->name('high-risk-plans.update');
        Route::delete('high-risk-plans/{highRiskPlan}', [HighRiskPlanController::class, 'destroy'])->name('high-risk-plans.destroy');

        // Combo Plans
        Route::get('combo-plans', [ComboPlanController::class, 'index'])->name('combo-plans');
        Route::post('combo-plans', [ComboPlanController::class, 'store'])->name('combo-plans.store');
        Route::put('combo-plans/{comboPlan}', [ComboPlanController::class, 'update'])->name('combo-plans.update');
        Route::delete('combo-plans/{comboPlan}', [ComboPlanController::class, 'destroy'])->name('combo-plans.destroy');

        // Insurance Plans
        Route::get('insurance-plans', [InsurancePlanController::class, 'index'])->name('insurance-plans');
        Route::post('insurance-plans', [InsurancePlanController::class, 'store'])->name('insurance-plans.store');
        Route::put('insurance-plans/{insurancePlan}', [InsurancePlanController::class, 'update'])->name('insurance-plans.update');
        Route::delete('insurance-plans/{insurancePlan}', [InsurancePlanController::class, 'destroy'])->name('insurance-plans.destroy');

        // Specialization
        Route::get('specialization', [SpecializationController::class, 'index'])->name('specialization');
        Route::post('specialization', [SpecializationController::class, 'store'])->name('specialization.store');
        Route::put('specialization/{specialization}', [SpecializationController::class, 'update'])->name('specialization.update');
        Route::delete('specialization/{specialization}', [SpecializationController::class, 'destroy'])->name('specialization.destroy');
        
        // Doctor Posts
        Route::get('posts', function () {
            return view('admin.posts.index');
        })->name('posts');
        
        // Reports
        Route::get('reports', function () {
            return view('admin.reports.index');
        })->name('reports');
        
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
            Route::put('admin-management/{admin}', [AdminManagementController::class, 'update'])->name('admin-management.update');
            Route::post('admin-management/{admin}/reset-password', [AdminManagementController::class, 'resetPassword'])->name('admin-management.reset-password');
            Route::delete('admin-management/{admin}', [AdminManagementController::class, 'destroy'])->name('admin-management.destroy');
        });
    });
});

