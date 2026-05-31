<?php

namespace App\Support;

use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;

final class EnrollmentFormValidation
{
    public static function make(Request $request, ?int $ignoreEnrollmentId = null, bool $isAutosave = false): ValidatorInstance
    {
        $validator = Validator::make($request->all(), self::baseRules($isAutosave));

        $validator->after(function (ValidatorInstance $v) use ($request, $ignoreEnrollmentId, $isAutosave): void {
            self::validateFinancials($v, $request);
            self::validateEmailDuplicate($v, $request, $ignoreEnrollmentId);
            self::validateIdentityFields($v, $request, $ignoreEnrollmentId);
            self::validateDob($v, $request);
            self::validatePaymentProof($v, $request, $isAutosave);
        });

        return $validator;
    }

    /**
     * @return array<string, mixed>
     */
    private static function baseRules(bool $isAutosave): array
    {
        return [
            'customer_id_no' => 'nullable|string|max:100',
            'money_rc_no' => 'nullable|string|max:50',
            'agent_name' => 'nullable|string|max:200',
            'agent_phone_no' => 'nullable|string|max:20',
            'doctor_name' => ($isAutosave ? 'nullable' : 'required') . '|string|max:200',
            'doctor_address' => 'nullable|string|max:500',
            'country' => 'nullable|integer',
            'country_name' => 'nullable|string|max:100',
            'state' => 'nullable|integer',
            'state_name' => 'nullable|string|max:100',
            'city' => 'nullable|integer',
            'city_name' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:20',
            'mobile1' => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/'],
            'mobile2' => ($isAutosave ? 'nullable' : 'required') . '|string|regex:/^[6-9]\d{9}$/',
            'doctor_email' => 'nullable|email|max:200',
            'dob' => 'nullable|string',
            'qualification_names' => 'nullable|array',
            'qualification_names.*' => 'nullable|string|max:200',
            'qualification_years' => 'nullable|array',
            'qualification_years.*' => 'nullable|integer|min:1950|max:' . ((int) date('Y') + 1),
            'qualification' => 'nullable',
            'qualification_year' => 'nullable|array',
            'qualification_year.*' => 'nullable|integer',
            'medical_registration_no' => ($isAutosave ? 'nullable' : 'required') . '|string|max:100',
            'year_of_reg' => 'nullable|integer',
            'clinic_address' => 'nullable|string|max:500',
            'aadhar_card_no' => ($isAutosave ? 'nullable' : 'required') . '|digits:12',
            'pan_card_no' => ($isAutosave ? 'nullable' : 'required') . '|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/i',
            'specialization_id' => ($isAutosave ? 'nullable' : 'required') . '|integer|exists:specializations,id',
            'payment_mode' => 'nullable|string|max:50',
            'plan' => ($isAutosave ? 'nullable' : 'required') . '|integer|in:1,2,3',
            'plan_name' => 'nullable|string|max:50',
            'coverage_id' => 'nullable|integer',
            'coverage' => 'nullable|numeric|min:0',
            'service_amount' => 'nullable|numeric|min:0',
            'payment_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|integer|in:1,2,3',
            'payment_cheque' => 'nullable|string|max:100',
            'payment_bank_name' => 'nullable|string|max:200',
            'payment_branch_name' => 'nullable|string|max:200',
            'payment_upi_transaction_id' => 'nullable|string|max:100',
            'payment_cash_date' => ['nullable', 'string', 'max:20', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || trim((string) $value) === '') {
                    return;
                }

                $parsed = \App\Support\AdminDateFormat::parseToDatabase((string) $value);
                if ($parsed === null) {
                    $fail('Payment date must be in DD/MM/YY format.');

                    return;
                }

                if (Carbon::parse($parsed)->isFuture()) {
                    $fail('Payment date cannot be in the future.');
                }
            }],
            'bond_to_mail' => 'nullable|in:Y',
        ];
    }

    private static function validateFinancials(ValidatorInstance $v, Request $request): void
    {
        $totalAmount = (float) $request->input('total_amount', 0);
        $serviceAmount = (float) $request->input('service_amount', 0);

        if ($serviceAmount > $totalAmount && $totalAmount > 0) {
            $v->errors()->add('service_amount', 'Insurance amount cannot be greater than total amount.');
        }
    }

    private static function validateEmailDuplicate(ValidatorInstance $v, Request $request, ?int $ignoreEnrollmentId): void
    {
        $email = strtolower(trim((string) $request->input('doctor_email', '')));
        if ($email === '') {
            return;
        }

        $duplicateQuery = Enrollment::query()
            ->whereNotNull('doctor_email')
            ->where('doctor_email', '!=', '')
            ->whereRaw('LOWER(TRIM(doctor_email)) = ?', [$email]);

        if ($ignoreEnrollmentId) {
            $duplicateQuery->where('id', '!=', $ignoreEnrollmentId);
        }

        if ($duplicateQuery->exists()) {
            $v->errors()->add('doctor_email', 'This email already exists in the system.');
        }
    }

    private static function validateIdentityFields(ValidatorInstance $v, Request $request, ?int $ignoreEnrollmentId): void
    {
        $aadhaar = self::digitsOnly($request->input('aadhar_card_no'));
        if ($aadhaar !== '' && strlen($aadhaar) !== 12) {
            $v->errors()->add('aadhar_card_no', 'Aadhaar must be exactly 12 digits.');
        } elseif ($aadhaar !== '') {
            self::assertUniqueField($v, 'aadhar_card_no', $aadhaar, $ignoreEnrollmentId, 'Aadhaar number');
        }

        $pan = strtoupper(preg_replace('/\s+/', '', (string) $request->input('pan_card_no', '')));
        if ($pan !== '' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]$/', $pan)) {
            $v->errors()->add('pan_card_no', 'PAN must be in format ABCDE1234F (5 letters, 4 digits, 1 letter).');
        } elseif ($pan !== '') {
            self::assertUniqueField($v, 'pan_card_no', $pan, $ignoreEnrollmentId, 'PAN number');
        }

        $medReg = trim((string) $request->input('medical_registration_no', ''));
        if ($medReg !== '') {
            self::assertUniqueField($v, 'medical_registration_no', $medReg, $ignoreEnrollmentId, 'Medical registration number');
        }
    }

    private static function validateDob(ValidatorInstance $v, Request $request): void
    {
        $dobRaw = trim((string) $request->input('dob', ''));
        if ($dobRaw === '') {
            return;
        }

        try {
            $dob = str_contains($dobRaw, '/')
                ? \Carbon\Carbon::createFromFormat('d/m/Y', $dobRaw)->startOfDay()
                : \Carbon\Carbon::parse($dobRaw)->startOfDay();
        } catch (\Throwable) {
            $v->errors()->add('dob', 'Enter a valid date of birth.');

            return;
        }

        if ($dob->isFuture()) {
            $v->errors()->add('dob', 'Date of birth cannot be in the future.');
        }
    }

    private static function validatePaymentProof(ValidatorInstance $v, Request $request, bool $isAutosave): void
    {
        if ($isAutosave || !$request->boolean('add_payment_details')) {
            return;
        }

        $method = (int) $request->input('payment_method', 0);

        if ($method === 2) {
            return;
        }

        if (!in_array($method, [1, 3], true)) {
            return;
        }

        if (!$request->hasFile('doc_payment_document')) {
            $hasExisting = false;
            $enrollmentId = (int) $request->input('workflow_enrollment_id', 0);
            if ($enrollmentId > 0) {
                $hasExisting = \App\Models\DoctorDocument::query()
                    ->where('enrollment_id', $enrollmentId)
                    ->where('is_active', true)
                    ->where('document_type', 'payment_proof')
                    ->whereNotNull('document_file')
                    ->exists();
            }

            if (!$hasExisting) {
                $v->errors()->add('doc_payment_document', 'Payment proof document is required for Cheque and UPI payments.');
            }
        }
    }

    private static function assertUniqueField(
        ValidatorInstance $v,
        string $field,
        string $value,
        ?int $ignoreEnrollmentId,
        string $label
    ): void {
        $query = Enrollment::query();

        if ($field === 'aadhar_card_no') {
            $query->whereRaw(
                "REPLACE(REPLACE(REPLACE(aadhar_card_no, ' ', ''), '-', ''), '.', '') = ?",
                [$value]
            );
        } elseif ($field === 'pan_card_no') {
            $query->whereRaw('UPPER(REPLACE(pan_card_no, " ", "")) = ?', [strtoupper($value)]);
        } else {
            $query->where($field, $value);
        }

        if ($ignoreEnrollmentId) {
            $query->where('id', '!=', $ignoreEnrollmentId);
        }

        if ($query->exists()) {
            $v->errors()->add($field, "{$label} is already registered in the system.");
        }
    }

    public static function digitsOnly(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function normalizePan(mixed $value): ?string
    {
        $pan = strtoupper(preg_replace('/\s+/', '', (string) $value));

        return $pan !== '' ? $pan : null;
    }
}
