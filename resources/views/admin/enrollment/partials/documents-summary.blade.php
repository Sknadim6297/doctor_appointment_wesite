@php
    use App\Support\DoctorDocumentCatalog;

    $summary = $documentSummary ?? ['grouped' => [], 'slots' => [], 'documents' => collect()];
    $slots = $summary['slots'] ?? [];
    $allDocs = collect($summary['documents'] ?? []);
    $policyReceipts = $enrollment->policyReceipts ?? collect();
    $currentSection = null;
    $na = 'Not uploaded';
@endphp

<div class="enrollment-documents-summary space-y-4">
    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <strong>{{ $allDocs->count() }}</strong> enrollment document(s) on file.
        @if($policyReceipts->isNotEmpty())
            <span class="ml-1"><strong>{{ $policyReceipts->count() }}</strong> policy receipt(s).</span>
        @endif
        Required identity documents: Aadhaar, PAN, and Medical Registration certificate.
    </div>

    @foreach($slots as $slot)
        @if($currentSection !== ($slot['section'] ?? ''))
            @php $currentSection = $slot['section'] ?? ''; @endphp
            <h4 class="text-sm font-bold uppercase tracking-wide text-slate-600">{{ $currentSection }}</h4>
        @endif

        @php $uploaded = collect($slot['documents'] ?? []); @endphp

        <div class="rounded-lg border border-slate-200 bg-white p-4 {{ $uploaded->isEmpty() && !empty($slot['required']) ? 'border-amber-300 bg-amber-50/40' : '' }}">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <p class="font-semibold text-slate-900">
                        {{ $slot['title'] ?? 'Document' }}
                        @if(!empty($slot['required']))
                            <span class="ml-1 text-xs font-bold text-red-600">Required</span>
                        @else
                            <span class="ml-1 text-xs font-medium text-slate-500">Optional</span>
                        @endif
                    </p>
                    <p class="text-xs text-slate-500">{{ DoctorDocumentCatalog::categoryLabels()[$slot['category'] ?? ''] ?? '' }}</p>
                </div>
                @if($uploaded->isEmpty())
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $na }}</span>
                @else
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">{{ $uploaded->count() }} file(s)</span>
                @endif
            </div>

            @if($uploaded->isNotEmpty())
                <ul class="mt-3 space-y-2">
                    @foreach($uploaded as $doc)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                            <div>
                                <div class="font-medium text-slate-800">{{ $doc->displayTitle() }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $doc->displayFilename() }}
                                    · {{ optional($doc->created_at)->format('d M Y, h:i A') ?? '—' }}
                                    @if($doc->verification_status)
                                        · {{ $doc->statusLabel() }}
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2 text-xs font-semibold whitespace-nowrap">
                                @if($doc->fileExists())
                                    <a href="{{ route('admin.doctors.documents.view', [$enrollment->id, $doc->id]) }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">View</a>
                                    <a href="{{ route('admin.doctors.documents.download', [$enrollment->id, $doc->id]) }}" class="text-blue-600 hover:underline">Download</a>
                                @else
                                    <span class="text-red-600">File missing</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endforeach

    <h4 class="text-sm font-bold uppercase tracking-wide text-slate-600 pt-2">Policy receipts (Step 3)</h4>
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
        @if($policyReceipts->isNotEmpty())
            <ul class="space-y-2">
                @foreach($policyReceipts as $policyReceipt)
                    <li class="rounded-md border border-slate-200 bg-white p-3 text-sm">
                        <div class="font-semibold text-slate-900">{{ $policyReceipt->policy_no ?: 'Policy receipt' }}</div>
                        <div class="mt-1 text-xs text-slate-600">
                            Received: {{ optional($policyReceipt->receive_date)->format('d M Y') ?: '—' }}
                            @if($policyReceipt->policy_start_date && $policyReceipt->policy_end_date)
                                · {{ $policyReceipt->policy_start_date->format('d M Y') }} – {{ $policyReceipt->policy_end_date->format('d M Y') }}
                            @endif
                        </div>
                        @if($policyReceipt->policy_file)
                            <a class="mt-2 inline-block text-xs font-semibold text-blue-700 hover:underline" href="{{ asset('storage/' . $policyReceipt->policy_file) }}" target="_blank" rel="noopener">View policy file</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-blue-800">No policy receipts uploaded yet.</p>
        @endif
    </div>

    @php
        $workflowExtras = $allDocs->filter(fn ($doc) => !in_array($doc->source, [DoctorDocumentCatalog::SOURCE_ENROLLMENT_STEP1], true));
    @endphp
    @if($workflowExtras->isNotEmpty())
        <h4 class="text-sm font-bold uppercase tracking-wide text-slate-600 pt-2">Other workflow documents</h4>
        <ul class="space-y-2">
            @foreach($workflowExtras as $doc)
                <li class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-purple-200 bg-purple-50 px-3 py-2 text-sm">
                    <div>
                        <span class="font-semibold text-slate-900">{{ $doc->displayTitle() }}</span>
                        <span class="text-xs text-slate-500"> · {{ $doc->categoryLabel() }}</span>
                    </div>
                    @if($doc->fileExists())
                        <a href="{{ asset('storage/' . $doc->document_file) }}" target="_blank" rel="noopener" class="text-xs font-semibold text-blue-600 hover:underline">View</a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</div>
