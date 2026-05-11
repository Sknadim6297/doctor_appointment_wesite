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
use App\Services\LocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
        private readonly AdminAccessService $adminAccessService,
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
            foreach ($query->cursor() as $enrollment) {
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

        $validated['bond_to_mail'] = isset($validated['bond_to_mail']) && $validated['bond_to_mail'] === 'Y';
        $validated['created_by'] = $user?->id;
        $validated['agent_id'] = $user?->id;
        $validated['created_by_role'] = $this->resolveCreatorRole($user);
        $validated['agent_name'] = $agentDetails['name'];
        $validated['agent_phone_no'] = $agentDetails['phone'];

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

        $status = $isSuperAdmin ? 'approved' : 'pending';
        $enrollment = null;

        DB::transaction(function () use (&$enrollment, $validated, $isSuperAdmin, $user, $request, $status): void {
            $enrollment = Enrollment::create(array_merge($validated, [
                'status' => $status,
                'approved_by' => $isSuperAdmin ? $user?->id : null,
                'approved_at' => $isSuperAdmin ? now() : null,
            ]));

            $this->activityLogService->log(
                $request,
                'enrollment',
                $status === 'approved' ? 'create-approved' : 'create-pending',
                $enrollment,
                $user,
                $status === 'approved'
                    ? 'Created a new enrollment record and approved it immediately.'
                    : 'Created a new enrollment record and sent it for approval.',
                [
                    'doctor_name' => $enrollment->doctor_name,
                    'membership_no' => $enrollment->customer_id_no,
                    'status' => $enrollment->status,
                ]
            );
        });

        if ($isSuperAdmin) {
            return redirect()
                ->route('admin.enrollment.step2', $enrollment)
                ->with('success', 'Enrollment saved successfully. Review the document preview and continue to post submission.');
        }

        return redirect()
            ->route('admin.enrollment.details', $enrollment)
            ->with('success', 'Enrollment submitted for approval. It is now pending super admin review.');
    }

    /**
     * Show the form for editing the specified enrollment.
     */
    public function edit($id)
    {
        $enrollment = Enrollment::findOrFail($id);
        $this->authorizeEnrollmentAccess($enrollment);

        if ($this->shouldForceReadOnly($enrollment)) {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

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

        $validated = $this->validatedEnrollmentData($request);
        $validated['bond_to_mail'] = isset($validated['bond_to_mail']) && $validated['bond_to_mail'] === 'Y';

        $enrollment->update($validated);

        $this->activityLogService->log(
            $request,
            'enrollment',
            'update',
            $enrollment,
            Auth::user(),
            'Updated an enrollment record.',
            [
                'doctor_name' => $enrollment->doctor_name,
                'membership_no' => $enrollment->customer_id_no,
            ]
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

    /**
     * Return list of pending enrollments for admin
     */
    public function pending()
    {
        $this->activityLogService->log(request(), 'enrollment', 'view_pending', description: 'Viewed pending approvals list.');

        $query = $this->enrollmentListingQuery(Auth::user());
        $this->applyEnrollmentListFilters($query, request(), true);

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
        $canReject = $isSuperAdmin || $isAdmin;

        // Edit allowed for super-admin/admin or when explicit edit privilege exists
        $canEdit = $isSuperAdmin || $isAdmin || $this->adminAccessService->hasPrivilege($currentUser, 'enrollment', 'edit');

        return view('admin.enrollment.pending', compact('enrollments', 'employees', 'canApprove', 'canReject', 'canEdit', 'isSuperAdmin', 'isAdmin'));
    }

    /**
     * Show read-only details for a pending record
     */
    public function showDetails($id)
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

        return view('admin.enrollment.details', compact('enrollment', 'latestActivity'));
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

        return view('admin.enrollment.my-enrollment-details', compact('enrollment', 'latestActivity'));
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

    public function stepTwo(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($enrollment->status !== 'approved') {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        return view('admin.enrollment.step2', compact('enrollment'));
    }

    public function stepThree(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($enrollment->status !== 'approved') {
            return redirect()
                ->route('admin.enrollment.details', $enrollment)
                ->with('info', 'This enrollment is pending approval and is available in read-only mode.');
        }

        $policyReceipts = \App\Models\PolicyReceipt::where('enrollment_id', $enrollment->id)
            ->orderByDesc('id')
            ->get();

        return view('admin.enrollment.step3', compact('enrollment', 'policyReceipts'));
    }

    public function success(Enrollment $enrollment)
    {
        $this->authorizeEnrollmentAccess($enrollment);

        if ($enrollment->status !== 'approved') {
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
            'Monthly EMI' => 1 / 12,
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

    private function validatedEnrollmentData(Request $request): array
    {
        return $request->validate([
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
            'doctor_email'           => 'nullable|email|max:200',
            'dob'                    => 'nullable|date',
            'qualification'          => 'nullable|string|max:200',
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
        ]);
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
        return $enrollment->status !== 'approved' && !$this->isPrivilegedAdminUser(Auth::user());
    }

    private function isPrivilegedAdminUser(?User $user): bool
    {
        return (bool) ($user && (
            in_array(($user->role ?? null), ['admin', 'super_admin'], true) ||
            (method_exists($user, 'hasAdminRole') && $user->hasAdminRole(['admin', 'super_admin']))
        ));
    }
}
