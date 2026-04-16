@extends('admin.layouts.app')

@section('title', 'Doctor Cases')
@section('page-title', 'Doctor Cases')

@section('content')
@php
    $caseRows = $cases;
    $doctorOptions = $doctors ?? collect();
@endphp

<style>
    .case-shell { border: 1px solid #dbe3ee; border-radius: 14px; background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%); }
    .case-toolbar { border: 1px solid #dbeafe; background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%); }
    .case-table { border-collapse: separate; border-spacing: 0; border: 1px solid #dbe3ee; border-radius: 0.85rem; overflow: hidden; }
    .case-table thead th { background: #0f172a; color: #e2e8f0; font-size: 0.74rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; padding: 0.75rem; border-bottom: 1px solid #1e293b; white-space: nowrap; }
    .case-table tbody td { vertical-align: middle; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-size: 0.82rem; padding: 0.75rem; }
    .case-table tbody tr:nth-child(even) { background: #f8fafc; }
    .case-table tbody tr:hover { background: #eef6ff; }
    .case-pill { display: inline-flex; align-items: center; border-radius: 9999px; padding: 0.25rem 0.6rem; font-size: 0.7rem; font-weight: 700; line-height: 1; }
    .case-pill-direct { background: #dbeafe; color: #0369a1; }
    .case-pill-regular { background: #fef3c7; color: #92400e; }
    .case-action-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0.25rem; border-radius: 0.35rem; color: #fff; font-size: 0.75rem; font-weight: 700; text-decoration: none; transition: all 0.2s ease; border: 0; }
    .case-action-btn:hover { transform: translateY(-2px); }
    .case-action-view { background: #10b981; }
    .case-action-edit { background: #6b7280; }
    .case-action-delete { background: #ef4444; }
    .case-action-copy { background: #3b82f6; }
    .case-modal-backdrop { position: fixed; inset: 0; z-index: 60; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, 0.62); padding: 1rem; }
    .case-modal { width: min(100%, 980px); max-height: calc(100vh - 2rem); overflow: auto; border-radius: 18px; border: 1px solid #dbe3ee; background: #fff; box-shadow: 0 24px 80px rgba(15, 23, 42, 0.35); }
    .case-modal-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; }
    .case-modal-body { padding: 1.25rem; }
    .case-modal-foot { display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #e2e8f0; padding: 1rem 1.25rem; background: #f8fafc; }
    .case-grid { display: grid; gap: 0.85rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .case-grid-2 { display: grid; gap: 0.85rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
    @media (min-width: 768px) { .case-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .case-field label { display: block; margin-bottom: 0.35rem; font-size: 0.82rem; font-weight: 700; color: #334155; }
    .case-field input, .case-field select, .case-field textarea { width: 100%; border-radius: 0.7rem; border: 1px solid #cbd5e1; padding: 0.7rem 0.85rem; font-size: 0.9rem; color: #0f172a; }
    .case-field textarea { min-height: 96px; resize: vertical; }
    .case-field input:focus, .case-field select:focus, .case-field textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
    .case-help { margin-top: 0.35rem; font-size: 0.78rem; color: #64748b; }
    .case-inline { display: flex; align-items: center; gap: 0.5rem; }
    .case-inline input[type="checkbox"] { width: 1rem; height: 1rem; }
</style>

<section class="case-shell section-card space-y-5 p-5" x-data="casePage()">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="section-title mb-1">Case List ({{ $caseRows->total() }})</h3>
            <p class="text-sm text-slate-600">Legacy case management screen with create and edit modals.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="btn-brand !px-4 !py-2 text-sm" @click="openCreateModal()">
                <i class="ri-add-line"></i>
                Submit new case
            </button>
            <button type="button" onclick="window.print()" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                <i class="ri-printer-line mr-2"></i>
                Print
            </button>
        </div>
    </div>

    <div class="case-toolbar rounded-xl p-4">
        <form method="GET" action="{{ route('admin.cases') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-4">
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search doctor, phone, case no., complainant" class="w-full rounded-lg border-slate-300 text-sm">
            </div>
            <div class="md:col-span-2">
                <select name="specialization_id" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">All Specializations</option>
                    @foreach($specializations as $specialization)
                        <option value="{{ $specialization->id }}" {{ (string) request('specialization_id') === (string) $specialization->id ? 'selected' : '' }}>{{ $specialization->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="plan" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">All Category</option>
                    @foreach($plans as $id => $name)
                        <option value="{{ $id }}" {{ (string) request('plan') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <select name="stage" class="w-full rounded-lg border-slate-300 text-sm">
                    <option value="">All Stages</option>
                    <option value="open" {{ ($stage ?? '') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="progress" {{ ($stage ?? '') === 'progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="closed" {{ ($stage ?? '') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn-brand !px-4 !py-2 text-sm">Search</button>
                <a href="{{ route('admin.cases') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Reset</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="case-table min-w-[1700px] w-full">
            <thead>
                <tr>
                    <th style="width: 56px;">SL No.</th>
                    <th>Doctor name</th>
                    <th>Phone</th>
                    <th>Case number</th>
                    <th>Category</th>
                    <th>Stage</th>
                    <th>Court</th>
                    <th>Case details</th>
                    <th>Payment</th>
                    <th>Next date</th>
                    <th>Created date</th>
                    <th>Created by</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($caseRows as $caseRecord)
                    <tr>
                        <td class="font-semibold">{{ $caseRows->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="font-semibold text-slate-800">{{ $caseRecord->doctor_name ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-500">{{ $caseRecord->doctor_mail ?? 'N/A' }}</div>
                        </td>
                        <td>{{ $caseRecord->doctor_phone ?? 'N/A' }}</td>
                        <td class="font-mono text-sm">{{ $caseRecord->case_number ?? 'N/A' }}</td>
                        <td>{{ $caseRecord->case_cat ?? 'N/A' }}</td>
                        <td>
                            <span class="case-pill {{ $caseRecord->direct_payment ? 'case-pill-direct' : 'case-pill-regular' }}">
                                {{ $caseRecord->stage ?: 'N/A' }}
                            </span>
                        </td>
                        <td>
                            <div>{{ $caseRecord->court ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-500">{{ $caseRecord->court_year ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="max-w-[260px] truncate">{{ $caseRecord->case_details ?? 'N/A' }}</div>
                            <div class="text-xs text-slate-500">{{ $caseRecord->complainant_name ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div>₹{{ number_format((float) ($caseRecord->direct_payment_amount ?? 0), 0) }}</div>
                            <div class="text-xs text-slate-500">{{ $caseRecord->direct_payment ? 'Direct payment' : 'Regular case' }}</div>
                        </td>
                        <td>{{ optional($caseRecord->next_date)->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>{{ optional($caseRecord->created_at)->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>{{ $caseRecord->created_by ?? 'System' }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <button type="button" class="case-action-btn case-action-view" title="View / Edit" @click="openEditModalById({{ $caseRecord->id }})"><i class="ri-eye-line"></i></button>
                                <button type="button" class="case-action-btn case-action-edit" title="Edit" @click="openEditModalById({{ $caseRecord->id }})"><i class="ri-pencil-line"></i></button>
                                <form method="POST" action="{{ route('admin.cases.destroy', $caseRecord->id) }}" onsubmit="return confirm('Delete this case?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="case-action-btn case-action-delete" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="py-10 text-center text-slate-500">No case records found. Use Submit new case to add the first record.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $caseRows->links() }}
    </div>

    <div class="case-modal-backdrop" x-show="createModalOpen || editModalOpen" x-transition.opacity x-cloak style="display: none;">
        <div class="case-modal" @click.away="closeModals()">
            <form :action="formAction" method="POST" class="contents">
                @csrf
                <template x-if="formMethod !== 'POST'">
                    <input type="hidden" name="_method" :value="formMethod">
                </template>
                <input type="hidden" name="doctor_name_add_text" :value="selectedDoctorText">
                <div class="case-modal-head">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900" x-text="modalTitle"></h3>
                        <p class="text-sm text-slate-600">Same create/edit workflow as the legacy case screen.</p>
                    </div>
                    <button type="button" class="rounded-full border border-slate-300 px-2 py-1 text-slate-500 hover:bg-slate-100" @click="closeModals()">&times;</button>
                </div>

                <div class="case-modal-body">
                    <div class="case-grid-2">
                        <div class="case-field">
                            <label for="doctor_name_add">Doctor name <span class="text-red-500">*</span></label>
                            <select id="doctor_name_add" name="doctor_name_add" class="select2" @change="setDoctorFromSelect($event)">
                                <option value="">--Select doctor--</option>
                                @foreach($doctorOptions as $doctor)
                                    <option value="{{ $doctor->id }}" data-phone="{{ $doctor->mobile1 ?? '' }}" data-email="{{ $doctor->doctor_email ?? '' }}" data-text="{{ $doctor->doctor_name ?? '' }}" data-membership="{{ $doctor->customer_id_no ?? '' }}">{{ $doctor->doctor_name ?? 'N/A' }}{{ $doctor->customer_id_no ? ' (' . $doctor->customer_id_no . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="case-field">
                            <label for="case_number">Case number <span class="text-red-500">*</span></label>
                            <input type="text" id="case_number" name="case_number" placeholder="CC/062/2026">
                        </div>
                    </div>

                    <div class="case-grid-2 mt-3">
                        <div class="case-field">
                            <label for="doctor_phone">Phone number <span class="text-red-500">*</span></label>
                            <input type="text" id="doctor_phone" name="doctor_phone">
                        </div>
                        <div class="case-field">
                            <label for="doctor_mail">Doctor mail <span class="text-red-500">*</span></label>
                            <input type="text" id="doctor_mail" name="doctor_mail">
                        </div>
                    </div>

                    <div class="case-grid-2 mt-3">
                        <div class="case-field">
                            <label for="court_year">Case Year</label>
                            <select id="court_year" name="court_year">
                                <option value="">--Select Case Year--</option>
                                @for($year = (int) date('Y'); $year >= 2000; $year--)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="case-field">
                            <label for="court">Court</label>
                            <select id="court" name="court">
                                <option value="">--Select court--</option>
                                <option value="district">District</option>
                                <option value="state">State</option>
                                <option value="national">National</option>
                            </select>
                        </div>
                    </div>

                    <div class="case-field mt-3">
                        <label for="court_address">Court Address</label>
                        <textarea id="court_address" name="court_address" placeholder="Court address"></textarea>
                    </div>

                    <div class="case-grid-2 mt-3">
                        <div class="case-field">
                            <label for="case_cat">Case category</label>
                            <select id="case_cat" name="case_cat">
                                <option value="">--Select category--</option>
                                <option value="Medical">Medical</option>
                                <option value="Consumer">Consumer</option>
                                <option value="Civil">Civil</option>
                                <option value="Criminal">Criminal</option>
                            </select>
                        </div>
                        <div class="case-field">
                            <label for="stage">Case stage</label>
                            <textarea id="stage" name="stage" placeholder="Case stage"></textarea>
                        </div>
                    </div>

                    <div class="case-field mt-3">
                        <label for="case_details">Case details</label>
                        <textarea id="case_details" name="case_details" placeholder="Case details"></textarea>
                    </div>

                    <div class="case-grid-2 mt-3">
                        <div class="case-field">
                            <label for="advocat_mobile">Advocate mobile no.</label>
                            <input type="text" id="advocat_mobile" name="advocat_mobile">
                        </div>
                        <div class="case-field">
                            <label for="advocat_mail">Advocate mail</label>
                            <input type="text" id="advocat_mail" name="advocat_mail">
                        </div>
                    </div>

                    <div class="case-grid-2 mt-3">
                        <div class="case-field">
                            <label for="appear_date">Appearance date</label>
                            <input type="date" id="appear_date" name="appear_date">
                        </div>
                        <div class="case-field">
                            <label for="next_date">Next date</label>
                            <input type="date" id="next_date" name="next_date">
                        </div>
                    </div>

                    <div class="case-grid-2 mt-3">
                        <div class="case-field">
                            <label for="filling_date">Filling Date</label>
                            <input type="date" id="filling_date" name="filling_date">
                        </div>
                        <div class="case-field">
                            <label for="complainant_name">Complainant Name</label>
                            <input type="text" id="complainant_name" name="complainant_name">
                        </div>
                    </div>

                    <div class="case-field mt-3">
                        <label for="mail_link">Mail link</label>
                        <input type="text" id="mail_link" name="mail_link">
                    </div>

                    <div class="case-field mt-3 case-inline">
                        <input type="checkbox" id="direct_payment" name="direct_payment" value="direct_payment" @change="toggleDirectPayment($event)">
                        <label for="direct_payment" class="mb-0">Direct payment receipt from doctor</label>
                    </div>

                    <div id="direct_payment_clicked" class="mt-4 hidden">
                        <div class="case-grid-2">
                            <div class="case-field">
                                <label for="money_reciept_no">Money Receipt no.</label>
                                <input type="text" id="money_reciept_no" name="money_reciept_no" placeholder="MRL-128">
                            </div>
                            <div class="case-field">
                                <label for="payment_cheque_no">Payment cheque no.</label>
                                <input type="text" id="payment_cheque_no" name="payment_cheque_no">
                            </div>
                        </div>

                        <div class="case-grid-2 mt-3">
                            <div class="case-field">
                                <label for="direct_payment_bank">Bank name</label>
                                <input type="text" id="direct_payment_bank" name="direct_payment_bank">
                            </div>
                            <div class="case-field">
                                <label for="bank_branch">Bank Branch</label>
                                <input type="text" id="bank_branch" name="bank_branch">
                            </div>
                        </div>

                        <div class="case-grid-2 mt-3">
                            <div class="case-field">
                                <label for="direct_payment_amount">Amount</label>
                                <input type="text" id="direct_payment_amount" name="direct_payment_amount">
                            </div>
                            <div class="case-field">
                                <label for="check_date">Check Date</label>
                                <input type="date" id="check_date" name="check_date">
                            </div>
                        </div>
                    </div>

                    <div class="case-field mt-3">
                        <label for="case_link">Case link</label>
                        <input type="text" id="case_link" name="case_link" placeholder="https://...">
                    </div>
                </div>

                <div class="case-modal-foot">
                    <button type="button" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100" @click="closeModals()">Close</button>
                    <button type="submit" class="btn-brand !px-4 !py-2 text-sm" x-text="submitLabel"></button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function casePage() {
    return {
        createModalOpen: false,
        editModalOpen: false,
        modalTitle: 'Submit new case',
        submitLabel: 'Update',
        formAction: '{{ route('admin.cases.store') }}',
        formMethod: 'POST',
        selectedDoctorText: '',
        openCreateModal() {
            this.resetForm();
            this.createModalOpen = true;
            this.editModalOpen = false;
            this.modalTitle = 'Submit new case';
            this.submitLabel = 'Submit';
            this.formAction = '{{ route('admin.cases.store') }}';
            this.formMethod = 'POST';
        },
        openEditModal(caseRecord) {
            this.resetForm();
            this.editModalOpen = true;
            this.createModalOpen = false;
            this.modalTitle = 'Edit case';
            this.submitLabel = 'Update';
            this.formAction = '{{ url('admin/cases') }}/' + caseRecord.id;
            this.formMethod = 'PUT';

            document.getElementById('doctor_name_add').value = caseRecord.enrollment_id ?? '';
            document.getElementById('doctor_phone').value = caseRecord.doctor_phone ?? '';
            document.getElementById('doctor_mail').value = caseRecord.doctor_mail ?? '';
            document.getElementById('case_number').value = caseRecord.case_number ?? '';
            document.getElementById('court_year').value = caseRecord.court_year ?? '';
            document.getElementById('court').value = caseRecord.court ?? '';
            document.getElementById('court_address').value = caseRecord.court_address ?? '';
            document.getElementById('case_cat').value = caseRecord.case_cat ?? '';
            document.getElementById('stage').value = caseRecord.stage ?? '';
            document.getElementById('case_details').value = caseRecord.case_details ?? '';
            document.getElementById('advocat_mobile').value = caseRecord.advocat_mobile ?? '';
            document.getElementById('advocat_mail').value = caseRecord.advocat_mail ?? '';
            document.getElementById('appear_date').value = caseRecord.appear_date ?? '';
            document.getElementById('next_date').value = caseRecord.next_date ?? '';
            document.getElementById('filling_date').value = caseRecord.filling_date ?? '';
            document.getElementById('complainant_name').value = caseRecord.complainant_name ?? '';
            document.getElementById('mail_link').value = caseRecord.mail_link ?? '';
            document.getElementById('money_reciept_no').value = caseRecord.money_reciept_no ?? '';
            document.getElementById('payment_cheque_no').value = caseRecord.payment_cheque_no ?? '';
            document.getElementById('direct_payment_bank').value = caseRecord.direct_payment_bank ?? '';
            document.getElementById('bank_branch').value = caseRecord.bank_branch ?? '';
            document.getElementById('direct_payment_amount').value = caseRecord.direct_payment_amount ?? '';
            document.getElementById('check_date').value = caseRecord.check_date ?? '';
            document.getElementById('case_link').value = caseRecord.case_link ?? '';

            const directPayment = document.getElementById('direct_payment');
            directPayment.checked = Boolean(caseRecord.direct_payment);
            this.toggleDirectPayment({ target: directPayment });
        },
        async openEditModalById(caseId) {
            try {
                const response = await fetch(`{{ url('admin/cases') }}/${caseId}/json`, {
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                const payload = await response.json();
                if (!response.ok || !payload.success || !payload.case) {
                    alert('Unable to load case details. Please try again.');
                    return;
                }
                this.openEditModal(payload.case);
            } catch (error) {
                alert('Unable to load case details. Please check your connection and retry.');
            }
        },
        closeModals() {
            this.createModalOpen = false;
            this.editModalOpen = false;
        },
        resetForm() {
            const fields = [
                'doctor_name_add','doctor_phone','doctor_mail','case_number','court_year','court','court_address','case_cat','stage','case_details',
                'advocat_mobile','advocat_mail','appear_date','next_date','filling_date','complainant_name','mail_link','money_reciept_no',
                'payment_cheque_no','direct_payment_bank','bank_branch','direct_payment_amount','check_date','case_link'
            ];
            fields.forEach(function (fieldId) {
                const element = document.getElementById(fieldId);
                if (element) {
                    element.value = '';
                }
            });
            const directPayment = document.getElementById('direct_payment');
            if (directPayment) {
                directPayment.checked = false;
            }
            this.selectedDoctorText = '';
            this.toggleDirectPayment({ target: directPayment ?? { checked: false } });
        },
        setDoctorFromSelect(event) {
            const option = event.target.selectedOptions[0];
            if (!option) {
                return;
            }
            this.selectedDoctorText = option.dataset.text || '';
            document.getElementById('doctor_phone').value = option.dataset.phone || '';
            document.getElementById('doctor_mail').value = option.dataset.email || '';
        },
        toggleDirectPayment(event) {
            const panel = document.getElementById('direct_payment_clicked');
            if (!panel) {
                return;
            }
            panel.classList.toggle('hidden', !event.target.checked);
        }
    };
}
</script>
@endpush
