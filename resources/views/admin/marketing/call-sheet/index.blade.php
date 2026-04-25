@extends('admin.layouts.app')

@section('title', 'Call Sheet')
@section('page-title', 'Marketing Call Sheet')

@section('content')
<section class="section-card" x-data="callSheetPage()">
    <div class="mb-5 flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div>
            <h3 class="section-title mb-1">Call sheet ({{ $callSheets->total() }})</h3>
            <p class="text-sm text-slate-600">Marketing list with month/year filtering, PDF view, edit, archive, and SMS actions.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 no-print">
            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-300" @click="openCreate()" title="Add New Call Sheet">
                <i class="ri-add-line"></i>
                <span>New Call Sheet</span>
            </button>

            <button type="button" class="btn btn-default" onclick="printCallSheet()" title="Print All">
                <i class="ri-printer-line"></i>
                <span>Print</span>
            </button>

            <a class="btn btn-primary" href="{{ route('admin.call-sheet.csv', request()->query()) }}" title="Export CSV">
                <i class="ri-file-excel-2-line"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 no-print">
        <form method="GET" action="{{ route('admin.call-sheet.index') }}" class="grid gap-3 md:grid-cols-12 md:items-end">
            <div class="md:col-span-4 lg:col-span-3">
                <label for="search_month" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search Month</label>
                <select name="search_month" id="search_month" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">---Select Month---</option>
                    @foreach($months as $month)
                        <option value="{{ $month }}" {{ $selectedMonth === $month ? 'selected' : '' }}>{{ $month }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4 lg:col-span-3">
                <label for="search_year" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search Year</label>
                <select name="search_year" id="search_year" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">---Select Year---</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4 lg:col-span-3 flex flex-wrap gap-2">
                <button type="submit" class="btn btn-success">Search</button>
                @if(!empty($selectedMonth) || !empty($selectedYear))
                    <a href="{{ route('admin.call-sheet.index') }}" class="btn btn-default">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div id="print_content" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="data-table min-w-[1100px]">
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($callSheets as $item)
                    <tr>
                        <td>{{ $callSheets->firstItem() + $loop->index }}</td>
                        <td>{{ $item->doctor_name ?: 'N/A' }}</td>
                        <td>
                            @php
                                $multiSpecIds = collect($item->call_sheet_specialization_ids ?? [])->map(fn($id) => (int) $id)->values();
                                $multiSpecNames = $multiSpecIds->map(fn($id) => $specializationMap[$id] ?? null)->filter()->values();
                                $specLabel = $multiSpecNames->isNotEmpty()
                                    ? $multiSpecNames->implode(', ')
                                    : ($item->specialization?->name ?: 'N/A');
                            @endphp
                            {{ $specLabel }}
                        </td>
                        <td>{{ $item->doctor_email ?: 'N/A' }}</td>
                        <td>{{ $item->mobile1 ?: 'N/A' }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200" title="Edit" @click="openEdit({{ $item->id }})">
                                    <i class="ri-pencil-line"></i>
                                </button>

                                <a href="{{ route('admin.call-sheet.pdf', $item) }}" target="_blank" class="inline-flex items-center gap-1 rounded-lg bg-blue-100 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-200" title="View PDF">
                                    <i class="ri-file-pdf-line"></i>
                                </a>

                                <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-cyan-100 px-3 py-2 text-xs font-semibold text-cyan-700 hover:bg-cyan-200" title="Send SMS" @click="sendSms({{ $item->id }})">
                                    <i class="ri-message-2-line"></i>
                                </button>

                                <form method="POST" action="{{ route('admin.call-sheet.destroy', $item) }}" onsubmit="return confirm('Archive this call sheet entry?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200" title="Delete">
                                        <i class="ri-delete-bin-5-line"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500">No data available in table</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($callSheets->hasPages())
        <div class="mt-4 no-print">{{ $callSheets->links() }}</div>
    @endif

    <div
        x-show="modalOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4 no-print"
        style="display: none;"
        @keydown.escape.window="closeModal()"
    >
        <div class="w-full max-w-xl rounded-2xl bg-white shadow-2xl" @click.away="closeModal()">
            <form :action="formAction" method="POST" class="overflow-hidden">
                @csrf
                <input type="hidden" name="_method" x-bind:value="formMethod">
                <input type="hidden" name="form_mode" x-bind:value="formMode">
                <input type="hidden" name="call_sheet_id" id="call_sheet_id" value="">

                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 class="text-lg font-semibold" x-text="modalTitle"></h3>
                    <button type="button" class="text-2xl leading-none text-slate-500 hover:text-slate-700" @click="closeModal()">&times;</button>
                </div>

                <div class="space-y-4 px-5 py-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Name</label>
                        <input type="text" name="doctor_name" id="doctor_name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" required>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold">Specialization</label>
                        <select name="specialization_ids[]" id="specialization_ids" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" multiple>
                            @foreach($specializations as $specialization)
                                <option value="{{ $specialization->id }}">{{ $specialization->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-slate-500">You can select multiple specializations.</p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold">Email</label>
                        <input type="email" name="doctor_email" id="doctor_email" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold">Phone</label>
                        <input type="text" name="mobile1" id="mobile1" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" class="btn btn-default" @click="closeModal()">Close</button>
                    <button type="submit" class="btn btn-primary" x-text="submitLabel">Submit</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    function callSheetPage() {
        return {
            modalOpen: false,
            modalTitle: 'Edit call sheet',
            submitLabel: 'Update',
            formAction: @json(route('admin.call-sheet.store')),
            formMethod: 'PUT',
            formMode: 'edit',
            closeModal() {
                this.modalOpen = false;
            },
            openCreate() {
                this.modalTitle = 'New call sheet';
                this.submitLabel = 'Save';
                this.formAction = @json(route('admin.call-sheet.store'));
                this.formMethod = 'POST';
                this.formMode = 'create';
                this.modalOpen = true;

                document.getElementById('call_sheet_id').value = '';
                document.getElementById('doctor_name').value = '';
                document.getElementById('doctor_email').value = '';
                document.getElementById('mobile1').value = '';

                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $('#specialization_ids').val([]).trigger('change');
                } else {
                    const select = document.getElementById('specialization_ids');
                    Array.from(select.options).forEach(function(option) {
                        option.selected = false;
                    });
                }
            },
            async openEdit(callSheetId) {
                try {
                    const response = await fetch(@json(url('/admin/call-sheet')).replace('/admin/call-sheet', '/admin/call-sheet/' + callSheetId + '/edit'), {
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json();
                    if (!payload.success) {
                        throw new Error('Unable to load call sheet data.');
                    }

                    const callSheet = payload.callSheet || {};
                    this.modalTitle = 'Edit call sheet';
                    this.submitLabel = 'Update';
                    this.formAction = @json(url('/admin/call-sheet')).replace('/admin/call-sheet', '/admin/call-sheet/' + callSheetId);
                    this.formMethod = 'PUT';
                    this.formMode = 'edit';
                    this.modalOpen = true;

                    document.getElementById('call_sheet_id').value = callSheet.id || '';
                    document.getElementById('doctor_name').value = callSheet.doctor_name || '';
                    document.getElementById('doctor_email').value = callSheet.doctor_email || '';
                    document.getElementById('mobile1').value = callSheet.mobile1 || '';

                    const selectedSpecializationIds = Array.isArray(callSheet.specialization_ids)
                        ? callSheet.specialization_ids.map(String)
                        : [];

                    if (typeof $ !== 'undefined' && $.fn.select2) {
                        $('#specialization_ids').val(selectedSpecializationIds).trigger('change');
                    } else {
                        const select = document.getElementById('specialization_ids');
                        Array.from(select.options).forEach(function(option) {
                            option.selected = selectedSpecializationIds.includes(option.value);
                        });
                    }
                } catch (error) {
                    alert(error.message || 'Unable to load call sheet data.');
                }
            },
            async sendSms(callSheetId) {
                try {
                    const response = await fetch(@json(url('/admin/call-sheet')).replace('/admin/call-sheet', '/admin/call-sheet/' + callSheetId + '/sms'), {
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json();
                    if (!payload.success || !payload.sms_url) {
                        alert('No valid mobile number found for SMS.');
                        return;
                    }

                    window.location.href = payload.sms_url;
                } catch (error) {
                    alert(error.message || 'Unable to prepare SMS link.');
                }
            },
        };
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#specialization_ids').select2({
                width: '100%',
                placeholder: '---Select specialization---',
                allowClear: true,
            });
        }

        const shouldOpenCreateModal = @json($errors->any() && old('form_mode') === 'create');
        if (!shouldOpenCreateModal) {
            return;
        }

        const alpineRoot = document.querySelector('[x-data="callSheetPage()"]');
        if (!alpineRoot || !alpineRoot.__x) {
            return;
        }

        const component = alpineRoot.__x.$data;
        component.openCreate();

        document.getElementById('doctor_name').value = @json(old('doctor_name', ''));
        document.getElementById('doctor_email').value = @json(old('doctor_email', ''));
        document.getElementById('mobile1').value = @json(old('mobile1', ''));

        const oldSpecializationIds = @json(array_map('strval', old('specialization_ids', [])));
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#specialization_ids').val(oldSpecializationIds).trigger('change');
        } else {
            const select = document.getElementById('specialization_ids');
            Array.from(select.options).forEach(function(option) {
                option.selected = oldSpecializationIds.includes(option.value);
            });
        }
    });

    function printCallSheet() {
        const content = document.getElementById('print_content');
        if (!content) return;

        const printWindow = window.open('', '_blank');
        if (!printWindow) return;

        printWindow.document.write(`
            <html>
                <head>
                    <title>Call Sheet</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; text-align: left; }
                        th { background: #f1f5f9; }
                    </style>
                </head>
                <body>
                    <h3>Call sheet</h3>
                    ${content.innerHTML}
                </body>
            </html>
        `);

        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
</script>
@endpush
