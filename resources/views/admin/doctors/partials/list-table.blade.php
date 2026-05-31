<div class="overflow-x-auto">
    <table class="doctor-table w-full" style="min-width: 1400px;">
        <thead>
            <tr>
                <th style="width: 42px;">SL</th>
                <th>Name / Phone</th>
                <th>Speciality &amp; Plan</th>
                <th>Policy No / Membership</th>
                <th>Insurance coverage</th>
                <th>Insurance amt</th>
                <th>Medeforum amt</th>
                <th>Policy received</th>
                <th>Last renewed</th>
                <th>Next renewal</th>
                <th>Payment date</th>
                <th>Status</th>
                <th>Notify</th>
                <th class="actions-col" style="min-width: 300px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($doctors as $doctor)
                @php
                    $policy = $doctor->latestPolicyReceipt;
                    $nextRenewal = $doctor->renewal_date
                        ?? ($doctor->created_at ? $doctor->created_at->copy()->addYear() : null);
                    $lastRenewed = $doctor->last_renewal_date
                        ?? $policy?->last_renewed_date
                        ?? $policy?->receive_date;
                    $daysUntilRenewal = $nextRenewal ? now()->diffInDays($nextRenewal, false) : null;
                    $paymentDate = $doctor->payment_cash_date ?? $doctor->policy_date ?? $doctor->approved_at;
                @endphp
                <tr>
                    <td class="font-semibold">{{ $doctors->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="font-semibold text-slate-800">{{ $doctor->doctor_name ?? '—' }}</div>
                        @if(!empty($doctor->doctor_money_reciept_no) || !empty($doctor->money_rc_no))
                            <div class="text-xs text-emerald-700 font-medium mt-0.5">
                                MR {{ $doctor->doctor_money_reciept_no ?? $doctor->money_rc_no }}@if(!empty($doctor->doctor_money_reciept_year)) ({{ $doctor->doctor_money_reciept_year }})@endif
                            </div>
                        @endif
                        <div class="text-xs text-slate-500">{{ $doctor->mobile1 ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="text-sm">{{ $doctor->displaySpecializationName() ?? '—' }}</div>
                        <div class="mt-0.5">
                            @php $planLabel = $doctor->planLabel(); @endphp
                            @if($planLabel !== '—')
                                <span class="doctor-pill doctor-pill-renewal">{{ $planLabel }}</span>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="text-sm font-mono">{{ $policy?->policy_no ?: '—' }}</div>
                        <div class="text-xs text-slate-500">{{ $doctor->customer_id_no ?? '—' }}</div>
                        @if($doctor->money_rc_no)
                            <div class="text-xs text-slate-500">MR: {{ $doctor->money_rc_no }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="text-sm">{{ $doctor->formattedCoverageLabel() }}</div>
                        @if($policy?->policy_start_date && $policy?->policy_end_date)
                            <div class="text-xs text-slate-500">
                                {{ $policy->policy_start_date->format('d M Y') }} – {{ $policy->policy_end_date->format('d M Y') }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <div class="text-sm font-semibold whitespace-nowrap">
                            @if($doctor->service_amount)
                                Rs. {{ number_format((float) $doctor->service_amount, 0) }}
                            @else
                                —
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="text-sm font-semibold whitespace-nowrap">
                            @if($doctor->payment_amount)
                                Rs. {{ number_format((float) $doctor->payment_amount, 0) }}
                            @else
                                —
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="text-sm whitespace-nowrap">{{ optional($policy?->receive_date ?? $doctor->policy_date)->format('d M Y') ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="text-sm whitespace-nowrap">{{ optional($lastRenewed)->format('d M Y') ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="text-sm whitespace-nowrap">{{ optional($nextRenewal)->format('d M Y') ?? '—' }}</div>
                    </td>
                    <td>
                        <div class="text-sm whitespace-nowrap">{{ \App\Support\AdminDateFormat::display($paymentDate) }}</div>
                    </td>
                    <td>
                        @if($daysUntilRenewal === null)
                            <span class="text-xs text-slate-400">—</span>
                        @elseif($daysUntilRenewal > 30)
                            <span class="doctor-pill doctor-pill-renewal">Upcoming</span>
                        @elseif($daysUntilRenewal > 0)
                            <span class="doctor-pill doctor-pill-upcoming">Due soon</span>
                        @else
                            <span class="doctor-pill doctor-pill-due">Overdue</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-col gap-1 text-xs">
                            <span class="inline-flex items-center gap-1" title="{{ $doctor->bond_to_mail ? 'Auto email on' : 'Auto email off' }}">
                                <i class="ri-mail-line" style="color: {{ $doctor->bond_to_mail ? '#16a34a' : '#94a3b8' }};"></i>
                                Email
                            </span>
                            <span class="inline-flex items-center gap-1" title="{{ $doctor->auto_sms_enabled ? 'Auto SMS on' : 'Auto SMS off' }}">
                                <i class="ri-message-2-line" style="color: {{ $doctor->auto_sms_enabled ? '#16a34a' : '#94a3b8' }};"></i>
                                SMS
                            </span>
                        </div>
                    </td>
                    <td class="actions-cell">
                        <div class="flex flex-wrap gap-1">
                            <a href="{{ route('admin.doctors.show', $doctor->id) }}" class="doctor-action-btn doctor-action-btn-view" title="View Details" onclick="event.stopPropagation();">
                                <i class="ri-eye-line"></i>
                            </a>
                            <a href="{{ route('admin.enrollment.legacy-edit', $doctor->id) }}" class="doctor-action-btn doctor-action-btn-edit" title="Edit" onclick="event.stopPropagation();">
                                <i class="ri-pencil-line"></i>
                            </a>
                            <a target="_blank" href="{{ route('admin.doctors.show', $doctor->id) }}?tab=doctor_policy_tab" class="doctor-action-btn doctor-action-btn-doc" title="Policy" onclick="event.stopPropagation();">
                                <i class="ri-file-shield-line"></i>
                            </a>
                            <button type="button" class="doctor-action-btn doctor-action-btn-renew" title="Renew" onclick="event.stopPropagation(); renewDoctor({{ $doctor->id }})">
                                <i class="ri-refresh-line"></i>
                            </button>
                            <button type="button" class="doctor-action-btn doctor-action-btn-mail" title="Send Email" onclick="event.stopPropagation(); sendMail({{ $doctor->id }}, @json($doctor->doctor_email))">
                                <i class="ri-mail-line"></i>
                            </button>
                            <button type="button" class="doctor-action-btn doctor-action-btn-sms" title="Send SMS" onclick="event.stopPropagation(); sendSms({{ $doctor->id }}, @json($doctor->mobile1))">
                                <i class="ri-message-2-line"></i>
                            </button>
                            <button type="button" class="doctor-action-btn doctor-action-btn-bond" title="Resend Bond" onclick="event.stopPropagation(); resendBond({{ $doctor->id }}, @json($doctor->doctor_email))">
                                <i class="ri-send-plane-line"></i>
                            </button>
                            <button type="button" class="doctor-action-btn doctor-action-btn-receipt" title="Resend Receipt" onclick="event.stopPropagation(); resendReceipt({{ $doctor->id }}, @json($doctor->doctor_email))">
                                <i class="ri-mail-send-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" class="text-center py-8 text-slate-500">
                        No active doctors found. Approved enrollments appear here after workflow completion and document verification.
                        <a href="{{ route('admin.enrollment.monitoring') }}" class="text-blue-600 hover:underline">View enrollment pipeline</a>
                        or <a href="{{ route('admin.enrollment.create') }}" class="text-blue-600 hover:underline">create enrollment</a>.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
