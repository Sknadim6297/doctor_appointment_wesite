<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Specialization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class BulkDoctorUploadService
{
    public function import(UploadedFile $file, int|null $createdBy = null): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headers = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $processed = 0;

        foreach ($rows as $rowNumber => $row) {
            if ((int) $rowNumber === 1) {
                $headers = $this->normalizeHeaders($row);
                continue;
            }

            $normalizedRow = $this->buildRow($headers, $row);

            if ($this->rowIsBlank($normalizedRow)) {
                continue;
            }

            $processed++;

            $validator = Validator::make($normalizedRow, [
                'doctor_name' => ['required', 'string', 'max:200'],
                'doctor_email' => ['nullable', 'email', 'max:200'],
                'mobile1' => ['nullable', 'string', 'max:20'],
                'mobile2' => ['nullable', 'string', 'max:20'],
                'postcode' => ['nullable', 'string', 'max:20'],
                'customer_id_no' => ['nullable', 'string', 'max:100'],
                'money_rc_no' => ['nullable', 'string', 'max:50'],
                'agent_name' => ['nullable', 'string', 'max:200'],
                'agent_phone_no' => ['nullable', 'string', 'max:20'],
                'doctor_address' => ['nullable', 'string', 'max:500'],
                'country_name' => ['nullable', 'string', 'max:100'],
                'state_name' => ['nullable', 'string', 'max:100'],
                'city_name' => ['nullable', 'string', 'max:100'],
                'qualification' => ['nullable', 'string', 'max:200'],
                'medical_registration_no' => ['nullable', 'string', 'max:100'],
                'year_of_reg' => ['nullable', 'integer'],
                'clinic_address' => ['nullable', 'string', 'max:500'],
                'aadhar_card_no' => ['nullable', 'string', 'max:20'],
                'pan_card_no' => ['nullable', 'string', 'max:20'],
                'payment_mode' => ['nullable', 'string', 'max:50'],
                'plan' => ['nullable'],
                'plan_name' => ['nullable', 'string', 'max:50'],
                'service_amount' => ['nullable', 'numeric', 'min:0'],
                'payment_amount' => ['nullable', 'numeric', 'min:0'],
                'total_amount' => ['nullable', 'numeric', 'min:0'],
                'payment_cheque' => ['nullable', 'string', 'max:100'],
                'payment_bank_name' => ['nullable', 'string', 'max:200'],
                'payment_branch_name' => ['nullable', 'string', 'max:200'],
                'payment_upi_transaction_id' => ['nullable', 'string', 'max:100'],
            ]);

            if ($validator->fails()) {
                $errors[] = [
                    'row' => $rowNumber,
                    'messages' => $validator->errors()->all(),
                ];
                $skipped++;
                continue;
            }

            $payload = $this->buildPayload($normalizedRow, $createdBy);

            try {
                $query = Enrollment::query();

                if (!empty($payload['customer_id_no'])) {
                    $query->where('customer_id_no', $payload['customer_id_no']);
                }

                $existing = !empty($payload['customer_id_no']) ? $query->first() : null;

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                } else {
                    Enrollment::create($payload);
                    $created++;
                }
            } catch (Throwable $throwable) {
                $errors[] = [
                    'row' => $rowNumber,
                    'messages' => [$throwable->getMessage()],
                ];
                $skipped++;
            }
        }

        return [
            'processed_rows' => $processed,
            'created_rows' => $created,
            'updated_rows' => $updated,
            'skipped_rows' => $skipped,
            'errors' => $errors,
        ];
    }

    public static function templateHeaders(): array
    {
        return [
            'customer_id_no',
            'money_rc_no',
            'agent_name',
            'agent_phone_no',
            'doctor_name',
            'doctor_address',
            'country',
            'country_name',
            'state',
            'state_name',
            'city',
            'city_name',
            'postcode',
            'mobile1',
            'mobile2',
            'doctor_email',
            'dob',
            'qualification',
            'qualification_year',
            'medical_registration_no',
            'year_of_reg',
            'clinic_address',
            'aadhar_card_no',
            'pan_card_no',
            'specialization_id',
            'specialization_name',
            'payment_mode',
            'plan',
            'plan_name',
            'coverage_id',
            'service_amount',
            'payment_amount',
            'total_amount',
            'payment_method',
            'payment_cheque',
            'payment_bank_name',
            'payment_branch_name',
            'payment_upi_transaction_id',
            'payment_cash_date',
            'bond_to_mail',
            'auto_sms_enabled',
        ];
    }

    public static function templateExampleRow(): array
    {
        return [
            'MF-1001',
            'RC-2026-01',
            'Demo Agent',
            '9876543210',
            'Dr. Example Name',
            '12 Example Street, Example City',
            '101',
            'India',
            '41',
            'West Bengal',
            '5583',
            'Kolkata',
            '700001',
            '9000000001',
            '9000000002',
            'doctor@example.com',
            '1985-05-15',
            'MBBS, MD',
            '2005,2010',
            'REG-12345',
            '2010',
            'Example Clinic Address',
            '123412341234',
            'ABCDE1234F',
            '1',
            'Cardiology',
            'Cash',
            '1',
            'Normal',
            '100',
            '5000',
            '6500',
            '2',
            '',
            '',
            '',
            '',
            '',
            '2026-04-14',
            'Y',
            'Y',
        ];
    }

    private function normalizeHeaders(array $row): array
    {
        return array_map(function ($value) {
            $value = strtolower(trim((string) $value));
            $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';

            return trim($value, '_');
        }, $row);
    }

    private function buildRow(array $headers, array $row): array
    {
        $normalized = [];
        $values = array_values($row);

        foreach ($values as $index => $value) {
            $header = $headers[$index] ?? null;

            if ($header === null || $header === '') {
                continue;
            }

            $normalized[$header] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    private function rowIsBlank(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function buildPayload(array $row, int|null $createdBy): array
    {
        $plan = $this->normalizePlan(Arr::get($row, 'plan'), Arr::get($row, 'plan_name'));
        $specializationId = $this->resolveSpecializationId($row);

        $country = $this->resolveLocationValue(Arr::get($row, 'country'), LocationService::countries(), Arr::get($row, 'country_name'));
        $state = $this->resolveLocationValue(Arr::get($row, 'state'), LocationService::indiaStates(), Arr::get($row, 'state_name'));
        $city = $this->resolveCityValue(Arr::get($row, 'city'), Arr::get($row, 'city_name'), (int) ($state['id'] ?? 0));

        $serviceAmount = $this->numericOrNull(Arr::get($row, 'service_amount'));
        $paymentAmount = $this->numericOrNull(Arr::get($row, 'payment_amount'));

        return [
            'customer_id_no' => $this->stringOrNull(Arr::get($row, 'customer_id_no')),
            'money_rc_no' => $this->stringOrNull(Arr::get($row, 'money_rc_no')),
            'agent_name' => $this->stringOrNull(Arr::get($row, 'agent_name')),
            'agent_phone_no' => $this->stringOrNull(Arr::get($row, 'agent_phone_no')),
            'doctor_name' => $this->stringOrNull(Arr::get($row, 'doctor_name')),
            'doctor_address' => $this->stringOrNull(Arr::get($row, 'doctor_address')),
            'country' => $country['id'] ?? null,
            'country_name' => $country['name'] ?? $this->stringOrNull(Arr::get($row, 'country_name')),
            'state' => $state['id'] ?? null,
            'state_name' => $state['name'] ?? $this->stringOrNull(Arr::get($row, 'state_name')),
            'city' => $city['id'] ?? null,
            'city_name' => $city['name'] ?? $this->stringOrNull(Arr::get($row, 'city_name')),
            'postcode' => $this->stringOrNull(Arr::get($row, 'postcode')),
            'mobile1' => $this->stringOrNull(Arr::get($row, 'mobile1')),
            'mobile2' => $this->stringOrNull(Arr::get($row, 'mobile2')),
            'doctor_email' => $this->stringOrNull(Arr::get($row, 'doctor_email')),
            'dob' => $this->dateOrNull(Arr::get($row, 'dob')),
            'qualification' => $this->stringOrNull(Arr::get($row, 'qualification')),
            'qualification_year' => $this->qualificationYears(Arr::get($row, 'qualification_year')),
            'medical_registration_no' => $this->stringOrNull(Arr::get($row, 'medical_registration_no')),
            'year_of_reg' => $this->integerOrNull(Arr::get($row, 'year_of_reg')),
            'clinic_address' => $this->stringOrNull(Arr::get($row, 'clinic_address')),
            'aadhar_card_no' => $this->stringOrNull(Arr::get($row, 'aadhar_card_no')),
            'pan_card_no' => $this->stringOrNull(Arr::get($row, 'pan_card_no')),
            'specialization_id' => $specializationId,
            'payment_mode' => $this->stringOrNull(Arr::get($row, 'payment_mode')),
            'plan' => $plan['value'],
            'plan_name' => $plan['label'],
            'coverage_id' => $this->integerOrNull(Arr::get($row, 'coverage_id')),
            'service_amount' => $serviceAmount,
            'payment_amount' => $paymentAmount,
            'total_amount' => $this->numericOrNull(Arr::get($row, 'total_amount')) ?? ($serviceAmount !== null && $paymentAmount !== null ? $serviceAmount + $paymentAmount : null),
            'payment_method' => $this->normalizePaymentMethod(Arr::get($row, 'payment_method')),
            'payment_cheque' => $this->stringOrNull(Arr::get($row, 'payment_cheque')),
            'payment_bank_name' => $this->stringOrNull(Arr::get($row, 'payment_bank_name')),
            'payment_branch_name' => $this->stringOrNull(Arr::get($row, 'payment_branch_name')),
            'payment_upi_transaction_id' => $this->stringOrNull(Arr::get($row, 'payment_upi_transaction_id')),
            'payment_cash_date' => $this->dateOrNull(Arr::get($row, 'payment_cash_date')),
            'bond_to_mail' => $this->booleanValue(Arr::get($row, 'bond_to_mail')),
            'auto_sms_enabled' => $this->booleanValue(Arr::get($row, 'auto_sms_enabled')),
            'created_by' => $createdBy,
        ];
    }

    private function resolveSpecializationId(array $row): int|null
    {
        $specializationId = $this->integerOrNull(Arr::get($row, 'specialization_id'));

        if ($specializationId && Specialization::query()->whereKey($specializationId)->exists()) {
            return $specializationId;
        }

        $specializationName = $this->stringOrNull(Arr::get($row, 'specialization_name'));

        if ($specializationName === null) {
            return null;
        }

        $specialization = Specialization::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($specializationName)])
            ->first();

        return $specialization?->id;
    }

    private function resolveLocationValue(mixed $value, array $lookup, mixed $name): array
    {
        $numericValue = $this->integerOrNull($value);

        if ($numericValue !== null && isset($lookup[$numericValue])) {
            return ['id' => (string) $numericValue, 'name' => $lookup[$numericValue]];
        }

        $label = $this->stringOrNull($name);

        if ($label === null) {
            return ['id' => null, 'name' => null];
        }

        $matchedId = null;
        $needle = mb_strtolower($label);
        foreach ($lookup as $lookupId => $lookupLabel) {
            if (mb_strtolower((string) $lookupLabel) === $needle) {
                $matchedId = $lookupId;
                break;
            }
        }

        return [
            'id' => $matchedId === null ? null : (string) $matchedId,
            'name' => $label,
        ];
    }

    private function resolveCityValue(mixed $value, mixed $name, int $stateId): array
    {
        $cities = $stateId > 0 ? LocationService::citiesByState($stateId) : [];
        $numericValue = $this->integerOrNull($value);

        if ($numericValue !== null && isset($cities[$numericValue])) {
            return ['id' => (string) $numericValue, 'name' => $cities[$numericValue]];
        }

        $label = $this->stringOrNull($name);

        if ($label === null) {
            return ['id' => null, 'name' => null];
        }

        $matchedId = null;
        $needle = mb_strtolower($label);
        foreach ($cities as $cityId => $cityLabel) {
            if (mb_strtolower((string) $cityLabel) === $needle) {
                $matchedId = $cityId;
                break;
            }
        }

        return [
            'id' => $matchedId === null ? null : (string) $matchedId,
            'name' => $label,
        ];
    }

    private function normalizePlan(mixed $value, mixed $label): array
    {
        $text = strtolower(trim((string) ($label ?? $value ?? '')));
        $numeric = $this->integerOrNull($value);

        if ($numeric === 2 || $text === 'high risk' || $text === 'high_risk') {
            return ['value' => 2, 'label' => 'High Risk'];
        }

        if ($numeric === 3 || $text === 'combo') {
            return ['value' => 3, 'label' => 'Combo'];
        }

        if ($numeric === 1 || $text === 'normal' || $text === 'normal plan') {
            return ['value' => 1, 'label' => 'Normal'];
        }

        return ['value' => $numeric, 'label' => $this->stringOrNull($label)];
    }

    private function normalizePaymentMethod(mixed $value): int|null
    {
        $text = strtolower(trim((string) $value));
        $numeric = $this->integerOrNull($value);

        if ($numeric !== null && in_array($numeric, [1, 2, 3], true)) {
            return $numeric;
        }

        return match ($text) {
            'cheque', 'check' => 1,
            'cash' => 2,
            'upi', 'online', 'bank', 'netbanking', 'internet_banking' => 3,
            default => null,
        };
    }

    private function qualificationYears(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $years = array_map('intval', array_filter($value, fn ($item) => $item !== null && trim((string) $item) !== ''));
            return $years === [] ? null : array_values(array_unique($years));
        }

        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        if ($text[0] === '[') {
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                $years = array_map('intval', array_filter($decoded, fn ($item) => $item !== null && trim((string) $item) !== ''));
                return $years === [] ? null : array_values(array_unique($years));
            }
        }

        $parts = preg_split('/[;,|]+/', $text) ?: [$text];
        $years = array_map('intval', array_filter($parts, fn ($item) => trim((string) $item) !== ''));

        return $years === [] ? null : array_values(array_unique($years));
    }

    private function booleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $text = strtolower(trim((string) $value));

        return in_array($text, ['1', 'true', 'yes', 'y', 'on', 'enabled'], true);
    }

    private function integerOrNull(mixed $value): int|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function numericOrNull(mixed $value): float|null
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function dateOrNull(mixed $value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        try {
            return Carbon::parse($text)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

}