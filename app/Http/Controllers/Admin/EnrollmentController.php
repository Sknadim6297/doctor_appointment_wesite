<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\User;
use App\Models\ComboPlan;
use App\Models\Enrollment;
use App\Models\HighRiskPlan;
use App\Models\InsurancePlan;
use App\Models\NormalPlan;
use App\Models\Specialization;
use App\Services\AdminAccessService;
use App\Services\ActivityLogService;
use App\Services\DoctorDocumentService;
use App\Services\EnrollmentEditAccessService;
use App\Services\LocationService;
use App\Support\EnrollmentWorkflow;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly AdminAccessService $adminAccessService,
        private readonly EnrollmentEditAccessService $enrollmentEditAccessService,
        private readonly DoctorDocumentService $doctorDocumentService,
    )
    {
    }

    public function index()
    {
        $this->activityLogService->log(
            request(),
            'enrollment',
            'view',
            description: 'Viewed enrollment listing.',
            metadata: request()->only(['renew_type', 'search_month', 'search_year'])
        );

        $renewType = request('renew_type', 'upcoming_renewed');
        $searchMonth = request('search_month');
        $searchYear = request('search_year');
        $search = trim((string) request('search', ''));
        $status = trim((string) request('status', ''));
        $dateFrom = request('date_from');
        $dateTo = request('date_to');

        $query = $this->enrollmentListingQuery(Auth::user());
        $this->applyEnrollmentListFilters($query, request(), false);

        $enrollments = $query->paginate(20)->appends(request()->query());

        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];

        $currentYear = (int) date('Y');
        $years = range($currentYear + 10, 2006);

        return view('admin.enrollment.index', compact(
            'enrollments',
            'months',
            'years',
            'renewType',
            'searchMonth',
            'searchYear',
            'search',
            'status',
            'dateFrom',
            'dateTo'
        ));
    }

    public function myEnrollments()
    {
        $this->activityLogService->log(
            request(),
            'enrollment',
            'view_my_enrollments',
            description: 'Viewed my enrollments list.'
        );

        $query = $this->enrollmentListingQuery(Auth::user());
        $this->applyEnrollmentListFilters($query, request(), false);

        $enrollments = $query->paginate(20)->appends(request()->query());

        return view('admin.enrollment.my-enrollments', compact('enrollments'));
    }

    public function incompleteDocuments(Request $request)
    {
        $enrollments = $this->enrollmentListingQuery(Auth::user())
            ->where(function ($query) {
                $query->whereNull('aadhar_card_no')
                    ->orWhere('aadhar_card_no', '')
                    ->orWhereNull('pan_card_no')
                    ->orWhere('pan_card_no', '')
                    ->orWhereNull('medical_registration_no')
                    ->orWhere('medical_registration_no', '');
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.enrollment.index', [
            'enrollments' => $enrollments,
            'months' => [
                'January',
                'February',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December',
            ],
            'years' => range((int) date('Y') + 10, 2006),
            'renewType' => $request->input('renew_type', 'upcoming_renewed'),
            'searchMonth' => $request->input('search_month'),
            'searchYear' => $request->input('search_year'),
            'showIncompleteOnly' => true,
        ]);
    }

    public function csvReport(Request $request): StreamedResponse
    {
        $renewType = $request->input('renew_type', 'upcoming_renewed');
        $searchMonth = $request->input('search_month');
        $searchYear = $request->input('search_year');

        $query = $this->enrollmentListingQuery(Auth::user());
        $this->applyEnrollmentListFilters($query, $request, false);

        $fileName = 'doctor-renewal-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'SL No',
                'Name/Phone No',
                'Speciality & Plan',
                'Degree & Reg/Year',
                'Policy No/Membership No',
                'Insurance Cov/Legal Service',
                'Insurance amount',
                'Medeforum Amount',
                'Last Renewed DT',
                'Next Renewal DT',
                'Marketing staff name/Phone No.',
                'Auto email',
                'Auto SMS',
            ]);

            $slNo = 1;
            // Eager load specialization to avoid N+1 queries in loop
            foreach ($query->with('specialization')->cursor() as $enrollment) {
                $planLabel = match ((int) $enrollment->plan) {
                    1 => 'Normal',
                    2 => 'High Risk',
                    3 => 'Combo',
                    default => '',
                };

                fputcsv($output, [
                    $slNo,
                    trim(($enrollment->doctor_name ?? '') . ' / ' . ($enrollment->mobile1 ?? '')),
                    trim(($enrollment->specialization?->name ?? '') . ' / ' . $planLabel),
                    trim(($enrollment->qualification ?? '') . ' / ' . ($enrollment->medical_registration_no ?? '') . ' / ' . ($enrollment->year_of_reg ?? '')),
                    trim(($enrollment->money_rc_no ?? '') . ' / ' . ($enrollment->customer_id_no ?? '')),
                    trim('Coverage ID: ' . ($enrollment->coverage_id ?? '-') . ' / Legal Service: ' . ($enrollment->service_amount ?? '-')),
                    (string) $enrollment->payment_amount,
                    (string) $enrollment->service_amount,
                    optional($enrollment->created_at)->format('d-m-Y'),
                    optional($enrollment->created_at)->copy()->addYear()->format('d-m-Y'),
                    trim(($enrollment->agent_name ?? '') . ' / ' . ($enrollment->agent_phone_no ?? '')),
                    $enrollment->bond_to_mail ? 'Enabled' : 'Disabled',
                    !empty($enrollment->mobile1) ? 'Ready' : 'No Mobile',
                ]);

                $slNo++;
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function create()
    {
        $this->activityLogService->log(request(), 'enrollment', 'edit', description: 'Opened enrollment creation form.');

        $specializations = Specialization::orderBy('name')->get();
        $countries = LocationService::countries();

        $defaultCountryId = 101;
        $defaultStateId = 41;
        $defaultCityId = 5583;

        $selectedCountryId = (int) old('country', $defaultCountryId);
        $selectedStateId = (int) old('state', $defaultStateId);

        $states = LocationService::statesByCountry($selectedCountryId);
        $cities = LocationService::citiesByState($selectedStateId);

        $currentYear = (int) date('Y');
        $years = range($currentYear, 1950);
        $officeUseAgent = $this->currentUserAgentDetails(Auth::user());
        $officeUseAgentName = $officeUseAgent['name'];
        $officeUseAgentPhone = $officeUseAgent['phone'];
        $generatedCustomerId = old('customer_id_no', $this->generateCustomerId());
        $isSuperAdmin = $this->isSuperAdminUser(Auth::user());

        return view('admin.enrollment.create', compact(
            'specializations',
            'countries',
            'states',
            'cities',
            'years',
            'defaultCountryId',
            'defaultStateId',
            'defaultCityId',
            'officeUseAgentName',
            'officeUseAgentPhone',
            'generatedCustomerId',
            'isSuperAdmin'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedEnrollmentData($request);

        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdminUser($user);
        $agentDetails = $this->currentUserAgentDetails($user);
        $workflowEnrollmentId = (int) $request->input('workflow_enrollment_id', 0);
        $workflowDraft = $workflowEnrollmentId > 0 ? Enrollment::find($workflowEnrollmentId) : null;

        $validated['bond_to_mail'] = isset($validated['bond_to_mail']) && $validated['bond_to_mail'] === 'Y';
        $validated['created_by'] = $user?->id;
        $validated['agent_id'] = $user?->id;
        $validated['created_by_role'] = $this->resolveCreatorRole($user);
        $validated['agent_name'] = $agentDetails['name'];
        $validated['agent_phone_no'] = $agentDetails['phone'];
        $validated['current_step'] = 2;
        $validated['workflow_status'] = $isSuperAdmin ? EnrollmentWorkflow::IN_PROGRESS : EnrollmentWorkflow::PENDING_APPROVAL;
        $validated['is_step_incomplete'] = true;
        $validated['last_activity_at'] = now();
        $validated['completed_steps'] = [1];

        if ($isSuperAdmin) {
            $validated['status'] = 'approved';
            $validated['approved_by'] = $user?->id;
            $validated['approved_at'] = now();
        }

        if (empty($validated['customer_id_no'])) {
            $validated['customer_id_no'] = $this->generateCustomerId();
        }

        if (empty($validated['country_name']) && !empty($validated['country'])) {
            $countries = LocationService::countries();
            $validated['country_name'] = $countries[(int) $validated['country']] ?? null;
        }
        if (empty($validated['state_name']) && !empty($validated['state'])) {
            $states = LocationService::indiaStates();
            $validated['state_name'] = $states[(int) $validated['state']] ?? null;
        }
        if (empty($validated['city_name']) && !empty($validated['city'])) {
            $cities = LocationService::citiesByState((int) $validated['state'] ?? 0);
            $validated['city_name'] = $cities[(int) $validated['city']] ?? null;
        }

        $enrollment = null;
        $validated['draft_data'] = $this->mergeWorkflowDraftData($workflowDraft?->draft_data, 'step1', $validated);

        DB::transaction(function () use (&$enrollment, $validated, $workflowDraft, $user, $request, $isSuperAdmin): void {
            if ($workflowDraft) {
                $workflowDraft->fill($validated);
                $workflowDraft->save();
                $enrollment = $workflowDraft;
            } else {
                $enrollment = Enrollment::create(array_merge($validated, [
                    'status' => $isSuperAdmin ? 'approved' : 'pending',
                    'approved_by' => $isSuperAdmin ? $user?->id : null,
                    'approved_at' => $isSuperAdmin ? now() : null,
                ]));
            }

            $this->doctorDocumentService->persistEnrollmentStep1Documents($request, $enrollment);

            $this->activityLogService->log(
                $request,
                'enrollment',
                'create-draft',
                $enrollment,
                $user,
                'Created or updated a workflow draft and moved it to the next step.',
                [
                    'doctor_name' => $enrollment->doctor_name,
                    'membership_no' => $enrollment->customer_id_no,
                    'status' => $enrollment->status,
                    'workflow_status' => $enrollment->workflow_status,
                    'current_step' => $enrollment->current_step,
                ]
            );
        });

        return redirect()
            ->route('admin.enrollment.step2', $enrollment)
            ->with('success', $isSuperAdmin ? 'Enrollment saved successfully.' : 'Enrollment draft saved. Continue with the workflow from the same step.');
    }

    /**
     * Show the form for editing the specified enrollment.
     */
    public function edit($id)
    {
        $enrollment = Enrollment::query()
            ->with(['doctorDocuments' => fn ($q) => $q->where('is_active', true)->with('creator')->orderByDesc('id')])
            ->findOrFail($id);
        $this->authorizeEnrollmentAccess($enrollment);

        if ($this->shouldForceReadOnly($enrollment)) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        if ($redirect = $this->enrollmentEditAccessService->assertMayPerformEdit(request(), $enrollment, Auth::user())) {
            return $redirect;
        }

        $this->doctorDocumentService->syncMissingWorkflowDocuments($enrollment);
        $enrollment->load(['doctorDocuments' => fn ($q) => $q->where('is_active', true)->with('creator')->orderByDesc('id')]);

        $specializations = Specialization::orderBy('name')->get();
        $countries = LocationService::countries();

        $defaultCountryId = $enrollment->country ?? 101;
        $defaultStateId = $enrollment->state ?? 41;
        $defaultCityId = $enrollment->city ?? 5583;

        $states = LocationService::statesByCountry($defaultCountryId);
        $cities = LocationService::citiesByState($defaultStateId);

        $currentYear = (int) date('Y');
        $years = range($currentYear, 1950);
        $officeUseAgent = $this->currentUserAgentDetails(Auth::user());
        $officeUseAgentName = $officeUseAgent['name'];
        $officeUseAgentPhone = $officeUseAgent['phone'];

        return view('admin.enrollment.create', compact(
            'specializations',
            'countries',
            'states',
            'cities',
            'years',
            'defaultCountryId',
            'defaultStateId',
            'defaultCityId',
            'officeUseAgentName',
            'officeUseAgentPhone'
        ))->with([
            'enrollment' => $enrollment,
            'submitRoute' => request()->routeIs('admin.enrollment.legacy-edit')
                ? route('admin.enrollment.legacy-update', $enrollment->id)
                : route('admin.enrollment.update', $enrollment->id),
            'isSuperAdmin' => $this->isSuperAdminUser(Auth::user()),
            'existingDocuments' => $enrollment->doctorDocuments,
            'documentCategoryLabels' => \App\Support\DoctorDocumentCatalog::categoryLabels(),
        ]);
    }

    /**
     * Update the specified enrollment in storage.
     */
    public function update(Request $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $this->authorizeEnrollmentAccess($enrollment);

        if ($this->shouldForceReadOnly($enrollment)) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and cannot be edited until it is approved.');
        }

        if ($redirect = $this->enrollmentEditAccessService->assertMayPerformEdit($request, $enrollment, Auth::user())) {
            return $redirect;
        }

        $validated = $this->validatedEnrollmentData($request, $enrollment);
        $validated['bond_to_mail'] = isset($validated['bond_to_mail']) && $validated['bond_to_mail'] === 'Y';

        $protectedEdit = !$this->isSuperAdminUser(Auth::user())
            && $this->enrollmentEditAccessService->requiresOtpGuard($enrollment);

        $before = $enrollment->only(array_keys($validated));

        $enrollment->update($validated);

        $this->doctorDocumentService->persistEnrollmentStep1Documents($request, $enrollment);

        $metadata = [
            'doctor_name' => $enrollment->doctor_name,
            'membership_no' => $enrollment->customer_id_no,
        ];

        if ($protectedEdit) {
            $changes = [];
            foreach ($validated as $key => $newVal) {
                $oldVal = $before[$key] ?? null;
                if ($oldVal != $enrollment->getAttribute($key)) {
                    $changes[$key] = [
                        'from' => $oldVal,
                        'to' => $enrollment->getAttribute($key),
                    ];
                }
            }
            $metadata['protected_edit_session'] = true;
            $metadata['field_changes'] = $changes;
        }

        $this->activityLogService->log(
            $request,
            'enrollment',
            'update',
            $enrollment,
            Auth::user(),
            'Updated an enrollment record.',
            $metadata
        );

        return redirect()->route('admin.enrollment')->with('success', 'Enrollment updated successfully.');
    }

    /**
     * Approve the enrollment (used by admins)
     */
    public function approve(Request $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $validated = $request->validate([
            'approval_remarks' => 'nullable|string|max:2000',
        ]);

        $enrollment->forceFill([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'approval_remarks' => $validated['approval_remarks'] ?? null,
            'rejection_reason' => null,
            'workflow_status' => EnrollmentWorkflow::IN_PROGRESS,
            'last_activity_at' => now(),
        ])->save();

        $this->activityLogService->log(
            $request,
            'enrollment',
            'approve',
            $enrollment,
            Auth::user(),
            'Approved an enrollment record.',
            [
                'approval_remarks' => $validated['approval_remarks'] ?? null,
                'doctor_name' => $enrollment->doctor_name,
                'membership_no' => $enrollment->customer_id_no,
            ]
        );

        return redirect()->route('admin.enrollment.pending')->with('success', 'Enrollment approved.');
    }

    /**
     * Reject the enrollment with a reason
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:2000',
            'approval_remarks' => 'nullable|string|max:2000',
        ]);

        $enrollment = Enrollment::findOrFail($id);
        $enrollment->forceFill([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'] ?? null,
            'approval_remarks' => $validated['approval_remarks'] ?? null,
            'workflow_status' => EnrollmentWorkflow::REJECTED,
            'last_activity_at' => now(),
        ])->save();

        $this->activityLogService->log(
            $request,
            'enrollment',
            'reject',
            $enrollment,
            Auth::user(),
            'Rejected an enrollment record.',
            [
                'rejection_reason' => $validated['rejection_reason'] ?? null,
                'approval_remarks' => $validated['approval_remarks'] ?? null,
                'doctor_name' => $enrollment->doctor_name,
                'membership_no' => $enrollment->customer_id_no,
            ]
        );

        return redirect()->route('admin.enrollment.pending')->with('success', 'Enrollment rejected.');
    }

    public function returnForCorrection(Request $request, $id)
    {
        $validated = $request->validate([
            'approval_remarks' => 'required|string|max:2000',
        ]);

        $enrollment = Enrollment::findOrFail($id);
        $enrollment->forceFill([
            'workflow_status' => EnrollmentWorkflow::RETURNED_FOR_CORRECTION,
            'approval_remarks' => $validated['approval_remarks'],
            'last_activity_at' => now(),
        ])->save();

        $this->activityLogService->log(
            $request,
            'enrollment',
            'return_for_correction',
            $enrollment,
            Auth::user(),
            'Returned enrollment for correction.',
            [
                'approval_remarks' => $validated['approval_remarks'],
                'doctor_name' => $enrollment->doctor_name,
                'membership_no' => $enrollment->customer_id_no,
            ]
        );

        return redirect()->route('admin.enrollment.pending')->with('success', 'Enrollment sent back for correction.');
    }

    /**
     * Enrollment monitoring workspace (CRM-style buckets).
     */
    public function monitoring(Request $request, ?string $bucket = null)
    {
        $this->activityLogService->log(
            $request,
            'enrollment',
            'view_monitoring',
            description: 'Viewed enrollment monitoring workspace.',
            metadata: ['bucket' => $bucket]
        );

        $bucket = $bucket ?: 'overview';
        $allowed = ['overview', 'new_entries', 'pending_approvals', 'incomplete', 'completed', 'rejected', 'returned'];

        if (!in_array($bucket, $allowed, true)) {
            $bucket = 'overview';
        }

        $base = $this->enrollmentMonitoringQuery(Auth::user());
        $counts = [
            'new_entries' => (clone $base)->tap(fn ($q) => EnrollmentWorkflow::scopeNewEntries($q))->count(),
            'pending_approvals' => (clone $base)->tap(fn ($q) => EnrollmentWorkflow::scopePendingAdminGate($q))->count(),
            'incomplete' => (clone $base)->tap(fn ($q) => EnrollmentWorkflow::scopeIncompletePipeline($q))->count(),
            'completed' => (clone $base)->productionReady()->count(),
            'rejected' => (clone $base)->tap(fn ($q) => EnrollmentWorkflow::scopeRejectedCases($q))->count(),
            'returned' => (clone $base)->tap(fn ($q) => EnrollmentWorkflow::scopeReturnedForCorrection($q))->count(),
        ];

        $query = $this->enrollmentMonitoringQuery(Auth::user());

        match ($bucket) {
            'new_entries' => EnrollmentWorkflow::scopeNewEntries($query),
            'pending_approvals' => EnrollmentWorkflow::scopePendingAdminGate($query),
            'incomplete' => EnrollmentWorkflow::scopeIncompletePipeline($query),
            'completed' => $query->productionReady(),
            'rejected' => EnrollmentWorkflow::scopeRejectedCases($query),
            'returned' => EnrollmentWorkflow::scopeReturnedForCorrection($query),
            default => null,
        };

        $this->applyEnrollmentListFilters($query, $request, false);

        if ($bucket === 'pending_approvals' && trim((string) $request->input('status', '')) === '') {
            $query->where('status', 'pending');
        }

        $enrollments = $query->paginate(25)->appends($request->query());

        $employees = User::query()
            ->where('role', '!=', 'super_admin')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        $currentUser = Auth::user();
        $isSuperAdmin = $this->isSuperAdminUser($currentUser);
        $isAdmin = (bool) ($currentUser && (
            (method_exists($currentUser, 'hasAdminRole') && $currentUser->hasAdminRole('admin')) ||
            (($currentUser->role ?? null) === 'admin')
        ));
        $canApprove = $isSuperAdmin || $isAdmin;
        $canReject = $canApprove;
        $canReturn = $canApprove;
        $canEdit = $isSuperAdmin || $isAdmin || $this->adminAccessService->hasPrivilege($currentUser, 'enrollment', 'edit');

        return view('admin.enrollment.monitoring', compact(
            'enrollments',
            'employees',
            'bucket',
            'counts',
            'canApprove',
            'canReject',
            'canReturn',
            'canEdit',
            'isSuperAdmin',
            'isAdmin'
        ));
    }

    /**
     * Pending items awaiting an approve / reject / return decision (subset of monitoring).
     */
    public function pending()
    {
        $this->activityLogService->log(request(), 'enrollment', 'view_pending', description: 'Viewed pending approvals list.');

        $query = $this->enrollmentMonitoringQuery(Auth::user());
        EnrollmentWorkflow::scopePendingAdminGate($query);
        $this->applyEnrollmentListFilters($query, request(), false);

        if (trim((string) request('status', '')) === '') {
            $query->where('status', 'pending');
        }

        $enrollments = $query->paginate(25)->appends(request()->query());

        $employees = User::query()
            ->where('role', '!=', 'super_admin')
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        // Compute current user's permissions once and pass to the view to avoid per-row inconsistencies
        $currentUser = Auth::user();
        $isSuperAdmin = $this->isSuperAdminUser($currentUser);
        $isAdmin = (bool) ($currentUser && (
            (method_exists($currentUser, 'hasAdminRole') && $currentUser->hasAdminRole('admin')) ||
            (($currentUser->role ?? null) === 'admin')
        ));

        // Approve/Reject should be restricted to Admins and Super Admins only
        // (do not show approval actions to regular employees even if a privilege record exists)
        $canApprove = $isSuperAdmin || $isAdmin;
        $canReject = $canApprove;
        $canReturn = $canApprove;

        $canEdit = $isSuperAdmin || $isAdmin || $this->adminAccessService->hasPrivilege($currentUser, 'enrollment', 'edit');

        return view('admin.enrollment.pending', compact('enrollments', 'employees', 'canApprove', 'canReject', 'canReturn', 'canEdit', 'isSuperAdmin', 'isAdmin'));
    }

    /**
     * Full enrollment dossier for admin monitoring (all steps, drafts, documents, timeline).
     */
    public function showDetails($id)
    {
        $this->activityLogService->log(
            request(),
            'enrollment',
            'view_details',
            description: 'Opened enrollment details / dossier.',
            metadata: ['enrollment_id' => (int) $id]
        );

        $enrollment = Enrollment::query()
            ->with(['specialization', 'creator', 'approver', 'agent', 'policyReceipts', 'doctorDocuments.creator', 'doctorDocuments.verifier'])
            ->findOrFail($id);

        $this->authorizeEnrollmentAccess($enrollment);

        $this->doctorDocumentService->syncMissingWorkflowDocuments($enrollment);

        $activityTimeline = AdminActivityLog::query()
            ->with(['actor:id,name,email,role', 'owner:id,name,email,role'])
            ->where('subject_type', Enrollment::class)
            ->where('subject_id', $enrollment->id)
            ->orderByDesc('occurred_at')
            ->limit(120)
            ->get()
            ->sortBy('occurred_at')
            ->values();

        $latestActivity = $activityTimeline->last();

        $workflowSteps = $this->buildWorkflowSteps($enrollment);

        $currentUser = Auth::user();
        $isSuperAdmin = $this->isSuperAdminUser($currentUser);
        $isPrivilegedAdmin = $this->isPrivilegedAdminUser($currentUser);
        $wf = $enrollment->normalizedWorkflowStatus();
        $atApprovalGate = $enrollment->status === 'pending'
            && $wf !== EnrollmentWorkflow::RETURNED_FOR_CORRECTION
            && (
                in_array($wf, EnrollmentWorkflow::gateStatuses(), true)
                || ($wf === EnrollmentWorkflow::IN_PROGRESS && (int) ($enrollment->current_step ?? 1) >= 2)
            );
        $canShowApprovalPanel = !$isSuperAdmin && $isPrivilegedAdmin && $atApprovalGate;
        $canReturnForCorrection = $canShowApprovalPanel;
        $editAccessState = $this->enrollmentEditAccessService->viewState($enrollment, $currentUser);
        $editPathLocked = ($editAccessState['locked'] ?? false) && !($editAccessState['session_active'] ?? false);
        $canResumeWorkflow = !$this->shouldForceReadOnly($enrollment)
            && $this->adminAccessService->hasPrivilege($currentUser, 'enrollment', 'edit')
            && !$editPathLocked;
        $bypassesApprovalWorkflow = $this->bypassesEnrollmentApprovalWorkflow($enrollment, $currentUser);
        $canProceedToStep2 = ($bypassesApprovalWorkflow || $enrollment->status === 'approved')
            && ((Auth::id() === (int) $enrollment->created_by) || $isPrivilegedAdmin || $isSuperAdmin)
            && ($bypassesApprovalWorkflow || !$editPathLocked);

        return view('admin.enrollment.details', compact(
            'enrollment',
            'latestActivity',
            'activityTimeline',
            'workflowSteps',
            'canShowApprovalPanel',
            'canReturnForCorrection',
            'canResumeWorkflow',
            'isPrivilegedAdmin',
            'isSuperAdmin',
            'bypassesApprovalWorkflow',
            'canProceedToStep2',
            'editAccessState'
        ));
    }

    public function myEnrollmentDetails($id)
    {
        $enrollment = Enrollment::query()
            ->with(['specialization', 'creator', 'approver', 'policyReceipts', 'doctorDocuments'])
            ->findOrFail($id);

        $this->authorizeEnrollmentAccess($enrollment);

        $latestActivity = AdminActivityLog::query()
            ->with(['actor:id,name,email', 'owner:id,name,email'])
            ->where('subject_type', Enrollment::class)
            ->where('subject_id', $enrollment->id)
            ->orderByDesc('occurred_at')
            ->first();

        $editAccessState = $this->enrollmentEditAccessService->viewState($enrollment, Auth::user());

        return view('admin.enrollment.my-enrollment-details', compact('enrollment', 'latestActivity', 'editAccessState'));
    }

    public function myEnrollmentPdf($id)
    {
        $enrollment = Enrollment::query()->with('specialization')->findOrFail($id);

        $this->authorizeEnrollmentAccess($enrollment);

        if ($enrollment->status !== 'approved' && !$this->bypassesEnrollmentApprovalWorkflow($enrollment, Auth::user())) {
            return redirect()
                ->route('admin.my-enrollments.show', $enrollment->id)
                ->with('info', 'PDF download is available after approval.');
        }

        $pdf = Pdf::loadView('admin.enrollment.pdf.step2', $this->legacyEnrollmentPdfData($enrollment))
            ->setPaper('a4', 'portrait');

        $storagePath = 'enrollment-pdfs/enrollment-' . $enrollment->id . '.pdf';
        Storage::disk('public')->put($storagePath, $pdf->output());

        $downloadName = 'enrollment-' . $enrollment->id . '-membership-certificate.pdf';

        return response()->download(
            Storage::disk('public')->path($storagePath),
            $downloadName,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function getUserRoleKey($user): string
    {
        return $this->resolveCreatorRole($user);
    }

    public function updateLegacy(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    /**
     * Generate a unique customer ID for a new enrollment.
     * Format: IND-YYYYMMDD-MMMSS-XXXX where MMMSS is milliseconds and XXXX is random
     */
    private function generateCustomerId(): string
    {
        $date = now()->format('Ymd');
        $microtime = str_pad((int)(microtime(true) * 10000) % 100000, 5, '0', STR_PAD_LEFT);
        $random = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        return "IND-{$date}-{$microtime}-{$random}";
    }

    private function workflowDraftPayload(Request $request, int $step): array
    {
        $step1Keys = [
            'customer_id_no', 'money_rc_no', 'agent_name', 'agent_phone_no', 'doctor_name', 'doctor_address',
            'country', 'country_name', 'state', 'state_name', 'city', 'city_name', 'postcode', 'mobile1', 'mobile2',
            'doctor_email', 'dob', 'qualification', 'qualification_names', 'qualification_years', 'qualification_year',
            'medical_registration_no', 'year_of_reg', 'clinic_address', 'aadhar_card_no', 'pan_card_no',
            'specialization_id', 'payment_mode', 'plan', 'plan_name', 'coverage_id', 'coverage', 'service_amount',
            'payment_amount', 'total_amount', 'bond_to_mail',
        ];

        $step3Keys = ['policy_no', 'last_renewed_date', 'policy_start_date', 'policy_end_date', 'rcv_date'];
        $step4Keys = ['post_doc_date', 'post_doc_consignment_no', 'post_doc_by', 'post_doc_recieved_date', 'post_doc_recieved_by', 'post_doc_remark', 'tracking_link'];

        $keys = match ($step) {
            3 => $step3Keys,
            4 => $step4Keys,
            default => $step1Keys,
        };

        $payload = Arr::only($request->all(), $keys);

        foreach (['dob', 'last_renewed_date', 'policy_start_date', 'policy_end_date', 'rcv_date', 'post_doc_date', 'post_doc_recieved_date'] as $dateKey) {
            if (array_key_exists($dateKey, $payload)) {
                $payload[$dateKey] = $this->normalizeWorkflowDate($payload[$dateKey]);
            }
        }

        $payload = $this->mergeQualificationFields($request, $payload);
        $payload['bond_to_mail'] = isset($payload['bond_to_mail']) && $payload['bond_to_mail'] === 'Y';

        return $payload;
    }

    private function normalizeWorkflowDate($value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mergeWorkflowDraftData($existingDraftData, string $stepKey, array $payload): array
    {
        $draftData = is_array($existingDraftData) ? $existingDraftData : [];
        $draftData[$stepKey] = $payload;

        return $draftData;
    }

    private function workflowResumeUrl(Enrollment $enrollment): string
    {
        $step = max(1, (int) ($enrollment->current_step ?: 1));

        return match ($step) {
            1 => route('admin.enrollment.edit', $enrollment->id),
            2 => route('admin.enrollment.step2', $enrollment),
            3 => route('admin.enrollment.step3', $enrollment),
            4 => route('admin.enrollment.step4', $enrollment),
            default => route('admin.enrollment.edit', $enrollment->id),
        };
    }

    private function markWorkflowStep(Enrollment $enrollment, int $step, string $workflowStatus, array $completedSteps): void
    {
        $enrollment->forceFill([
            'current_step' => $step,
            'workflow_status' => $workflowStatus,
            'is_step_incomplete' => true,
            'last_activity_at' => now(),
            'completed_steps' => array_values(array_unique($completedSteps)),
        ])->save();
    }

    private function buildWorkflowSteps(Enrollment $enrollment): array
    {
        $completedSteps = (array) ($enrollment->completed_steps ?? []);
        $currentStep = max(1, (int) ($enrollment->current_step ?: 1));

        return [
            ['step' => 1, 'label' => 'Enrollment Details', 'state' => in_array(1, $completedSteps, true) ? 'completed' : ($currentStep === 1 ? 'current' : 'pending')],
            ['step' => 2, 'label' => 'Preview', 'state' => in_array(2, $completedSteps, true) ? 'completed' : ($currentStep === 2 ? 'current' : 'pending')],
            ['step' => 3, 'label' => 'Policy Received', 'state' => in_array(3, $completedSteps, true) ? 'completed' : ($currentStep === 3 ? 'current' : 'pending')],
            ['step' => 4, 'label' => 'Post Submission', 'state' => in_array(4, $completedSteps, true) ? 'completed' : ($currentStep === 4 ? 'current' : 'pending')],
        ];
    }

    public function autosave(Request $request): JsonResponse
    {
        $step = max(1, (int) $request->input('workflow_step', 1));
        $enrollmentId = (int) $request->input('workflow_enrollment_id', 0);
        $enrollment = $enrollmentId > 0 ? Enrollment::find($enrollmentId) : null;

        if ($step > 1 && !$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Please save the enrollment first before continuing this workflow step.',
            ], 422);
        }

        $user = Auth::user();
        $isSuperAdmin = $this->isSuperAdminUser($user);

        if ($enrollment) {
            $this->authorizeEnrollmentAccess($enrollment);

            if (!$isSuperAdmin && $json = $this->enrollmentEditAccessService->assertMayPerformEditJson($request, $enrollment, $user)) {
                return $json;
            }
        }
        $agentDetails = $this->currentUserAgentDetails($user);
        $payload = $this->workflowDraftPayload($request, $step);

        DB::transaction(function () use (&$enrollment, $payload, $step, $user, $agentDetails, $request, $isSuperAdmin): void {
            if (!$enrollment) {
                $enrollment = Enrollment::create([
                    'customer_id_no' => $payload['customer_id_no'] ?? $this->generateCustomerId(),
                    'money_rc_no' => $payload['money_rc_no'] ?? null,
                    'doctor_name' => $payload['doctor_name'] ?? null,
                    'doctor_address' => $payload['doctor_address'] ?? null,
                    'country' => $payload['country'] ?? null,
                    'country_name' => $payload['country_name'] ?? null,
                    'state' => $payload['state'] ?? null,
                    'state_name' => $payload['state_name'] ?? null,
                    'city' => $payload['city'] ?? null,
                    'city_name' => $payload['city_name'] ?? null,
                    'postcode' => $payload['postcode'] ?? null,
                    'mobile1' => $payload['mobile1'] ?? null,
                    'mobile2' => $payload['mobile2'] ?? null,
                    'doctor_email' => $payload['doctor_email'] ?? null,
                    'dob' => $payload['dob'] ?? null,
                    'qualification' => $payload['qualification'] ?? null,
                    'qualification_year' => $payload['qualification_year'] ?? [],
                    'medical_registration_no' => $payload['medical_registration_no'] ?? null,
                    'year_of_reg' => $payload['year_of_reg'] ?? null,
                    'clinic_address' => $payload['clinic_address'] ?? null,
                    'aadhar_card_no' => $payload['aadhar_card_no'] ?? null,
                    'pan_card_no' => $payload['pan_card_no'] ?? null,
                    'specialization_id' => $payload['specialization_id'] ?? null,
                    'payment_mode' => $payload['payment_mode'] ?? null,
                    'plan' => $payload['plan'] ?? null,
                    'plan_name' => $payload['plan_name'] ?? null,
                    'coverage_id' => $payload['coverage_id'] ?? null,
                    'coverage' => $payload['coverage'] ?? null,
                    'service_amount' => $payload['service_amount'] ?? null,
                    'payment_amount' => $payload['payment_amount'] ?? null,
                    'total_amount' => $payload['total_amount'] ?? null,
                    'bond_to_mail' => $payload['bond_to_mail'] ?? false,
                    'created_by' => $user?->id,
                    'agent_id' => $user?->id,
                    'created_by_role' => $this->resolveCreatorRole($user),
                    'agent_name' => $agentDetails['name'],
                    'agent_phone_no' => $agentDetails['phone'],
                    'status' => $isSuperAdmin ? 'approved' : 'pending',
                    'workflow_status' => $isSuperAdmin ? EnrollmentWorkflow::IN_PROGRESS : 'draft',
                    'current_step' => $step,
                    'is_step_incomplete' => true,
                    'last_activity_at' => now(),
                    'completed_steps' => [],
                    'draft_data' => [],
                ]);
            }

            $draftData = $enrollment->draft_data ?? [];
            $draftData = $this->mergeWorkflowDraftData($draftData, 'step' . $step, $payload);

            $enrollment->fill([
                'workflow_status' => $isSuperAdmin
                    ? EnrollmentWorkflow::IN_PROGRESS
                    : ($step === 1 ? EnrollmentWorkflow::DRAFT : EnrollmentWorkflow::IN_PROGRESS),
                'current_step' => $step,
                'is_step_incomplete' => true,
                'last_activity_at' => now(),
                'draft_data' => $draftData,
            ]);

            if ($isSuperAdmin) {
                $enrollment->fill([
                    'status' => 'approved',
                    'approved_by' => $enrollment->approved_by ?? $user?->id,
                    'approved_at' => $enrollment->approved_at ?? now(),
                ]);
            }

            if ($step === 1) {
                $enrollment->fill(Arr::only($payload, [
                    'customer_id_no', 'money_rc_no', 'doctor_name', 'doctor_address', 'country', 'country_name',
                    'state', 'state_name', 'city', 'city_name', 'postcode', 'mobile1', 'mobile2', 'doctor_email',
                    'dob', 'qualification', 'qualification_year', 'medical_registration_no', 'year_of_reg',
                    'clinic_address', 'aadhar_card_no', 'pan_card_no', 'specialization_id', 'payment_mode', 'plan',
                    'plan_name', 'coverage_id', 'coverage', 'service_amount', 'payment_amount', 'total_amount',
                    'bond_to_mail',
                ]));
            }

            $enrollment->save();

        });

        return response()->json([
            'success' => true,
            'message' => 'Draft saved.',
            'enrollment_id' => $enrollment->id,
            'current_step' => $enrollment->current_step,
            'workflow_status' => $enrollment->workflow_status,
            'resume_url' => route('admin.enrollment.resume', $enrollment),
        ]);
    }

    public function resume(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($this->isSuperAdminUser(Auth::user())) {
            return redirect()->to($this->workflowResumeUrl($enrollment));
        }

        if ($this->shouldForceReadOnly($enrollment)) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        if ($redirect = $this->enrollmentEditAccessService->assertMayPerformEdit(request(), $enrollment, Auth::user())) {
            return $redirect;
        }

        return redirect()->to($this->workflowResumeUrl($enrollment));
    }

    public function stepTwo(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($redirect = $this->ensureEnrollmentStepsUnlocked($enrollment)) {
            return $redirect;
        }

        if ($this->isSuperAdminUser(Auth::user())) {
            $this->markWorkflowStep($enrollment, 2, $enrollment->workflow_status ?: EnrollmentWorkflow::IN_PROGRESS, [1]);

            return view('admin.enrollment.step2', [
                'enrollment' => $enrollment,
                'workflowSteps' => $this->buildWorkflowSteps($enrollment),
            ]);
        }

        if ($this->shouldForceReadOnly($enrollment)) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        if ($redirect = $this->enrollmentEditAccessService->assertMayPerformEdit(request(), $enrollment, Auth::user())) {
            return $redirect;
        }

        if ((int) ($enrollment->current_step ?? 1) < 2) {
            return redirect()->route('admin.enrollment.resume', $enrollment);
        }

        $this->markWorkflowStep($enrollment, 2, $enrollment->workflow_status ?: 'in_progress', [1]);

        return view('admin.enrollment.step2', [
            'enrollment' => $enrollment,
            'workflowSteps' => $this->buildWorkflowSteps($enrollment),
        ]);
    }

    public function downloadStepTwoPdf(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($enrollment->status !== 'approved' && !$this->isSuperAdminUser(Auth::user())) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        $enrollment->loadMissing('specialization');

        $pdf = Pdf::loadView('admin.enrollment.pdf.step2', $this->legacyEnrollmentPdfData($enrollment))
            ->setPaper('a4', 'portrait');

        $storagePath = 'enrollment-pdfs/enrollment-' . $enrollment->id . '.pdf';
        Storage::disk('public')->put($storagePath, $pdf->output());

        $downloadName = 'enrollment-' . $enrollment->id . '-membership-certificate.pdf';

        return response()->download(
            Storage::disk('public')->path($storagePath),
            $downloadName,
            ['Content-Type' => 'application/pdf']
        );
    }

    private function legacyEnrollmentPdfData(Enrollment $enrollment): array
    {
        $approvedAt = $enrollment->approved_at ?? $enrollment->created_at ?? now();
        $renewalDate = $enrollment->renewal_date ?? $approvedAt;
        $cashDate = $enrollment->payment_cash_date ?? $approvedAt;
        $qualificationYear = $enrollment->qualification_year;

        if (is_array($qualificationYear)) {
            $qualificationYear = implode(', ', array_filter($qualificationYear));
        }

        $qualification = $enrollment->qualification ?? 'N/A';
        if (is_array($qualification)) {
            $qualification = implode(', ', array_filter(array_map(function ($item) {
                if (is_array($item)) {
                    return (string) ($item['name'] ?? '');
                }

                return (string) $item;
            }, $qualification)));

            $qualification = $qualification !== '' ? $qualification : 'N/A';
        }

        $amount = (int) round((float) ($enrollment->total_amount ?? $enrollment->payment_amount ?? 0));
        $coverageId = (int) ($enrollment->coverage_id ?? 0);
        $planId = (int) ($enrollment->plan ?? 0);
        $planName = $enrollment->plan_name ?? 'N/A';
        $paymentMode = (string) ($enrollment->payment_mode ?? '');

        $coverageLabel = match ($planId) {
            1, 2 => 'INR' . $coverageId . '00000',
            3 => 'AS PER INSURANCE T/C',
            default => 'N/A',
        };

        return [
            'customer_id' => $enrollment->customer_id_no ?? $enrollment->id,
            'payment_mode' => $paymentMode,
            'payment_mode_label' => $paymentMode !== '' ? strtoupper(str_replace('_', ' ', $paymentMode)) : 'N/A',
            'enrollment_date' => $approvedAt,
            'renewal_date' => $renewalDate->copy()->subDay(),
            'doctor_name' => $enrollment->doctor_name ?? 'N/A',
            'doctor_mobile_no' => $enrollment->mobile1 ?? 'N/A',
            'doctor_address' => $enrollment->doctor_address ?: $enrollment->clinic_address ?: 'N/A',
            'state' => $enrollment->state_name ?: $enrollment->state ?: 'N/A',
            'city' => $enrollment->city_name ?: $enrollment->city ?: 'N/A',
            'postcode' => $enrollment->postcode ?? 'N/A',
            'agent_name' => $enrollment->agent_name ?? 'N/A',
            'agent_phone_no' => $enrollment->agent_phone_no ?? 'N/A',
            'plan_id' => $planId,
            'plan_name' => $planName,
            'coverage_id' => $coverageId,
            'coverage_label' => $coverageLabel,
            'membership_period' => $paymentMode,
            'medical_reg_no' => $enrollment->medical_registration_no ?? 'N/A',
            'year_of_reg' => $enrollment->year_of_reg ?? 'N/A',
            'speciliazition_name' => $enrollment->specialization?->name ?? 'N/A',
            'doctor_qualification' => $qualification,
            'doctor_qualification_year' => $qualificationYear ?: 'N/A',
            'recipet_no' => $enrollment->money_rc_no ?? $enrollment->customer_id_no ?? $enrollment->id,
            'payment_method' => (string) ($enrollment->payment_method ?? ''),
            'payment_method_label' => (string) ($enrollment->payment_method ?? '') === '1' ? 'CHEQUE' : 'CASH',
            'cheque_rec_date' => $approvedAt->format('Y-m-d'),
            'cash_rec_date' => $cashDate->format('Y-m-d'),
            'bank_name' => $enrollment->payment_bank_name ?? 'N/A',
            'branch_name' => $enrollment->payment_branch_name ?? 'N/A',
            'cheque_no' => $enrollment->payment_cheque ?? 'N/A',
            'amount' => $amount,
            'amount_words' => $this->numberToWords($amount),
            'generated_date' => now(),
        ];
    }

    private function numberToWords(int $number): string
    {
        $dictionary = [
            0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
            6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten', 11 => 'eleven',
            12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen',
            17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
            40 => 'forty', 50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty', 90 => 'ninety',
            100 => 'hundred', 1000 => 'thousand', 1000000 => 'million', 1000000000 => 'billion',
        ];

        if ($number < 0) {
            return 'negative ' . $this->numberToWords(abs($number));
        }

        if ($number < 21) {
            return $dictionary[$number];
        }

        if ($number < 100) {
            $tens = (int) (floor($number / 10) * 10);
            $units = $number % 10;

            return $dictionary[$tens] . ($units ? '-' . $dictionary[$units] : '');
        }

        if ($number < 1000) {
            $hundreds = (int) floor($number / 100);
            $remainder = $number % 100;

            return $dictionary[$hundreds] . ' hundred' . ($remainder ? ' and ' . $this->numberToWords($remainder) : '');
        }

        foreach ([1000000000, 1000000, 1000] as $baseUnit) {
            if ($number >= $baseUnit) {
                $numBaseUnits = (int) floor($number / $baseUnit);
                $remainder = $number % $baseUnit;

                return $this->numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit]
                    . ($remainder ? ($remainder < 100 ? ' and ' : ', ') . $this->numberToWords($remainder) : '');
            }
        }

        return (string) $number;
    }

    public function stepThree(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($redirect = $this->ensureEnrollmentStepsUnlocked($enrollment)) {
            return $redirect;
        }

        if ($this->isSuperAdminUser(Auth::user())) {
            $this->markWorkflowStep($enrollment, 3, $enrollment->workflow_status ?: EnrollmentWorkflow::IN_PROGRESS, [1, 2]);

            $policyReceipts = \App\Models\PolicyReceipt::where('enrollment_id', $enrollment->id)
                ->orderByDesc('id')
                ->get();

            return view('admin.enrollment.step3', [
                'enrollment' => $enrollment,
                'policyReceipts' => $policyReceipts,
                'workflowSteps' => $this->buildWorkflowSteps($enrollment),
                'draftStep3' => data_get($enrollment->draft_data, 'step3', []),
            ]);
        }

        if ($this->shouldForceReadOnly($enrollment)) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        if ($redirect = $this->enrollmentEditAccessService->assertMayPerformEdit(request(), $enrollment, Auth::user())) {
            return $redirect;
        }

        if ((int) ($enrollment->current_step ?? 1) < 3 && !$this->isSuperAdminUser(Auth::user())) {
            return redirect()->route('admin.enrollment.resume', $enrollment);
        }

        $this->markWorkflowStep($enrollment, 3, $enrollment->workflow_status ?: 'in_progress', [1, 2]);

        $policyReceipts = \App\Models\PolicyReceipt::where('enrollment_id', $enrollment->id)
            ->orderByDesc('id')
            ->get();

        return view('admin.enrollment.step3', [
            'enrollment' => $enrollment,
            'policyReceipts' => $policyReceipts,
            'workflowSteps' => $this->buildWorkflowSteps($enrollment),
            'draftStep3' => data_get($enrollment->draft_data, 'step3', []),
        ]);
    }

    public function stepFour(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($redirect = $this->ensureEnrollmentStepsUnlocked($enrollment)) {
            return $redirect;
        }

        if ($this->isSuperAdminUser(Auth::user())) {
            $this->markWorkflowStep($enrollment, 4, $enrollment->workflow_status ?: EnrollmentWorkflow::IN_PROGRESS, [1, 2, 3]);

            $policyReceipts = \App\Models\PolicyReceipt::where('enrollment_id', $enrollment->id)
                ->orderByDesc('id')
                ->get();

            return view('admin.enrollment.step4', [
                'enrollment' => $enrollment,
                'policyReceipts' => $policyReceipts,
                'workflowSteps' => $this->buildWorkflowSteps($enrollment),
                'draftStep4' => data_get($enrollment->draft_data, 'step4', []),
            ]);
        }

        if ($this->shouldForceReadOnly($enrollment)) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        if ($redirect = $this->enrollmentEditAccessService->assertMayPerformEdit(request(), $enrollment, Auth::user())) {
            return $redirect;
        }

        if ((int) ($enrollment->current_step ?? 1) < 4 && !$this->isSuperAdminUser(Auth::user())) {
            return redirect()->route('admin.enrollment.resume', $enrollment);
        }

        $this->markWorkflowStep($enrollment, 4, $enrollment->workflow_status ?: 'in_progress', [1, 2, 3]);

        $policyReceipts = \App\Models\PolicyReceipt::where('enrollment_id', $enrollment->id)
            ->orderByDesc('id')
            ->get();

        return view('admin.enrollment.step4', [
            'enrollment' => $enrollment,
            'policyReceipts' => $policyReceipts,
            'workflowSteps' => $this->buildWorkflowSteps($enrollment),
            'draftStep4' => data_get($enrollment->draft_data, 'step4', []),
        ]);
    }

    public function success(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($enrollment->status !== 'approved' && !$this->isSuperAdminUser(Auth::user())) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        $policyReceipts = \App\Models\PolicyReceipt::where('enrollment_id', $enrollment->id)
            ->orderByDesc('id')
            ->get();

        return view('admin.enrollment.success', compact('enrollment', 'policyReceipts'));
    }

    // ──────────────────────────── AJAX endpoints ─────────────────────────────

    public function getStates(int $countryId): JsonResponse
    {
        $states = LocationService::statesByCountry($countryId);

        $options = collect($states)->map(fn ($name, $id) => [
            'id'   => $id,
            'name' => $name,
        ])->values();

        return response()->json($options);
    }

    public function getCities(int $stateId): JsonResponse
    {
        $cities = LocationService::citiesByState($stateId);

        $options = collect($cities)->map(fn ($name, $id) => [
            'id'   => $id,
            'name' => $name,
        ])->values();

        return response()->json($options);
    }

    public function getCoverage(Request $request): JsonResponse
    {
        $planType    = (int) $request->input('plan', 0);
        $paymentMode = $request->input('payment_mode', '');
        $specializationId = (int) $request->input('specialization_id', 0);

        $multiplier = match ($paymentMode) {
            'Two Year'    => 2,
            'Three Year'  => 3,
            'Four Year'   => 4,
            'Five Year'   => 5,
            default       => 1, // One Year
        };

        $options = [];

        if ($planType === 1) {
            // Normal plans
            $plans = NormalPlan::orderBy('coverage_lakh')->get();
            foreach ($plans as $plan) {
                $amount    = round((float) $plan->yearly_amount * $multiplier, 2);
                $options[] = [
                    'id'     => $plan->id,
                    'name'   => $plan->coverage_lakh . ' Lakh',
                    'amount' => $amount,
                ];
            }
        } elseif ($planType === 2) {
            // High Risk plans
            $plans = HighRiskPlan::orderBy('coverage_lakh')->get();
            foreach ($plans as $plan) {
                $amount    = round((float) $plan->yearly_amount * $multiplier, 2);
                $options[] = [
                    'id'     => $plan->id,
                    'name'   => $plan->coverage_lakh . ' Lakh (High Risk)',
                    'amount' => $amount,
                ];
            }
        } elseif ($planType === 3) {
            // Combo plans
            $plans = ComboPlan::orderBy('coverage_lakh')->get();
            foreach ($plans as $plan) {
                $amount    = round((float) $plan->yearly_amount * $multiplier, 2);
                $options[] = [
                    'id'     => $plan->id,
                    'name'   => $plan->coverage_lakh . ' Lakh (Combo)',
                    'amount' => $amount,
                ];
            }
        }

        // Fallback to insurance plans when no explicit coverage exists in selected plan table.
        if (empty($options)) {
            $insurancePlans = InsurancePlan::query()
                ->when($specializationId > 0, function ($query) use ($specializationId) {
                    $query->where(function ($inner) use ($specializationId) {
                        $inner->whereJsonContains('specializations', (string) $specializationId)
                            ->orWhereJsonContains('specializations', $specializationId);
                    });
                })
                ->orderBy('id')
                ->get();

            foreach ($insurancePlans as $insurancePlan) {
                $amount = round((float) $insurancePlan->amount_per_lakh * $multiplier, 2);

                $options[] = [
                    'id' => $insurancePlan->id,
                    'name' => 'Insurance Plan #' . $insurancePlan->id,
                    'amount' => $amount,
                ];
            }
        }

        return response()->json($options);
    }

    private function validatedEnrollmentData(Request $request, ?Enrollment $enrollment = null): array
    {
        $validator = Validator::make($request->all(), [
            'customer_id_no'         => 'nullable|string|max:100',
            'money_rc_no'            => 'nullable|string|max:50',
            'agent_name'             => 'nullable|string|max:200',
            'agent_phone_no'         => 'nullable|string|max:20',
            'doctor_name'            => 'required|string|max:200',
            'doctor_address'         => 'nullable|string|max:500',
            'country'                => 'nullable|integer',
            'country_name'           => 'nullable|string|max:100',
            'state'                  => 'nullable|integer',
            'state_name'             => 'nullable|string|max:100',
            'city'                   => 'nullable|integer',
            'city_name'              => 'nullable|string|max:100',
            'postcode'               => 'nullable|string|max:20',
            'mobile1'                => 'nullable|string|max:20',
            'mobile2'                => 'required|string|max:20',
            'doctor_email'           => [
                'nullable',
                'email',
                'max:200',
                Rule::unique('enrollments', 'doctor_email')->ignore($enrollment?->id ?? 0),
            ],
            'dob'                    => 'nullable|string',
            'qualification_names'    => 'nullable|array',
            'qualification_names.*'  => 'nullable|string|max:200',
            'qualification_years'    => 'nullable|array',
            'qualification_years.*'  => 'nullable|integer|min:1950|max:' . ((int) date('Y') + 1),
            'qualification'          => 'nullable',
            'qualification_year'     => 'nullable|array',
            'qualification_year.*'   => 'nullable|integer',
            'medical_registration_no'=> 'nullable|string|max:100',
            'year_of_reg'            => 'nullable|integer',
            'clinic_address'         => 'nullable|string|max:500',
            'aadhar_card_no'         => 'required|string|max:20',
            'pan_card_no'            => 'required|string|max:20',
            'specialization_id'      => 'nullable|integer|exists:specializations,id',
            'payment_mode'           => 'nullable|string|max:50',
            'plan'                   => 'nullable|integer|in:1,2,3',
            'plan_name'              => 'nullable|string|max:50',
            'coverage_id'            => 'nullable|integer',
            'coverage'               => 'nullable|numeric|min:0',
            'service_amount'         => 'nullable|numeric|min:0',
            'payment_amount'         => 'nullable|numeric|min:0',
            'total_amount'           => 'nullable|numeric|min:0',
            'payment_method'         => 'nullable|integer|in:1,2,3',
            'payment_cheque'         => 'nullable|string|max:100',
            'payment_bank_name'      => 'nullable|string|max:200',
            'payment_branch_name'    => 'nullable|string|max:200',
            'payment_upi_transaction_id' => 'nullable|string|max:100',
            'payment_cash_date'      => 'nullable|date',
            'bond_to_mail'           => 'nullable|in:Y',
        ], [
            'doctor_email.unique' => 'This email already exists in the system. Please use a different email address.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $totalAmount = (float) $request->input('total_amount', 0);
            $serviceAmount = (float) $request->input('service_amount', 0);

            if ($serviceAmount > $totalAmount) {
                $validator->errors()->add('service_amount', 'Insurance amount cannot be greater than total amount.');
            }
        });

        $validated = $validator->validate();

        $validated = $this->mergeQualificationFields($request, $validated);

        if (!empty($validated['dob'])) {
            $validated['dob'] = $this->normalizeWorkflowDate($validated['dob']);
        }

        $totalAmount = (float) ($validated['total_amount'] ?? 0);
        $serviceAmount = (float) ($validated['service_amount'] ?? 0);
        $validated['payment_amount'] = round(max($totalAmount - $serviceAmount, 0), 2);

        unset($validated['qualification_names'], $validated['qualification_years']);

        return $validated;
    }

    private function mergeQualificationFields(Request $request, array $payload): array
    {
        if (!$request->has('qualification_names') && !$request->has('qualification_years')) {
            return $payload;
        }

        $names = array_values((array) $request->input('qualification_names', []));
        $years = array_values((array) $request->input('qualification_years', []));
        $qualifications = [];
        $count = max(count($names), count($years));

        for ($i = 0; $i < $count; $i++) {
            $name = trim((string) ($names[$i] ?? ''));
            $year = trim((string) ($years[$i] ?? ''));

            if ($name !== '' || $year !== '') {
                $qualifications[] = [
                    'name' => $name,
                    'year' => $year !== '' ? (int) $year : null,
                ];
            }
        }

        $payload['qualification'] = $qualifications;
        $payload['qualification_year'] = array_values(array_filter(
            array_map(fn (array $row) => $row['year'] ?? null, $qualifications),
            fn ($value) => $value !== null
        ));

        return $payload;
    }

    private function resolveCreatorRole($user): string
    {
        if ($this->isSuperAdminUser($user)) {
            return 'super_admin';
        }

        return (string) ($user?->role ?: 'agent');
    }

    private function isSuperAdminUser($user): bool
    {
        return (bool) ($user && (
            (method_exists($user, 'hasAdminRole') && $user->hasAdminRole('super_admin'))
            || (($user->role ?? null) === 'super_admin')
        ));
    }

    private function currentUserAgentDetails(?User $user): array
    {
        $name = trim((string) ($user?->name ?? ''));

        if ($name === '') {
            $name = trim((string) (($user?->first_name ?? '') . ' ' . ($user?->last_name ?? '')));
        }

        if ($name === '') {
            $name = $this->isSuperAdminUser($user) ? 'Super Admin' : 'Employee';
        }

        return [
            'name' => $name,
            'phone' => (string) ($user?->phone ?? ''),
        ];
    }

    private function enrollmentMonitoringQuery(?User $user): Builder
    {
        if ($this->isPrivilegedAdminUser($user) || $this->adminAccessService->hasPrivilege($user, 'enrollment', 'approve')) {
            return Enrollment::query()->with(['specialization', 'creator', 'approver'])->orderByDesc('id');
        }

        return $this->enrollmentListingQuery($user);
    }

    private function ensureEnrollmentStepsUnlocked(Enrollment $enrollment): ?RedirectResponse
    {
        $user = Auth::user();

        if ($this->bypassesEnrollmentApprovalWorkflow($enrollment, $user)) {
            return null;
        }

        if ($enrollment->status === 'approved') {
            return null;
        }

        return redirect()
            ->route('admin.enrollment.details', $enrollment->id)
            ->with('info', 'This enrollment must be approved by an administrator before continuing to the next workflow step.');
    }

    private function enrollmentListingQuery(?User $user): Builder
    {
        $query = Enrollment::query()->with(['specialization', 'creator', 'approver'])->orderByDesc('id');

        if (!$this->isSuperAdminUser($user) && $user?->id) {
            $query->where(function (Builder $builder) use ($user): void {
                $builder->where('created_by', $user->id)
                    ->orWhere('agent_id', $user->id);
            });
        }

        return $query;
    }

    private function applyEnrollmentListFilters(Builder $query, Request $request, bool $defaultPendingOnly): void
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $employeeId = (int) $request->input('employee_id', 0);
        $searchMonth = $request->input('search_month');
        $searchYear = $request->input('search_year');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $like = '%' . $search . '%';

                $builder->where('customer_id_no', 'like', $like)
                    ->orWhere('doctor_name', 'like', $like)
                    ->orWhere('mobile1', 'like', $like)
                    ->orWhere('agent_name', 'like', $like)
                    ->orWhere('agent_phone_no', 'like', $like);

                if (is_numeric($search)) {
                    $builder->orWhere('id', (int) $search);
                }
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        } elseif ($defaultPendingOnly) {
            $query->where('status', 'pending');
        }

        if ($employeeId > 0) {
            $query->where(function (Builder $builder) use ($employeeId): void {
                $builder->where('created_by', $employeeId)
                    ->orWhere('agent_id', $employeeId);
            });
        }

        if (!empty($searchMonth) && !empty($searchYear)) {
            $monthNumber = date('n', strtotime($searchMonth . ' 1'));
            $yearNumber = (int) $searchYear;

            $query->whereMonth('created_at', $monthNumber)
                ->whereYear('created_at', $yearNumber);
        }

        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }

    private function authorizeEnrollmentAccess(Enrollment $enrollment): void
    {
        if ($this->isPrivilegedAdminUser(Auth::user())) {
            return;
        }

        $userId = (int) Auth::id();

        if ($userId <= 0 || ((int) $enrollment->created_by !== $userId && (int) $enrollment->agent_id !== $userId)) {
            abort(403, 'You can only access your own enrollment records.');
        }
    }

    private function shouldForceReadOnly(Enrollment $enrollment): bool
    {
        if ($this->bypassesEnrollmentApprovalWorkflow($enrollment, Auth::user())) {
            return false;
        }

        $wf = EnrollmentWorkflow::normalize($enrollment->workflow_status);

        if ($enrollment->status === 'rejected' || $wf === EnrollmentWorkflow::REJECTED) {
            return true;
        }

        if ($wf === EnrollmentWorkflow::COMPLETED && !$enrollment->is_step_incomplete) {
            return true;
        }

        if (in_array($wf, EnrollmentWorkflow::gateStatuses(), true)) {
            return true;
        }

        if ($enrollment->status === 'pending'
            && $wf === EnrollmentWorkflow::IN_PROGRESS
            && (int) ($enrollment->current_step ?? 1) >= 2) {
            return true;
        }

        return false;
    }

    private function isPrivilegedAdminUser(?User $user): bool
    {
        return (bool) ($user && (
            in_array(($user->role ?? null), ['admin', 'super_admin'], true) ||
            (method_exists($user, 'hasAdminRole') && $user->hasAdminRole(['admin', 'super_admin']))
        ));
    }

    private function bypassesEnrollmentApprovalWorkflow(Enrollment $enrollment, ?User $user): bool
    {
        if ($this->isSuperAdminUser($user)) {
            return true;
        }

        if (($enrollment->created_by_role ?? '') === 'super_admin') {
            return true;
        }

        if ($enrollment->status === 'approved' && $enrollment->approved_by) {
            $approver = $enrollment->relationLoaded('approver')
                ? $enrollment->approver
                : User::query()->find($enrollment->approved_by);

            if ($this->isSuperAdminUser($approver)) {
                return true;
            }
        }

        return false;
    }
}
