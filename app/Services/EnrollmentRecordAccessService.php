<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentRecordAccessService
{
    public function hasFullRecordAccess(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if (($user->role ?? null) === 'admin') {
            return true;
        }

        return method_exists($user, 'hasAdminRole') && $user->hasAdminRole('admin');
    }

    public function isEmployeeLike(?User $user): bool
    {
        return $user !== null && !$this->hasFullRecordAccess($user);
    }

    public function ownsRecord(?User $user, Enrollment $enrollment): bool
    {
        if (!$user) {
            return false;
        }

        $userId = (int) $user->id;

        return (int) $enrollment->created_by === $userId
            || (int) $enrollment->agent_id === $userId;
    }

    public function canAccessRecord(?User $user, Enrollment $enrollment): bool
    {
        if ($this->hasFullRecordAccess($user)) {
            return true;
        }

        if (!$this->ownsRecord($user, $enrollment)) {
            return false;
        }

        if (($enrollment->created_by_role ?? null) === 'super_admin') {
            return false;
        }

        return true;
    }

    public function assertCanAccessRecord(?User $user, Enrollment $enrollment, ?string $message = null): void
    {
        if ($this->canAccessRecord($user, $enrollment)) {
            return;
        }

        abort(403, $message ?? 'You are not authorized to access this enrollment record.');
    }

    public function applyOwnedScope(Builder $query, ?User $user): Builder
    {
        if (!$user || $this->hasFullRecordAccess($user)) {
            return $query;
        }

        $query->where(function (Builder $builder) use ($user): void {
            $builder->where('created_by', $user->id)
                ->orWhere('agent_id', $user->id);
        });

        $query->where(function (Builder $builder): void {
            $builder->whereNull('created_by_role')
                ->orWhere('created_by_role', '!=', 'super_admin');
        });

        return $query;
    }

    public function isSuperAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (($user->role ?? null) === 'super_admin') {
            return true;
        }

        return method_exists($user, 'hasAdminRole') && $user->hasAdminRole('super_admin');
    }
}
