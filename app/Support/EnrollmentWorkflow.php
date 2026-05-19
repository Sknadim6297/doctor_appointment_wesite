<?php

namespace App\Support;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Canonical enrollment workflow_status values and query helpers.
 *
 * Lifecycle: Draft → Submitted → Pending Approval → Approved | Rejected | Hold
 * Rejected/Returned → employee edits → Resubmitted → Pending Approval
 * Approved → steps 2–4 → Completed
 */
final class EnrollmentWorkflow
{
    public const DRAFT = 'draft';

    public const SUBMITTED = 'submitted';

    public const IN_PROGRESS = 'in_progress';

    public const PENDING_APPROVAL = 'pending_approval';

    /** @deprecated Legacy rows only; normalized to pending_approval */
    public const PENDING_REVIEW = 'pending_review';

    public const RESUBMITTED = 'resubmitted';

    public const HOLD = 'hold';

    public const RETURNED_FOR_CORRECTION = 'returned_for_correction';

    public const REJECTED = 'rejected';

    public const COMPLETED = 'completed';

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
            self::SUBMITTED => 'Submitted',
            self::IN_PROGRESS => 'In progress',
            self::PENDING_APPROVAL => 'Pending approval',
            self::RESUBMITTED => 'Resubmitted',
            self::HOLD => 'On hold',
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
            self::SUBMITTED => 'bg-blue-100 text-blue-900 ring-blue-200',
            self::IN_PROGRESS => 'bg-sky-100 text-sky-800 ring-sky-200',
            self::PENDING_APPROVAL => 'bg-amber-100 text-amber-900 ring-amber-200',
            self::RESUBMITTED => 'bg-indigo-100 text-indigo-900 ring-indigo-200',
            self::HOLD => 'bg-orange-100 text-orange-900 ring-orange-200',
            self::RETURNED_FOR_CORRECTION => 'bg-violet-100 text-violet-900 ring-violet-200',
            self::REJECTED => 'bg-rose-100 text-rose-800 ring-rose-200',
            self::COMPLETED => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    public static function gateStatuses(): array
    {
        return [self::PENDING_APPROVAL, self::PENDING_REVIEW, self::RESUBMITTED, self::SUBMITTED];
    }

    public static function scopePendingAdminGate(Builder $query): Builder
    {
        return $query
            ->where('workflow_status', '!=', self::RETURNED_FOR_CORRECTION)
            ->where('workflow_status', '!=', self::HOLD)
            ->where('workflow_status', '!=', self::DRAFT)
            ->where(function (Builder $q): void {
                $q->whereIn('workflow_status', self::gateStatuses())
                    ->orWhere(function (Builder $inner): void {
                        $inner->where('status', 'pending')
                            ->whereNotNull('submitted_at')
                            ->whereNotIn('workflow_status', [
                                self::REJECTED,
                                self::COMPLETED,
                                self::RETURNED_FOR_CORRECTION,
                                self::HOLD,
                                self::DRAFT,
                            ]);
                    });
            });
    }

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
        return $query
            ->enrollmentPipeline()
            ->where(function (Builder $q): void {
                $q->where('workflow_status', '!=', self::COMPLETED)
                    ->orWhere('is_step_incomplete', true)
                    ->orWhere('current_step', '<', 4);
            })
            ->where(function (Builder $q): void {
                $q->where('status', '!=', 'rejected')
                    ->where('workflow_status', '!=', self::REJECTED);
            });
    }

    /** Approved enrollments still completing steps 2–4. */
    public static function scopeOnboardingInProgress(Builder $query): Builder
    {
        return $query
            ->where('status', 'approved')
            ->where(function (Builder $q): void {
                $q->where('workflow_status', self::IN_PROGRESS)
                    ->orWhere('current_step', '>', 1)
                    ->orWhere('is_step_incomplete', true);
            })
            ->where('workflow_status', '!=', self::COMPLETED);
    }

    public static function scopeDraftOnly(Builder $query): Builder
    {
        return $query->where('workflow_status', self::DRAFT);
    }

    public static function scopeApprovedNotCompleted(Builder $query): Builder
    {
        return $query
            ->where('status', 'approved')
            ->where('workflow_status', '!=', self::COMPLETED);
    }

    public static function scopeCompletedPipeline(Builder $query): Builder
    {
        return $query
            ->where('current_step', '>=', 4)
            ->where('workflow_status', self::COMPLETED)
            ->where('is_step_incomplete', false)
            ->where('status', 'approved');
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

    public static function scopeOnHold(Builder $query): Builder
    {
        return $query->where('workflow_status', self::HOLD);
    }

    public static function scopeResubmitted(Builder $query): Builder
    {
        return $query->where('workflow_status', self::RESUBMITTED);
    }

    public static function isOnHold(Enrollment $e): bool
    {
        return self::normalize($e->workflow_status) === self::HOLD;
    }

    public static function isRejected(Enrollment $e): bool
    {
        return $e->status === 'rejected' || self::normalize($e->workflow_status) === self::REJECTED;
    }

    public static function approvalStatus(Enrollment $e): string
    {
        if (self::isOnHold($e)) {
            return 'On hold';
        }

        if (self::isRejected($e)) {
            return 'Rejected';
        }

        if ($e->status === 'approved' && $e->approved_at) {
            return 'Approved';
        }

        if (self::normalize($e->workflow_status) === self::RESUBMITTED) {
            return 'Resubmitted';
        }

        if (self::normalize($e->workflow_status) === self::RETURNED_FOR_CORRECTION) {
            return 'Returned for correction';
        }

        if (in_array(self::normalize($e->workflow_status), self::gateStatuses(), true)
            || $e->status === 'pending') {
            return 'Pending approval';
        }

        if (self::normalize($e->workflow_status) === self::DRAFT) {
            return 'Draft';
        }

        return ucfirst((string) ($e->status ?: 'unknown'));
    }

    public static function approvalBadgeClasses(Enrollment $e): string
    {
        return match (self::approvalStatus($e)) {
            'Approved' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'Pending approval' => 'bg-amber-100 text-amber-900 ring-amber-200',
            'Resubmitted' => 'bg-indigo-100 text-indigo-900 ring-indigo-200',
            'On hold' => 'bg-orange-100 text-orange-900 ring-orange-200',
            'Rejected' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'Returned for correction' => 'bg-violet-100 text-violet-900 ring-violet-200',
            'Draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    public static function displayStatus(Enrollment $e): string
    {
        if (self::isOnHold($e)) {
            return 'On hold';
        }

        if (self::isRejected($e)) {
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

        if ($e->resubmitted_at && $e->status === 'pending') {
            return 'Pending approval';
        }

        if (self::normalize($e->workflow_status) === self::RESUBMITTED) {
            return 'Pending approval';
        }

        if ($e->submitted_at && $e->status === 'pending'
            && in_array(self::normalize($e->workflow_status), [self::PENDING_APPROVAL, self::SUBMITTED], true)) {
            return 'Pending approval';
        }

        if (in_array(self::normalize($e->workflow_status), self::gateStatuses(), true)
            || ($e->status === 'pending' && self::normalize($e->workflow_status) !== self::DRAFT)) {
            return 'Pending approval';
        }

        if ($e->status === 'approved') {
            return self::normalize($e->workflow_status) === self::IN_PROGRESS
                ? 'Approved — onboarding in progress'
                : 'Approved';
        }

        if (self::normalize($e->workflow_status) === self::DRAFT) {
            return 'Draft';
        }

        return self::label($e->workflow_status);
    }

    public static function dashboardStatusLabel(Enrollment $e): string
    {
        if (self::normalize($e->workflow_status) === self::COMPLETED
            && !$e->is_step_incomplete
            && $e->status === 'approved') {
            return 'Completed';
        }

        if ($e->status === 'approved') {
            return 'Approved';
        }

        if (self::isRejected($e)) {
            return 'Rejected';
        }

        if (self::normalize($e->workflow_status) === self::DRAFT) {
            return 'Draft';
        }

        if ($e->status === 'approved' && self::normalize($e->workflow_status) === self::IN_PROGRESS) {
            return 'In Progress';
        }

        if (in_array(self::normalize($e->workflow_status), self::gateStatuses(), true)
            || ($e->status === 'pending' && $e->submitted_at)) {
            return 'Pending Approval';
        }

        return 'Pending Approval';
    }

    public static function dashboardBadgeClasses(Enrollment $e): string
    {
        return match (self::dashboardStatusLabel($e)) {
            'Completed' => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            'Approved' => 'bg-green-100 text-green-800 ring-green-200',
            'Pending Approval' => 'bg-amber-100 text-amber-900 ring-amber-200',
            'Rejected' => 'bg-rose-100 text-rose-800 ring-rose-200',
            'Draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'In Progress' => 'bg-sky-100 text-sky-800 ring-sky-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    public static function pendingSinceLabel(Enrollment $e): ?string
    {
        $anchor = $e->resubmitted_at ?? $e->submitted_at ?? null;
        if (!$anchor) {
            return null;
        }

        return $anchor->diffForHumans();
    }

    public static function isDraft(Enrollment $e): bool
    {
        return self::normalize($e->workflow_status) === self::DRAFT;
    }

    /** Saved draft — continue Step 1 data entry (not yet submitted for approval). */
    public static function canContinueDraftEntry(Enrollment $e): bool
    {
        if (self::isOnHold($e) || self::isRejected($e)) {
            return false;
        }

        return self::isDraft($e) && $e->submitted_at === null;
    }

    /** Approved — continue Steps 2–4 onboarding. */
    public static function canResumeWorkflow(Enrollment $e): bool
    {
        if ($e->status !== 'approved' || self::isOnHold($e) || self::isRejected($e)) {
            return false;
        }

        return self::normalize($e->workflow_status) !== self::COMPLETED || $e->is_step_incomplete;
    }

    public static function canResumeFromDashboard(Enrollment $e): bool
    {
        return self::canContinueDraftEntry($e) || self::canResumeWorkflow($e);
    }

    public static function dashboardResumeLabel(Enrollment $e): string
    {
        return self::canContinueDraftEntry($e) ? 'Continue' : 'Resume';
    }

    public static function approvalWaitLabel(Enrollment $e): ?string
    {
        if (!in_array(self::dashboardStatusLabel($e), ['Pending Approval'], true)) {
            return null;
        }

        $anchor = $e->resubmitted_at ?? $e->submitted_at;
        if (!$anchor) {
            return null;
        }

        return $anchor->diffForHumans(null, true);
    }
}
