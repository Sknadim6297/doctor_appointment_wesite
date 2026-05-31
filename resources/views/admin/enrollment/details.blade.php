@extends('admin.layouts.app')

@section('title', 'Enrollment Details')
@section('page-title', 'Enrollment Details')

@section('content')
@php
    $na = 'N/A';
    $status = strtolower((string) ($enrollment->status ?? 'pending'));
    $planLabel = match ((int) ($enrollment->plan ?? 0)) {
        1 => 'Normal',
        2 => 'High Risk',
        3 => 'Combo',
        default => $na,
    };
    $paymentMethodLabel = match ((int) ($enrollment->payment_method ?? 0)) {
        1 => 'Cheque',
        2 => 'Cash',
        3 => 'UPI',
        default => $na,
    };
    $qualificationYears = is_array($enrollment->qualification_year) ? implode(', ', array_filter($enrollment->qualification_year)) : (string) ($enrollment->qualification_year ?? '');
    $qualificationLabel = '';
    if (is_array($enrollment->qualification)) {
        $qualificationLabel = implode(', ', array_map(fn($p) => is_array($p) ? ($p['name'] ?? '') : (string) $p, $enrollment->qualification));
    } else {
        $qualificationLabel = (string) ($enrollment->qualification ?? '');
    }
    $wfNorm = \App\Support\EnrollmentWorkflow::normalize($enrollment->workflow_status ?? '');
    $wfLabel = \App\Support\EnrollmentWorkflow::label($enrollment->workflow_status);
    $creator = $enrollment->creator;
    $approver = $enrollment->approver;
    $currentUser = auth()->user();
    $isPrivilegedAdmin = $currentUser && (
        in_array(($currentUser->role ?? null), ['admin', 'super_admin'], true) ||
        (method_exists($currentUser, 'hasAdminRole') && $currentUser->hasAdminRole(['admin', 'super_admin']))
    );
    $isSuperAdmin = $isSuperAdmin ?? (
        $currentUser && (
            (($currentUser->role ?? null) === 'super_admin') ||
            (method_exists($currentUser, 'hasAdminRole') && $currentUser->hasAdminRole('super_admin'))
        )
    );
    $bypassesApprovalWorkflow = $bypassesApprovalWorkflow ?? (
        $isSuperAdmin || in_array($enrollment->created_by_role ?? '', ['super_admin', 'admin'], true)
    );
    $ea = $editAccessState ?? ['locked' => false, 'session_active' => false, 'session_expires_at' => null, 'pending_otp' => false, 'requester' => null, 'can_request' => false];
    $editWorkflowUnlocked = $bypassesApprovalWorkflow || empty($ea['locked']) || !empty($ea['session_active']);
    $backRoute = $status === 'pending' && $isPrivilegedAdmin
        ? route('admin.enrollment.pending')
        : ($isPrivilegedAdmin ? route('admin.enrollment.monitoring', ['bucket' => 'incomplete']) : route('admin.my-enrollments.index'));
    $backLabel = $status === 'pending' && $isPrivilegedAdmin
        ? 'Pending Approvals'
        : ($isPrivilegedAdmin ? 'Enrollment CRM' : 'My Enrollments');
    $collapseSections = $isPrivilegedAdmin && $status !== 'pending';
    $sectionBodyClass = $collapseSections ? 'hidden' : '';
    $isOwner = $currentUser && (int) $enrollment->created_by === (int) $currentUser->id;
    $showEditAccessBanner = !empty($ea['locked'])
        && empty($ea['session_active'])
        && !$bypassesApprovalWorkflow
        && !$isOwner
        && $status === 'approved';
@endphp

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">{{ session('error') }}</div>
@endif

<!-- Header Navigation -->
<div class="mb-6 flex flex-wrap items-center gap-3">
    <a href="{{ $backRoute }}" class="btn btn-ghost">
        ← Back to {{ $backLabel }}
    </a>
    @if($isPrivilegedAdmin || $isSuperAdmin)
        <a href="{{ route('admin.enrollment.edit', $enrollment->id) }}" class="btn btn-primary">
            Edit enrollment
        </a>
    @elseif(!empty($ea['session_active']))
        <a href="{{ route('admin.enrollment.edit', $enrollment->id) }}" class="btn btn-primary">
            Edit enrollment (temporary access)
        </a>
    @endif
    @if($canResumeWorkflow ?? false)
        <a href="{{ route('admin.enrollment.resume', $enrollment) }}" class="btn btn-primary bg-blue-700 hover:bg-blue-800">
            Resume workflow
        </a>
    @endif
    @if(!empty($ea['can_request']) && empty($ea['pending_otp']))
        <button type="button" id="btnRequestEditAccess" class="btn rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">
            Request edit access
        </button>
    @endif
    @if($enrollment->isProductionActive())
        <a href="{{ route('admin.doctors.show', $enrollment->id) }}" class="btn rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-100">
            View active doctor profile
        </a>
    @endif
    @if(($canShowApprovalPanel ?? false) && $status === 'pending')
        <a href="#approval-decision" class="btn rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
            Approve / Reject
        </a>
    @endif
</div>

@if(!$enrollment->isProductionActive())
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        @if($enrollment->normalizedWorkflowStatus() === \App\Support\EnrollmentWorkflow::COMPLETED)
            <strong>Workflow completed.</strong> This record is not on the active Doctor List yet because required documents (Aadhaar, PAN, medical registration) still need verification.
        @else
            <strong>Enrollment pipeline.</strong> This record is not yet on the active Doctor List. It remains here until workflow is completed, approval is recorded, and required documents (Aadhaar, PAN, medical registration) are verified.
        @endif
    </div>
@endif

@if($showEditAccessBanner)
    <div class="mb-4 rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-800">
        <strong>View only.</strong> This enrollment is approved. Direct editing is disabled. Use <em>Request edit access</em> so an administrator can verify an OTP sent to their email and grant you a time-limited edit window.
    </div>
@endif

@if(!empty($ea['session_active']) && !empty($ea['session_expires_at']))
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        <span><strong>Temporary edit access active.</strong> Session ends at {{ $ea['session_expires_at']->format('d M Y, h:i A') }}.</span>
        <span id="editAccessCountdown" class="font-mono font-bold" data-expires-ts="{{ $ea['session_expires_at']->getTimestamp() }}">—</span>
    </div>
@endif

@if($isPrivilegedAdmin && !empty($ea['pending_otp']))
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 text-sm text-violet-900">
        <div>
            <strong>Edit access requested</strong>
            @if(!empty($ea['requester']))
                <span>by {{ $ea['requester']->name ?? 'Staff' }}.</span>
            @endif
            <span class="block text-xs text-violet-800 mt-1">Check administrator email for the OTP, then verify below.</span>
        </div>
        <button type="button" onclick="document.getElementById('verifyEditAccessModal').showModal()" class="rounded-lg bg-violet-700 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-600">
            Verify OTP
        </button>
    </div>
@endif

@if(!empty($ea['can_request']) && !empty($ea['pending_otp']))
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <strong>Request pending.</strong> An OTP has been emailed to administrators. Editing unlocks after they verify the OTP.
    </div>
@endif

<dialog id="verifyEditAccessModal" class="rounded-xl border border-slate-200 shadow-2xl backdrop:bg-black/50">
    <div class="w-full max-w-md p-6">
        <h2 class="mb-2 text-xl font-bold text-slate-900">Verify edit access OTP</h2>
        <p class="mb-4 text-sm text-slate-600">Enter the 6-digit code from the administrator email.</p>
        <form id="verifyEditAccessForm" class="space-y-4">
            @csrf
            <input type="text" name="otp" id="verifyEditOtpInput" maxlength="6" inputmode="numeric" pattern="[0-9]*" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-center text-lg tracking-widest" placeholder="000000" autocomplete="one-time-code">
            <p id="verifyEditAccessMsg" class="text-sm text-slate-600"></p>
            <div class="flex gap-2">
                <button type="button" onclick="document.getElementById('verifyEditAccessModal').close()" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 font-semibold">Cancel</button>
                <button type="submit" class="flex-1 rounded-lg bg-violet-700 px-4 py-2 font-semibold text-white hover:bg-violet-600">Verify</button>
            </div>
        </form>
    </div>
</dialog>

<!-- Main Header Card -->
<div class="mb-6 rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100 p-6 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $enrollment->doctor_name ?? $na }}</h1>
            <p class="mt-2 text-sm text-slate-600">Customer ID: <strong>{{ $enrollment->customer_id_no }}</strong></p>
            <p class="text-sm text-slate-600">Submitted on <strong>{{ optional($enrollment->created_at)->format('d M Y \a\t h:i A') ?? $na }}</strong></p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if($status === 'pending')
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-11.414a1 1 0 00-1.414-1.414L9 9.172 7.707 7.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Pending Approval
                </span>
            @elseif($status === 'approved')
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Approved
                </span>
            @elseif($status === 'rejected')
                <span class="inline-flex items-center gap-2 rounded-full bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    Rejected
                </span>
            @endif
        </div>
    </div>

    @if($isPrivilegedAdmin && $creator)
        <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-950">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Submitted by (enrollment entry)</p>
            <p class="mt-1 text-lg font-semibold text-slate-900">{{ $creator->name ?? 'Unknown' }}</p>
            <p class="mt-1 text-slate-700">
                Role: <strong>{{ $enrollment->created_by_role ?: ($creator->role ?? 'employee') }}</strong>
                @if($creator->email) · {{ $creator->email }} @endif
                @if($creator->phone) · {{ $creator->phone }} @endif
            </p>
            @if($status === 'pending')
                <p class="mt-2 text-xs text-blue-800">Review all sections below, then <strong>Approve enrollment</strong> at the bottom to unlock Steps 2–4 for this employee.</p>
            @elseif($status === 'approved')
                <p class="mt-2 text-xs text-blue-800">This enrollment is approved. Current workflow step: <strong>{{ (int) ($enrollment->current_step ?? 1) }} of 4</strong>.</p>
            @endif
        </div>
    @endif

    @if($status === 'pending' && !$bypassesApprovalWorkflow)
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            @if($isPrivilegedAdmin)
                <strong>Admin review:</strong> Verify proposer details, payment, and required documents (Aadhaar, PAN, medical registration) before approving.
            @else
                <strong>Waiting for approval:</strong> Step 2 is locked until an administrator approves this enrollment.
            @endif
        </div>
    @elseif($status === 'approved' && !$bypassesApprovalWorkflow)
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            @if($isPrivilegedAdmin && $approver)
                <strong>Approved</strong> by {{ $approver->name }} on {{ optional($enrollment->approved_at)->format('d M Y, h:i A') ?? '—' }}. The submitting employee can continue the workflow.
            @else
                <strong>Admin has approved your enrollment.</strong> You can proceed to the next step.
            @endif
        </div>
    @elseif($status === 'rejected')
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <strong>Rejected:</strong> {{ $enrollment->rejection_reason ?: 'No rejection reason recorded.' }}
        </div>
    @endif

    @if(($isOnHold ?? false) || \App\Support\EnrollmentWorkflow::isOnHold($enrollment))
        <div class="mt-4 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-950">
            <strong>On hold</strong>@if($enrollment->hold_reason) — {{ $enrollment->hold_reason }}@endif
        </div>
    @endif
</div>

<!-- Pipeline: steps + activity -->
<div class="mb-6 grid gap-4 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Step progress</h3>
        <p class="mt-1 text-xs text-slate-500">Current step {{ (int) ($enrollment->current_step ?? 1) }} · Last activity {{ optional($enrollment->last_activity_at)->format('d M Y, h:i A') ?? $na }}</p>
        <ol class="mt-4 space-y-3">
            @foreach($workflowSteps ?? [] as $step)
                @php
                    $st = $step['state'] ?? 'pending';
                    $dot = $st === 'completed' ? 'bg-emerald-500' : ($st === 'current' ? 'bg-blue-600 ring-4 ring-blue-100' : 'bg-slate-300');
                @endphp
                <li class="flex gap-3">
                    <span class="mt-1 h-3 w-3 shrink-0 rounded-full {{ $dot }}"></span>
                    <div>
                        <p class="text-sm font-semibold text-slate-900">Step {{ $step['step'] ?? '' }} — {{ $step['label'] ?? '' }}</p>
                        <p class="text-xs capitalize text-slate-500">{{ $st }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
        <div class="mt-4 rounded-xl bg-slate-50 px-3 py-2 text-xs text-slate-600">
            <span class="font-semibold text-slate-800">Workflow:</span>
            <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 font-semibold ring-1 ring-inset {{ \App\Support\EnrollmentWorkflow::badgeClasses($enrollment->workflow_status) }}">{{ \App\Support\EnrollmentWorkflow::displayStatus($enrollment) }}</span>
        </div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold uppercase tracking-wide text-slate-500">Activity timeline</h3>
        <p class="mt-1 text-xs text-slate-500">Who changed what (Admin / Sub Admin / Super Admin)</p>
        <div class="mt-4 max-h-72 space-y-3 overflow-y-auto pr-1 text-sm">
            @forelse($activityTimeline ?? [] as $log)
                <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="font-semibold text-slate-900">{{ $log->actor?->name ?? 'System' }}</span>
                        <span class="text-xs text-slate-500">{{ optional($log->occurred_at)->format('d M H:i') }}</span>
                    </div>
                    <p class="text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $log->action ?? '')) }} · {{ str_replace('_', ' ', $log->actor?->role ?? '') }}</p>
                    @if($log->description)
                        <p class="mt-1 text-xs text-slate-700">{{ $log->description }}</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-500">No logged activity yet.</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Quick Summary Cards -->
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
        <p class="text-xs font-semibold uppercase text-slate-500">Submitted By</p>
        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $creator?->name ?? $na }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ $enrollment->created_by_role ?: ($creator?->role ?? $na) }}</p>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
        <p class="text-xs font-semibold uppercase text-slate-500">Plan & Coverage</p>
        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $planLabel }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ $enrollment->specialization?->name ?: $na }}</p>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
        <p class="text-xs font-semibold uppercase text-slate-500">Amount</p>
        <p class="mt-2 text-lg font-semibold text-emerald-600">₹ {{ number_format((float) ($enrollment->total_amount ?? 0), 2) }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ $enrollment->payment_mode ?: $na }}</p>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
        <p class="text-xs font-semibold uppercase text-slate-500">Status</p>
        <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\EnrollmentWorkflow::displayStatus($enrollment) }}</p>
        <p class="mt-1 text-xs text-slate-500">Legacy status: {{ ucfirst($status) }}</p>
        @if($enrollment->approved_by)
            <p class="mt-1 text-xs text-slate-600">by {{ $approver?->name ?? 'Unknown' }}</p>
        @endif
    </div>
</div>

<!-- Expandable Sections -->
<div class="space-y-4">
    <!-- Section 1: Workflow & Creator Info -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Workflow & Creator Information</h3>
        </button>
        <div data-section class="border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Created By</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $creator?->name ?? $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Creator Role</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ ucfirst($enrollment->created_by_role ?: ($creator?->role ?? $na)) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Creator Email</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $creator?->email ?? $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Creator Mobile</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $creator?->phone ?? $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Created At</p>
                    <p class="mt-1 text-sm text-slate-700">{{ optional($enrollment->created_at)->format('d M Y h:i A') ?? $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Created From</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $latestActivity?->ip_address ?? $na }}</p>
                    @if(!empty($latestActivity?->user_agent))
                        <p class="mt-1 truncate text-xs text-slate-600">{{ $latestActivity->user_agent }}</p>
                    @endif
                </div>
                @if($enrollment->approved_by)
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Approved By</p>
                        <p class="mt-1 text-base font-semibold text-emerald-600">{{ $approver?->name ?? $na }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Approved At</p>
                        <p class="mt-1 text-sm text-slate-700">{{ optional($enrollment->approved_at)->format('d M Y h:i A') ?? $na }}</p>
                    </div>
                @endif
                @if($enrollment->approval_remarks)
                    <div class="md:col-span-2 lg:col-span-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Approval Remarks</p>
                        <p class="mt-1 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">{{ $enrollment->approval_remarks }}</p>
                    </div>
                @endif
                @if($enrollment->rejection_reason)
                    <div class="md:col-span-2 lg:col-span-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Rejection Reason</p>
                        <p class="mt-1 rounded-lg bg-rose-50 p-3 text-sm text-rose-900">{{ $enrollment->rejection_reason }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Section 2: Official Details -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Official Details</h3>
        </button>
        <div data-section class="{{ $sectionBodyClass }} border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Customer ID No</p>
                    <p class="mt-1 text-base font-mono font-semibold text-slate-900">{{ $enrollment->customer_id_no ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Money Receipt No</p>
                    <p class="mt-1 text-base font-mono font-semibold text-slate-900">{{ $enrollment->money_rc_no ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Broker / Agent Name</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->agent_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Agent Phone No</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->agent_phone_no ?: $na }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Uploaded Files -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Uploaded Files & Documents</h3>
        </button>
        <div data-section class="{{ $sectionBodyClass }} border-t border-slate-200 px-6 py-4">
            @include('admin.enrollment.partials.documents-summary', ['enrollment' => $enrollment, 'documentSummary' => $documentSummary ?? null])
        </div>
    </div>

    <!-- Section 4: Proposer Details -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Proposer's Personal Details</h3>
        </button>
        <div data-section class="{{ $sectionBodyClass }} border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Full Name</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->doctor_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Date of Birth</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ optional($enrollment->dob)->format('d/m/Y') ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Email</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $enrollment->doctor_email ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Mobile 1</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->mobile1 ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Mobile 2</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->mobile2 ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Qualification</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $qualificationLabel ?: $na }}</p>
                </div>
                @if($qualificationYears !== '')
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Qualification Years</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $qualificationYears }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Medical Reg No</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->medical_registration_no ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Year of Registration</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->displayYearOfReg() ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Aadhaar Card</p>
                    <p class="mt-1 font-mono text-slate-900">{{ $enrollment->aadhar_card_no ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">PAN Card</p>
                    <p class="mt-1 font-mono text-slate-900">{{ $enrollment->pan_card_no ?: $na }}</p>
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Address</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->doctor_address ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Country</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->country_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">State</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->state_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">City</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->city_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Postcode</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->postcode ?: $na }}</p>
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Clinic Address</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->clinic_address ?: $na }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 5: Payment Details -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Payment & Coverage Information</h3>
        </button>
        <div data-section class="{{ $sectionBodyClass }} border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Specialization</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->specialization?->name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Plan Type</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $planLabel }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Payment Mode</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->payment_mode ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Coverage/Legal Service</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->formattedCoverageLabel() }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Insurance Amount</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">₹ {{ number_format((float) ($enrollment->service_amount ?? 0), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Medeforum Amount</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">₹ {{ number_format((float) ($enrollment->payment_amount ?? 0), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Total Amount</p>
                    <p class="mt-1 text-lg font-bold text-emerald-600">₹ {{ number_format((float) ($enrollment->total_amount ?? 0), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Payment Method</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->paymentMethodLabel() }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Cheque No</p>
                    <p class="mt-1 font-mono text-slate-900">{{ $enrollment->payment_cheque ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Bank Name</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->payment_bank_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Branch Name</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->payment_branch_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">UPI Transaction ID</p>
                    <p class="mt-1 font-mono text-slate-900">{{ $enrollment->payment_upi_transaction_id ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Cash Date</p>
                    <p class="mt-1 text-slate-900">{{ \App\Support\AdminDateFormat::display($enrollment->payment_cash_date, $na) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Send Bond to Email</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->bond_to_mail ? '✓ Yes' : '✗ No' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Actions (at gate only) -->
@if($canShowApprovalPanel ?? false)
    <div id="approval-decision" class="approval-decision-panel my-8 scroll-mt-6 rounded-2xl border border-amber-300 bg-white p-6 shadow-sm">
        <h3 class="mb-2 flex items-center gap-2 text-xl font-bold text-slate-900">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-amber-800">
                <i class="ri-shield-check-line text-lg"></i>
            </span>
            Approval decision
        </h3>
        <p class="mb-5 text-sm text-slate-600">Review Step 1, then approve to unlock Steps 2–4 for the employee.</p>

        <div class="flex flex-wrap gap-3">
            <button type="button" onclick="document.getElementById('approveModal').showModal()"
                    class="approval-action-btn border-emerald-700 bg-emerald-600 hover:bg-emerald-700">
                <i class="ri-check-double-line text-lg"></i> Approve enrollment
            </button>
            <button type="button" onclick="document.getElementById('rejectModal').showModal()"
                    class="approval-action-btn border-rose-700 bg-rose-600 hover:bg-rose-700">
                <i class="ri-close-circle-line text-lg"></i> Reject enrollment
            </button>
            @if($canHoldEnrollment ?? false)
                <button type="button" onclick="document.getElementById('holdModal').showModal()"
                        class="approval-action-btn border-orange-700 bg-orange-600 hover:bg-orange-700">
                    <i class="ri-pause-circle-line text-lg"></i> Hold
                </button>
            @endif
            @if($canReturnForCorrection ?? false)
                <button type="button" onclick="document.getElementById('returnModal').showModal()"
                        class="approval-action-btn border-violet-700 bg-violet-600 hover:bg-violet-700">
                    <i class="ri-arrow-go-back-line text-lg"></i> Return for correction
                </button>
            @endif
        </div>
    </div>

    <dialog id="holdModal" class="rounded-xl border border-slate-200 shadow-2xl backdrop:bg-black/50">
        <div class="w-full max-w-md p-6">
            <h2 class="mb-2 text-xl font-bold text-slate-900">Place on hold</h2>
            <form action="{{ route('admin.enrollment.hold', $enrollment->id) }}" method="post" class="space-y-4">
                @csrf
                <textarea name="hold_reason" class="w-full rounded-lg border border-slate-300 px-4 py-2" rows="4" placeholder="Hold reason (required)" required minlength="3"></textarea>
                <div class="flex gap-2">
                    <button type="button" onclick="document.getElementById('holdModal').close()" class="flex-1 rounded-lg border px-4 py-2 font-semibold">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-orange-600 px-4 py-2 font-semibold text-white">Confirm</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Approve Modal -->
    <dialog id="approveModal" class="rounded-xl border border-slate-200 shadow-2xl backdrop:bg-black/50">
        <div class="w-full max-w-md p-6">
            <h2 class="mb-4 text-2xl font-bold text-slate-900">Approve Enrollment</h2>
            <form action="{{ route('admin.enrollment.approve', $enrollment->id) }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900">Approval Remarks (Optional)</label>
                    <textarea name="approval_remarks" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-emerald-500 focus:outline-none" rows="4" placeholder="Add approval notes..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('approveModal').close()" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white hover:bg-emerald-700">Confirm</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Reject Modal -->
    <dialog id="rejectModal" class="rounded-xl border border-slate-200 shadow-2xl backdrop:bg-black/50">
        <div class="w-full max-w-md p-6">
            <h2 class="mb-4 text-2xl font-bold text-slate-900">Reject Enrollment</h2>
            <form action="{{ route('admin.enrollment.reject', $enrollment->id) }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900">Rejection Reason</label>
                    <textarea name="rejection_reason" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-rose-500 focus:outline-none" rows="3" placeholder="Explain rejection..." required></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900">Internal Remarks (Optional)</label>
                    <textarea name="approval_remarks" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-rose-500 focus:outline-none" rows="3" placeholder="Internal notes..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('rejectModal').close()" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-rose-600 px-4 py-2 font-semibold text-white hover:bg-rose-700">Confirm</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Return for correction -->
    <dialog id="returnModal" class="rounded-xl border border-slate-200 shadow-2xl backdrop:bg-black/50">
        <div class="w-full max-w-md p-6">
            <h2 class="mb-4 text-2xl font-bold text-slate-900">Send back for correction</h2>
            <form action="{{ route('admin.enrollment.return-for-correction', $enrollment->id) }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900">Instructions for staff <span class="text-red-500">*</span></label>
                    <textarea name="approval_remarks" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-violet-500 focus:outline-none" rows="4" placeholder="Describe what must be corrected..." required></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('returnModal').close()" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-violet-600 px-4 py-2 font-semibold text-white hover:bg-violet-700">Send back</button>
                </div>
            </form>
        </div>
    </dialog>
@endif

@if($canReleaseHold ?? false)
    <section class="my-8 rounded-2xl border border-orange-300 bg-orange-50 p-6">
        <h3 class="text-lg font-bold text-orange-950">Enrollment on hold</h3>
        <p class="mt-2 text-sm text-orange-900">{{ $enrollment->hold_reason }}</p>
        <form action="{{ route('admin.enrollment.release-hold', $enrollment->id) }}" method="post" class="mt-4">
            @csrf
            <button type="submit" class="rounded-lg bg-orange-700 px-6 py-2.5 text-sm font-semibold text-white hover:bg-orange-800">Release hold</button>
        </form>
    </section>
@endif

@include('admin.enrollment.partials.workflow-continue-cta', [
    'enrollment' => $enrollment,
    'workflowContinueCta' => $workflowContinueCta ?? null,
    'workflowLockedCta' => $workflowLockedCta ?? false,
    'canResumeWorkflow' => $canResumeWorkflow ?? false,
])

@push('scripts')
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]');
    csrf = csrf ? csrf.getAttribute('content') : '';
    var headers = {
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json'
    };

    function startCountdown(el) {
        if (!el) return;
        var ts = parseInt(el.getAttribute('data-expires-ts'), 10);
        if (!ts || isNaN(ts)) return;
        function tick() {
            var left = ts - Math.floor(Date.now() / 1000);
            if (left <= 0) {
                el.textContent = 'Expired — refreshing…';
                window.setTimeout(function () { window.location.reload(); }, 800);
                return;
            }
            var m = Math.floor(left / 60), s = left % 60;
            el.textContent = m + 'm ' + (s < 10 ? '0' : '') + s + 's';
        }
        tick();
        window.setInterval(tick, 1000);
    }
    startCountdown(document.getElementById('editAccessCountdown'));

    var btnReq = document.getElementById('btnRequestEditAccess');
    if (btnReq) {
        btnReq.addEventListener('click', function () {
            btnReq.disabled = true;
            fetch(@json(route('admin.enrollment.edit-access.request', $enrollment)), {
                method: 'POST',
                headers: headers,
                body: '{}'
            })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                .then(function (x) {
                    alert(x.j.message || (x.ok ? 'OK' : 'Request failed'));
                    if (x.j.success) window.location.reload();
                })
                .catch(function () { alert('Request failed'); })
                .finally(function () { btnReq.disabled = false; });
        });
    }

    var form = document.getElementById('verifyEditAccessForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = document.getElementById('verifyEditOtpInput');
            var msg = document.getElementById('verifyEditAccessMsg');
            var otp = (input && input.value) ? String(input.value).replace(/\D/g, '').trim() : '';
            if (msg) msg.textContent = '';
            fetch(@json(route('admin.enrollment.edit-access.verify', $enrollment)), {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({ otp: otp })
            })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
                .then(function (x) {
                    if (msg) msg.textContent = (x.j && x.j.message) ? x.j.message : '';
                    if (x.j && x.j.success) window.location.reload();
                })
                .catch(function () { if (msg) msg.textContent = 'Verification failed'; });
        });
    }
})();
</script>
@endpush
@endsection
