<?php

namespace App\Http\Middleware;

use App\Models\Enrollment;
use App\Services\EnrollmentRecordAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnrollmentRecordAccess
{
    public function __construct(
        private readonly EnrollmentRecordAccessService $recordAccess,
    ) {
    }

    public function handle(Request $request, Closure $next, string $routeParam = 'doctor'): Response
    {
        $enrollment = $this->resolveEnrollment($request, $routeParam);

        if (!$enrollment) {
            abort(404);
        }

        $this->recordAccess->assertCanAccessRecord(Auth::user(), $enrollment);

        $request->attributes->set('enrollment_record', $enrollment);

        return $next($request);
    }

    private function resolveEnrollment(Request $request, string $routeParam): ?Enrollment
    {
        $raw = $request->route($routeParam);

        if ($raw instanceof Enrollment) {
            return $raw;
        }

        return $this->recordAccess->resolveFromRouteKey($raw);
    }
}
