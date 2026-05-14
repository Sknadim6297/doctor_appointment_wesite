<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\EnrollmentEditAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentEditAccessController extends Controller
{
    public function __construct(
        private readonly EnrollmentEditAccessService $editAccessService,
    ) {
    }

    public function request(Request $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorizeEnrollment($enrollment);

        $result = $this->editAccessService->requestAccess($request, $enrollment, Auth::user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function verify(Request $request, Enrollment $enrollment): JsonResponse
    {
        if (!$this->isPrivilegedAdmin(Auth::user())) {
            abort(403, 'Only administrators can verify edit access OTPs.');
        }

        $data = $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $result = $this->editAccessService->verifyOtp($request, $enrollment, Auth::user(), $data['otp']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function status(Request $request, Enrollment $enrollment): JsonResponse
    {
        $this->authorizeEnrollment($enrollment);

        $state = $this->editAccessService->viewState($enrollment, Auth::user());

        return response()->json([
            'locked' => $state['locked'],
            'session_active' => $state['session_active'],
            'session_expires_at' => optional($state['session_expires_at'])->toIso8601String(),
            'pending_otp' => $state['pending_otp'],
            'pending_requester' => $state['requester']?->only(['id', 'name', 'email']),
            'can_request' => $state['can_request'],
        ]);
    }

    private function authorizeEnrollment(Enrollment $enrollment): void
    {
        if ($this->isPrivilegedAdmin(Auth::user())) {
            return;
        }

        $uid = (int) Auth::id();
        if ($uid <= 0 || ((int) $enrollment->created_by !== $uid && (int) $enrollment->agent_id !== $uid)) {
            abort(403, 'You can only access your own enrollment records.');
        }
    }

    private function isPrivilegedAdmin($user): bool
    {
        return (bool) ($user && (
            in_array(($user->role ?? null), ['admin', 'super_admin'], true) ||
            (method_exists($user, 'hasAdminRole') && $user->hasAdminRole(['admin', 'super_admin']))
        ));
    }
}
