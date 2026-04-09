<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComboPlan;
use App\Models\Enrollment;
use App\Models\HighRiskPlan;
use App\Models\InsurancePlan;
use App\Models\NormalPlan;
use App\Models\Specialization;
use App\Services\ActivityLogService;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentController extends Controller
{
    public function __construct(private readonly ActivityLogService $activityLogService)
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

        $query = Enrollment::query()->with('specialization')->orderByDesc('id');

        if (!empty($searchMonth) && !empty($searchYear)) {
            $monthNumber = date('n', strtotime($searchMonth . ' 1'));
            $yearNumber = (int) $searchYear;

            if ($renewType === 'renewed') {
                $query
                    ->whereMonth('created_at', $monthNumber)
                    ->whereYear('created_at', $yearNumber);
            } else {
                // "Next Renewal" is computed as one year from initial enrollment date.
                $query->whereRaw('MONTH(DATE_ADD(created_at, INTERVAL 1 YEAR)) = ?', [$monthNumber])
                    ->whereRaw('YEAR(DATE_ADD(created_at, INTERVAL 1 YEAR)) = ?', [$yearNumber]);
            }
        }

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
            'searchYear'
        ));
    }

    public function incompleteDocuments(Request $request)
    {
        $enrollments = Enrollment::query()
            ->with('specialization')
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

        $query = Enrollment::query()->with('specialization')->orderByDesc('id');

        if (!empty($searchMonth) && !empty($searchYear)) {
            $monthNumber = date('n', strtotime($searchMonth . ' 1'));
            $yearNumber = (int) $searchYear;

            if ($renewType === 'renewed') {
                $query->whereMonth('created_at', $monthNumber)
                    ->whereYear('created_at', $yearNumber);
            } else {
                $query->whereRaw('MONTH(DATE_ADD(created_at, INTERVAL 1 YEAR)) = ?', [$monthNumber])
                    ->whereRaw('YEAR(DATE_ADD(created_at, INTERVAL 1 YEAR)) = ?', [$yearNumber]);
            }
        }

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

        return view('admin.enrollment.create', compact(
            'specializations',
            'countries',
            'states',
            'cities',
            'years',
            'defaultCountryId',
            'defaultStateId',
            'defaultCityId'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'mobile2'                => 'nullable|string|max:20',
            'doctor_email'           => 'nullable|email|max:200',
            'dob'                    => 'nullable|date',
            'qualification'          => 'nullable|string|max:200',
            'qualification_year'     => 'nullable|array',
            'qualification_year.*'   => 'nullable|integer',
            'medical_registration_no'=> 'nullable|string|max:100',
            'year_of_reg'            => 'nullable|integer',
            'clinic_address'         => 'nullable|string|max:500',
            'aadhar_card_no'         => 'nullable|string|max:20',
            'pan_card_no'            => 'nullable|string|max:20',
            'specialization_id'      => 'nullable|integer|exists:specializations,id',
            'payment_mode'           => 'nullable|string|max:50',
            'plan'                   => 'nullable|integer|in:1,2,3',
            'plan_name'              => 'nullable|string|max:50',
            'coverage_id'            => 'nullable|integer',
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

        $validated['bond_to_mail'] = isset($validated['bond_to_mail']) && $validated['bond_to_mail'] === 'Y';
        $validated['created_by']   = Auth::id();

        // Resolve city_name / state_name from IDs if not supplied
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

        $enrollment = Enrollment::create($validated);

        if ($enrollment) {
            $this->activityLogService->log(
                $request,
                'enrollment',
                'create',
                $enrollment,
                Auth::user(),
                'Created a new enrollment record.',
                [
                    'doctor_name' => $enrollment->doctor_name,
                    'membership_no' => $enrollment->customer_id_no,
                ]
            );
        }

        return redirect()
            ->route('admin.enrollment.step2', $enrollment)
            ->with('success', 'Enrollment saved successfully. Review the document preview and continue to post submission.');
    }

    /**
     * Show the form for editing the specified enrollment.
     */
    public function edit($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $specializations = Specialization::orderBy('name')->get();
        $countries = LocationService::countries();

        $defaultCountryId = $enrollment->country ?? 101;
        $defaultStateId = $enrollment->state ?? 41;
        $defaultCityId = $enrollment->city ?? 5583;

        $states = LocationService::statesByCountry($defaultCountryId);
        $cities = LocationService::citiesByState($defaultStateId);

        $currentYear = (int) date('Y');
        $years = range($currentYear, 1950);

        return view('admin.enrollment.create', compact(
            'specializations',
            'countries',
            'states',
            'cities',
            'years',
            'defaultCountryId',
            'defaultStateId',
            'defaultCityId'
        ))->with('enrollment', $enrollment);
    }

    /**
     * Update the specified enrollment in storage.
     */
    public function update(Request $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $validated = $request->validate([
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
            'mobile2'                => 'nullable|string|max:20',
            'doctor_email'           => 'nullable|email|max:200',
            'dob'                    => 'nullable|date',
            'qualification'          => 'nullable|string|max:200',
            'qualification_year'     => 'nullable|array',
            'qualification_year.*'   => 'nullable|integer',
            'medical_registration_no'=> 'nullable|string|max:100',
            'year_of_reg'            => 'nullable|integer',
            'clinic_address'         => 'nullable|string|max:500',
            'aadhar_card_no'         => 'nullable|string|max:20',
            'pan_card_no'            => 'nullable|string|max:20',
            'specialization_id'      => 'nullable|integer|exists:specializations,id',
            'payment_mode'           => 'nullable|string|max:50',
            'plan'                   => 'nullable|integer|in:1,2,3',
            'coverage_id'            => 'nullable|integer',
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

        $validated['bond_to_mail'] = isset($validated['bond_to_mail']) && $validated['bond_to_mail'] === 'Y';

        $enrollment->update($validated);

        return redirect()->route('admin.enrollment')->with('success', 'Enrollment updated successfully.');
    }

    public function stepTwo(Enrollment $enrollment)
    {
        return view('admin.enrollment.step2', compact('enrollment'));
    }

    public function stepThree(Enrollment $enrollment)
    {
        return view('admin.enrollment.step3', compact('enrollment'));
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
}
