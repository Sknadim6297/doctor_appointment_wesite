<?php

namespace App\Services;

use App\Models\AdminRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use stdClass;

class LegacyStaffImportService
{
    private const STAFF_USER_TYPE_IDS = [1, 2];

    /**
     * @return array{linked:int,created:int,updated:int,skipped:int,errors:array<int,string>}
     */
    public function syncStaffFromStaging(bool $dryRun = false): array
    {
        if (!Schema::hasTable('legacy_tbl_user')) {
            throw new \RuntimeException('legacy_tbl_user table missing.');
        }

        $stats = ['linked' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        DB::table('legacy_tbl_user')
            ->whereIn('user_type_id', self::STAFF_USER_TYPE_IDS)
            ->orderBy('id')
            ->chunk(100, function ($rows) use ($dryRun, &$stats) {
                foreach ($rows as $row) {
                    try {
                        $result = $this->importStaffRow($row, $dryRun);

                        if ($result === 'linked') {
                            $stats['linked']++;
                        } elseif ($result === 'created') {
                            $stats['created']++;
                        } elseif ($result === 'updated') {
                            $stats['updated']++;
                        } else {
                            $stats['skipped']++;
                        }
                    } catch (\Throwable $exception) {
                        $stats['errors'][$row->id] = $exception->getMessage();
                    }
                }
            });

        return $stats;
    }

    public function stagingStaffCount(): int
    {
        if (!Schema::hasTable('legacy_tbl_user')) {
            return 0;
        }

        return (int) DB::table('legacy_tbl_user')
            ->whereIn('user_type_id', self::STAFF_USER_TYPE_IDS)
            ->count();
    }

    /**
     * @return 'linked'|'created'|'updated'|'skipped'
     */
    private function importStaffRow(stdClass $row, bool $dryRun): string
    {
        $legacyId = (int) $row->id;
        $email = $this->normalizeEmail($row->email);
        $userTypeId = (int) ($row->user_type_id ?? 0);

        if ($email === null && $userTypeId !== 1) {
            return 'skipped';
        }

        $existingByLegacy = User::query()->where('legacy_user_id', $legacyId)->first();
        $existingByEmail = $email !== null
            ? User::query()->where('email', $email)->first()
            : null;

        $target = $existingByLegacy ?? $existingByEmail;

        if ($userTypeId === 1) {
            if ($target === null) {
                $target = User::query()
                    ->where('role', 'super_admin')
                    ->orderBy('id')
                    ->first();
            }

            if ($target === null) {
                return 'skipped';
            }

            $isLink = $existingByLegacy === null;

            if ($dryRun) {
                return $isLink ? 'linked' : 'updated';
            }

            $this->applyLegacyFields($target, $row, true, preserveEmail: $isLink && $target->email !== null);
            $target->save();
            $this->syncAdminRoles($target, $row);

            return $isLink ? 'linked' : 'updated';
        }

        if ($dryRun) {
            return $target ? 'updated' : 'created';
        }

        if ($target) {
            $this->applyLegacyFields($target, $row, false);
            $target->save();
            $this->syncAdminRoles($target, $row);

            return 'updated';
        }

        $user = new User;
        $this->applyLegacyFields($user, $row, false);
        $user->save();
        $this->syncAdminRoles($user, $row);

        return 'created';
    }

    private function applyLegacyFields(User $user, stdClass $row, bool $isSuperAdmin, bool $preserveEmail = false): void
    {
        $name = $this->resolveName($row);
        $createdAt = $this->legacyDateTime($row->created_date);
        $updatedAt = $this->legacyDateTime($row->edited_date) ?? $createdAt;

        $user->legacy_user_id = (int) $row->id;
        $user->name = $name;
        $user->first_name = $this->stringOrNull($row->first_name);
        $user->last_name = $this->stringOrNull($row->last_name);

        $email = $this->normalizeEmail($row->email);

        if ($email !== null && !($preserveEmail && $user->email)) {
            $user->email = $email;
        }

        $password = $this->stringOrNull($row->password);

        if ($password !== null) {
            $user->password = $password;
        }

        $user->role = $isSuperAdmin ? 'super_admin' : 'admin';
        $user->is_active = strtolower((string) ($row->status ?? '')) === 'active';
        $user->employee_no = $this->stringOrNull($row->employee_no);
        $user->phone = $this->stringOrNull($row->mobile_no);
        $user->aadhaar_no = $this->normalizeDocumentNo($row->aadhar_card_no);
        $user->pan_no = $this->normalizeDocumentNo($row->pan_card_no);
        $user->dob = $this->parseDob($row->dob);
        $user->profile_pic = $this->stringOrNull($row->profile_pic);

        if ($createdAt) {
            $user->created_at = $createdAt;
        }

        if ($updatedAt) {
            $user->updated_at = $updatedAt;
        }
    }

    private function syncAdminRoles(User $user, stdClass $row): void
    {
        if ($user->role === 'super_admin') {
            $user->roles()->sync([]);

            return;
        }

        $legacyRoleId = is_numeric($row->role_id ?? null) ? (int) $row->role_id : null;

        if (!$legacyRoleId || $legacyRoleId <= 1) {
            return;
        }

        $adminRole = AdminRole::query()->where('legacy_role_id', $legacyRoleId)->first();

        if (!$adminRole) {
            return;
        }

        $user->roles()->sync([$adminRole->id]);
    }

    private function resolveName(stdClass $row): string
    {
        $full = $this->stringOrNull($row->full_name);

        if ($full !== null) {
            return Str::title($full);
        }

        $parts = array_filter([
            $this->stringOrNull($row->first_name),
            $this->stringOrNull($row->last_name),
        ]);

        if ($parts !== []) {
            return Str::title(implode(' ', $parts));
        }

        return 'Legacy User ' . $row->id;
    }

    private function normalizeDocumentNo(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        $normalized = strtolower($value);

        if (in_array($normalized, ['0', 'n.a', 'n.a.', 'na', 'not found', 'not collected'], true)) {
            return null;
        }

        return $value;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = $this->stringOrNull($value);

        if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return strtolower($email);
    }

    private function parseDob(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        $formats = ['Y-m-d', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);

                if ($parsed && $parsed->year >= 1900 && $parsed->year <= now()->year) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            $parsed = Carbon::parse($value);

            if ($parsed->year >= 1900 && $parsed->year <= now()->year) {
                return $parsed->format('Y-m-d');
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function legacyDateTime(mixed $value): ?Carbon
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
