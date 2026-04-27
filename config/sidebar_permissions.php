<?php

return [
    [
        'key' => 'dashboard',
        'title' => 'Dashboard',
        'icon' => 'ri-dashboard-line',
        'route' => 'admin.dashboard',
        'route_names' => ['admin.dashboard'],
    ],
    [
        'key' => 'master-data-management',
        'title' => 'Master Data Management',
        'icon' => 'ri-layout-grid-line',
        'children' => [
            ['key' => 'specialization', 'title' => 'Specialization', 'icon' => 'ri-list-check-2', 'route' => 'admin.specialization', 'route_names' => ['admin.specialization']],
            ['key' => 'normal-plans', 'title' => 'Normal Plan', 'icon' => 'ri-list-check-2', 'route' => 'admin.plans', 'route_names' => ['admin.plans']],
            ['key' => 'high-risk-plans', 'title' => 'High Risk Plan', 'icon' => 'ri-list-check-2', 'route' => 'admin.high-risk-plans', 'route_names' => ['admin.high-risk-plans']],
            ['key' => 'combo-plans', 'title' => 'Combo Plan', 'icon' => 'ri-list-check-2', 'route' => 'admin.combo-plans', 'route_names' => ['admin.combo-plans']],
            ['key' => 'insurance-plans', 'title' => 'Insurance Plan', 'icon' => 'ri-list-check-2', 'route' => 'admin.insurance-plans', 'route_names' => ['admin.insurance-plans']],
        ],
    ],
    [
        'key' => 'employee-management',
        'title' => 'Employee Management',
        'icon' => 'ri-user-settings-line',
        'children' => [
            ['key' => 'sub-admin-management', 'title' => 'Sub-Admin Management', 'icon' => 'ri-list-check-2', 'route' => 'admin.admin-management.index', 'route_names' => ['admin.admin-management.index', 'admin.admin-management.create', 'admin.admin-management.edit']],
            ['key' => 'role-management', 'title' => 'Role Management', 'icon' => 'ri-list-check-2', 'route' => 'admin.admin-management.roles', 'route_names' => ['admin.admin-management.roles']],
        ],
    ],
    [
        'key' => 'doctor-management',
        'title' => 'Doctor Management',
        'icon' => 'ri-stethoscope-line',
        'children' => [
            ['key' => 'enrollment-entry', 'title' => 'Enrollment Entry', 'icon' => 'ri-user-add-line', 'route' => 'admin.enrollment.create', 'route_names' => ['admin.enrollment.create']],
            ['key' => 'doctor-list', 'title' => 'Doctor List', 'icon' => 'ri-list-check-2', 'route' => 'admin.enrollment', 'route_names' => ['admin.enrollment', 'admin.enrollment.step2', 'admin.enrollment.step3', 'admin.enrollment.edit', 'admin.enrollment.legacy-update', 'admin.enrollment.legacy-edit', 'admin.enrollment.legacy-renewal']],
            ['key' => 'incomplete-docs', 'title' => 'Incomplete Docs', 'icon' => 'ri-file-warning-line', 'route' => 'admin.doctors.incomplete-documents', 'route_names' => ['admin.doctors.incomplete-documents']],
            ['key' => 'membership-nos', 'title' => 'Membership nos.', 'icon' => 'ri-list-check-2', 'route' => 'admin.doctors.membership-nos', 'route_names' => ['admin.doctors.membership-nos', 'admin.doctors.membership-nos.legacy', 'admin.doctors.membership-search.legacy']],
        ],
    ],
    [
        'key' => 'policy-management',
        'title' => 'Policy Management',
        'icon' => 'ri-file-list-3-line',
        'children' => [
            ['key' => 'policy-received', 'title' => 'Policy Received', 'icon' => 'ri-list-check-2', 'route' => 'admin.policy-receipt.index', 'route_names' => ['admin.policy-receipt.*']],
            ['key' => 'doctors-policy', 'title' => 'Doctors policy', 'icon' => 'ri-list-check-2', 'route' => 'admin.policy-receipt.doctors', 'route_names' => ['admin.policy-receipt.doctors']],
        ],
    ],
    [
        'key' => 'renew-doctor',
        'title' => 'Renew doctor',
        'icon' => 'ri-repeat-line',
        'children' => [
            ['key' => 'renew-doctor-list', 'title' => 'Renew doctor', 'icon' => 'ri-list-check-2', 'route' => 'admin.doctors.index', 'route_names' => ['admin.doctors.index']],
        ],
    ],
    [
        'key' => 'legal-case-management',
        'title' => 'Legal case management',
        'icon' => 'ri-scales-3-line',
        'children' => [
            ['key' => 'case-list', 'title' => 'Case List', 'icon' => 'ri-list-check-2', 'route' => 'admin.cases', 'route_names' => ['admin.cases', 'admin.cases.*']],
        ],
    ],
    [
        'key' => 'account-management',
        'title' => 'Account Management',
        'icon' => 'ri-bank-card-line',
        'children' => [
            ['key' => 'money-receipt', 'title' => 'Money Receipt', 'icon' => 'ri-list-check-2', 'route' => 'admin.receipts', 'route_names' => ['admin.receipts', 'admin.receipts.*', 'admin.receipts.legacy-*']],
            ['key' => 'premium-amount', 'title' => 'Premium Amount', 'icon' => 'ri-list-check-2', 'route' => 'admin.premium-amount.index', 'route_names' => ['admin.premium-amount.*']],
            ['key' => 'enrollment-cheque-deposit', 'title' => 'Enrollment cheque deposit', 'icon' => 'ri-list-check-2', 'route' => 'admin.receipts.enrollment-cheque-deposit', 'route_names' => ['admin.receipts.enrollment-cheque-deposit', 'admin.receipts.enrollment-cheque-deposit.csv', 'admin.receipts.legacy-enrollment-cheque-deposit*']],
            ['key' => 'renewal-cheque-deposit', 'title' => 'Renewal cheque deposit', 'icon' => 'ri-list-check-2', 'route' => 'admin.receipts.renewal-cheque-deposit', 'route_names' => ['admin.receipts.renewal-cheque-deposit', 'admin.receipts.renewal-cheque-deposit.csv', 'admin.receipts.legacy-renewal-cheque-deposit*']],
            ['key' => 'expense-category', 'title' => 'Manage expense category', 'icon' => 'ri-list-check-2', 'route' => 'admin.expense-categories.index', 'route_names' => ['admin.expense-categories.*']],
            ['key' => 'manage-expense', 'title' => 'Manage expense', 'icon' => 'ri-list-check-2', 'route' => 'admin.manage-expense.index', 'route_names' => ['admin.manage-expense.*']],
            ['key' => 'manage-salary', 'title' => 'Manage salary', 'icon' => 'ri-list-check-2', 'route' => 'admin.manage-salary.index', 'route_names' => ['admin.manage-salary.*', 'admin.manage-salary.legacy-*']],
            ['key' => 'office-expensions', 'title' => 'Office expensions', 'icon' => 'ri-list-check-2', 'route' => 'admin.office-expensions.index', 'route_names' => ['admin.office-expensions.*', 'admin.office-expensions.legacy-*']],
        ],
    ],
    [
        'key' => 'marketing',
        'title' => 'Marketing',
        'icon' => 'ri-megaphone-line',
        'children' => [
            ['key' => 'call-sheet', 'title' => 'Call sheet', 'icon' => 'ri-list-check-2', 'route' => 'admin.call-sheet.index', 'route_names' => ['admin.call-sheet.*']],
        ],
    ],
    [
        'key' => 'dispatched-post',
        'title' => 'Dispatched post',
        'icon' => 'ri-mail-send-line',
        'children' => [
            ['key' => 'post-list', 'title' => 'Post List', 'icon' => 'ri-external-link-line', 'route' => 'admin.posts', 'route_names' => ['admin.posts*']],
        ],
    ],
    [
        'key' => 'bulk-upload',
        'title' => 'Bulk Upload',
        'icon' => 'ri-upload-cloud-2-line',
        'children' => [
            ['key' => 'bulk-upload-page', 'title' => 'Bulk Upload', 'icon' => 'ri-external-link-line', 'route' => 'admin.bulk-upload.index', 'route_names' => ['admin.bulk-upload.*']],
        ],
    ],
    [
        'key' => 'reports',
        'title' => 'Reports',
        'icon' => 'ri-bar-chart-2-line',
        'route' => 'admin.reports',
        'route_names' => ['admin.reports'],
    ],
];
