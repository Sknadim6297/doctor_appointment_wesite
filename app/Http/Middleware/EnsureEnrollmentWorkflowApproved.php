<?php

namespace App\Http\Middleware;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentRecordAccessService;
use App\Support\EnrollmentWorkflow;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Employees may only continue enrollment workflow steps after admin approval.
 */
class EnsureEnrollmentWorkflowApproved
{
    public function __construct(
        private readonly EnrollmentRecordAccessService $recordAccess,
    ) {
    }

    public function handle(Request $request, Closure $next, string $routeParameter = 'enrollment'): Response
    {
        $enrollment = $request->route($routeParameter);

        if (!$enrollment instanceof Enrollment) {
            abort(404);
        }

        $user = $request->user() ?? Auth::user();

        if ($user) {
            $this->recordAccess->assertCanAccessRecord(
                $user,
                $enrollment,
                'You can only access your own enrollment records.'
            );
        }

        if ($this->mayContinueWithoutApproval($user, $enrollment)) {
            return $next($request);
        }

        if ($enrollment->status === 'approved') {
            return $next($request);
        }

        if (EnrollmentWorkflow::isOnHold($enrollment)) {
            return redirect()
                ->to($this->redirectTarget($user, $enrollment))
                ->with('error', 'This enrollment is on hold. Contact an administrator.');
        }

        if ($enrollment->status === 'rejected' || EnrollmentWorkflow::isRejected($enrollment)) {
            return redirect()
                ->to($this->redirectTarget($user, $enrollment))
                ->with('error', 'This enrollment was rejected. Edit and resubmit for approval before continuing.');
        }

        $redirect = $this->redirectTarget($user, $enrollment);

        return redirect()
            ->to($redirect)
            ->with('error', 'Wait for admin approval before continuing to the next step.');
    }

    private function mayContinueWithoutApproval(?User $user, Enrollment $enrollment): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isSuperAdmin($user)) {
            return true;
        }

        if (in_array(($enrollment->created_by_role ?? ''), ['super_admin', 'admin'], true)) {
            return true;
        }

        if (in_array(($user->role ?? null), ['admin', 'super_admin'], true)) {
            return true;
        }

        if (method_exists($user, 'hasAdminRole') && $user->exists && $user->hasAdminRole(['admin', 'super_admin'])) {
            return true;
        }

        return false;
    }

    private function isSuperAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (($user->role ?? null) === 'super_admin') {
            return true;
        }

        return $user->exists
            && method_exists($user, 'hasAdminRole')
            && $user->hasAdminRole('super_admin');
    }

    private function redirectTarget(?User $user, Enrollment $enrollment): string
    {
        if ($user && (int) $enrollment->created_by === (int) $user->id) {
            return route('admin.my-enrollments.show', $enrollment->id);
        }

        return route('admin.enrollment.details', $enrollment->id);
    }
}
