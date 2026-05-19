@php
    $cta = $workflowContinueCta ?? null;
    $locked = $workflowLockedCta ?? false;
    $tone = $cta['tone'] ?? 'blue';
    $toneMap = [
        'emerald' => ['border' => 'border-emerald-300', 'bg' => 'from-emerald-50 to-white', 'title' => 'text-emerald-950', 'desc' => 'text-emerald-800', 'btn' => 'bg-emerald-600 hover:bg-emerald-700', 'badge' => 'bg-emerald-100 text-emerald-800'],
        'amber' => ['border' => 'border-amber-300', 'bg' => 'from-amber-50 to-white', 'title' => 'text-amber-950', 'desc' => 'text-amber-900', 'btn' => 'bg-amber-600 hover:bg-amber-700', 'badge' => 'bg-amber-100 text-amber-900'],
        'blue' => ['border' => 'border-blue-300', 'bg' => 'from-blue-50 to-white', 'title' => 'text-blue-950', 'desc' => 'text-blue-900', 'btn' => 'bg-blue-600 hover:bg-blue-700', 'badge' => 'bg-blue-100 text-blue-900'],
    ];
    $t = $toneMap[$tone] ?? $toneMap['blue'];
@endphp

@if($locked)
    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-300 bg-gradient-to-br from-slate-50 to-white shadow-sm">
        <div class="flex flex-col gap-4 p-6 md:flex-row md:items-center md:justify-between">
            <div class="flex gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-200 text-slate-700">
                    <i class="ri-lock-line text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Workflow locked</h3>
                    <p class="mt-1 text-sm text-slate-600">This approved enrollment is view-only until an administrator verifies an OTP for temporary edit access. Use <strong>Request edit access</strong> in the header.</p>
                </div>
            </div>
        </div>
    </section>
@elseif($cta)
    <section class="mt-8 overflow-hidden rounded-2xl border {{ $t['border'] }} bg-gradient-to-br {{ $t['bg'] }} shadow-sm">
        <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex gap-4">
                <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl {{ $t['badge'] }} font-bold">
                    <span class="text-[10px] uppercase tracking-wide opacity-80">Step</span>
                    <span class="text-xl leading-none">{{ (int) ($cta['step'] ?? 1) }}</span>
                </div>
                <div>
                    <h3 class="text-lg font-bold {{ $t['title'] }}">{{ $cta['title'] }}</h3>
                    <p class="mt-1 max-w-xl text-sm {{ $t['desc'] }}">{{ $cta['description'] }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 lg:shrink-0">
                <a href="{{ $cta['url'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl px-6 py-3 text-sm font-bold text-white shadow-md transition {{ $t['btn'] }}">
                    {{ $cta['button_label'] }}
                    <i class="ri-arrow-right-line text-lg"></i>
                </a>
                @if(($canResumeWorkflow ?? false))
                    <a href="{{ route('admin.enrollment.resume', $enrollment) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        <i class="ri-play-line"></i>
                        Resume
                    </a>
                @endif
            </div>
        </div>
    </section>
@endif
