<?php

namespace App\Support;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Canonical enrollment workflow_status values and query helpers.
 */
final class EnrollmentWorkflow
{
    public const DRAFT = 'draft';

    public const IN_PROGRESS = 'in_progress';

    public const PENDING_APPROVAL = 'pending_approval';

    /** @deprecated Legacy rows only; normalized to pending_approval */
    public const PENDING_REVIEW = 'pending_review';

    public const RETURNED_FOR_CORRECTION = 'returned_for_correction';

    public const REJECTED = 'rejected';

    public const COMPLETED = 'completed';

    /**
     * Map stored DB value to canonical workflow key.
     */
    public static function normalize(?string $workflowStatus): string
    {
        $v = strtolower(trim((string) $workflowStatus));

        return match ($v) {
            self::PENDING_REVIEW => self::PENDING_APPROVAL,
            '' => self::DRAFT,
            default => $v,
        };
    }

    public static function label(?string $workflowStatus): string
    {
        return match (self::normalize($workflowStatus)) {
            self::DRAFT => 'Draft',
            self::IN_PROGRESS => 'In progress',
            self::PENDING_APPROVAL => 'Pending approval',
            self::RETURNED_FOR_CORRECTION => 'Returned for correction',
            self::REJECTED => 'Rejected',
            self::COMPLETED => 'Completed',
            default => ucfirst(str_replace('_', ' ', (string) $workflowStatus)),
        };
    }

    public static function badgeClasses(?string $workflowStatus): string
    {
        return match (self::normalize($workflowStatus)) {
            self::DRAFT => 'bg-slate-100 text-slate-800 ring-slate-200',
            self::IN_PROGRESS => 'bg-sky-100 text-sky-800 ring-sky-200',
            self::PENDING_APPROVAL => 'bg-amber-100 text-amber-900 ring-amber-200',
            self::RETURNED_FOR_CORRECTION => 'bg-violet-100 text-violet-900 ring-violet-200',
            self::REJECTED => 'bg-rose-100 text-rose-800 ring-rose-200',
            self::COMPLETED => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    public static function gateStatuses(): array
    {
        return [self::PENDING_APPROVAL, self::PENDING_REVIEW];
    }

    /**
     * Enrollments that need an approve / reject / return decision at the admin gate.
     */
    public static function scopePendingAdminGate(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->whereIn('workflow_status', self::gateStatuses())
                ->orWhere(function (Builder $inner): void {
                    $inner->where('status', 'pending')
                        ->where('workflow_status', self::IN_PROGRESS)
                        ->where('current_step', '>=', 2);
                });
        })->where('workflow_status', '!=', self::RETURNED_FOR_CORRECTION);
    }

    /**
     * New intake: drafts and step-1 work not yet at the approval gate.
     */
    public static function scopeNewEntries(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('workflow_status', self::DRAFT)
                ->orWhere(function (Builder $inner): void {
                    $inner->where('current_step', '<=', 1)
                        ->whereIn('workflow_status', [self::DRAFT, self::IN_PROGRESS]);
                });
        });
    }

    public static function scopeIncompletePipeline(Builder $query): Builder
    {
        return $query->where('is_step_incomplete', true)
            ->whereNotIn('workflow_status', [self::COMPLETED, self::REJECTED]);
    }

    public static function scopeRejectedCases(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('workflow_status', self::REJECTED)
                ->orWhere('status', 'rejected');
        });
    }

    public static function scopeReturnedForCorrection(Builder $query): Builder
    {
        return $query->where('workflow_status', self::RETURNED_FOR_CORRECTION);
    }

    public static function displayStatus(Enrollment $e): string
    {
        if ($e->status === 'rejected' || self::normalize($e->workflow_status) === self::REJECTED) {
            return 'Rejected';
        }

        if (self::normalize($e->workflow_status) === self::RETURNED_FOR_CORRECTION) {
            return 'Returned for correction';
        }

        if (self::normalize($e->workflow_status) === self::COMPLETED
            && !$e->is_step_incomplete
            && $e->status === 'approved') {
            return 'Completed';
        }

        if (in_array(self::normalize($e->workflow_status), self::gateStatuses(), true)
            || ($e->status === 'pending' && (int) ($e->current_step ?? 1) >= 2
                && self::normalize($e->workflow_status) === self::IN_PROGRESS)) {
            return 'Pending approval';
        }

        if ($e->status === 'approved') {
            return self::normalize($e->workflow_status) === self::IN_PROGRESS
                ? 'Approved — onboarding in progress'
                : 'Approved';
        }

        return self::label($e->workflow_status);
    }
}
