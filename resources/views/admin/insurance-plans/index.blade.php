@extends('admin.layouts.app')

@section('title', 'Insurence')
@section('page-title', 'Insurence Plan Management')

@section('content')
<section class="section-card" x-data="{
    addModalOpen: false,
    editModalOpen: false,
    editId: null,
    editAmount: '',
    editServiceTax: '',
    editSpecializations: [],
    openEdit(id, amount, serviceTax, specializations) {
        this.editId = id;
        this.editAmount = amount;
        this.editServiceTax = serviceTax;
        this.editSpecializations = specializations;
        this.editModalOpen = true;
    }
}">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Insurence ({{ $totalPlans ?? 0 }})</h3>
        <button type="button" @click="addModalOpen = true" class="btn-brand !px-4 !py-2 text-sm">
            <i class="ri-pencil-line"></i>
            <span>Add Insurence</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table" style="table-layout: auto; min-width: 100%;">
            <thead>
                <tr>
                    <th style="width: 60px;">SL No.</th>
                    <th style="min-width: 360px; max-width: 520px;">Specialization</th>
                    <th style="width: 220px;">Amount (Per Lakh Per Year)</th>
                    <th style="width: 120px;">Service Tax</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    @php
                        $amount = (float) $plan->amount_per_lakh;
                        $serviceTax = (float) $plan->service_tax_percent;
                        $specs = is_array($plan->specializations) ? implode(', ', $plan->specializations) : $plan->specializations;
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td style="max-width: 520px; word-wrap: break-word; white-space: normal;">{{ $specs }}</td>
                        <td>Rs {{ number_format($amount, 0) }}/-</td>
                        <td>{{ rtrim(rtrim(number_format($serviceTax, 2, '.', ''), '0'), '.') }}%</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2" style="min-width: 160px;">
                                <button type="button"
                                        @click="openEdit({{ $plan->id }}, '{{ number_format($amount, 2, '.', '') }}', '{{ number_format($serviceTax, 2, '.', '') }}', {{ json_encode($plan->specializations) }})"
                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </button>
                                <form method="POST" action="{{ route('admin.insurance-plans.destroy', $plan) }}" onsubmit="return confirm('Delete this insurance plan?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200">
                                        <i class="ri-delete-bin-line"></i>
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500">No insurence plans found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="addModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="addModalOpen = false">
            <form action="{{ route('admin.insurance-plans.store') }}" method="POST" class="form-horizontal" id="add_insurance_form" onsubmit="return addplan()">
                @csrf
                <div class="modal-header">
                    <h3>New Insurence</h3>
                    <button type="button" class="close" @click="addModalOpen = false">x</button>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <div class="control-group" id="spec_control">
                            <label class="control-label" for="specializations">Specialization</label>
                            <span id="choose_spec_message" style="color:red;"></span>
                            <div class="controls">
                                <select id="specializations" class="form-control" multiple name="specializations[]" size="8" style="height:auto;">
                                    @foreach($specializations as $spec)
                                        <option value="{{ $spec->name }}">{{ $spec->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-slate-600">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                            </div>
                        </div>

                        <div class="control-group" id="price_control">
                            <label class="control-label" for="amount">Amount (Per Lakh/Year)</label>
                            <div class="controls">
                                <input type="text" id="amount" class="form-control" name="amount" value="{{ old('amount') }}">&nbsp;&nbsp;Rs.
                                <br>
                                <span class="help-inline" id="price_message" style="display:none; color:red"></span>
                            </div>
                        </div>

                        <div class="control-group" id="resume_download_control">
                            <label class="control-label" for="service_tax">Service Tax</label>
                            <div class="controls">
                                <input type="text" id="service_tax" class="form-control" name="service_tax" value="{{ old('service_tax', '18') }}">&nbsp;&nbsp;%
                                <br>
                                <span class="help-inline" id="tax_message" style="display:none; color:red"></span>
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" @click="addModalOpen = false">Close</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="editModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="editModalOpen = false">
            <form :action="`{{ url('/admin/insurance-plans') }}/${editId}`" method="POST" class="form-horizontal" id="edit_insurance_form" onsubmit="return editplan()">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h3>Edit Insurence</h3>
                    <button type="button" class="close" @click="editModalOpen = false">x</button>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <div class="control-group">
                            <label class="control-label" for="edit_specializations">Specialization</label>
                            <div class="controls">
                                <select id="edit_specializations" class="form-control" multiple name="specializations[]" size="8" style="height:auto;">
                                    @foreach($specializations as $spec)
                                        <option value="{{ $spec->name }}">{{ $spec->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-slate-600">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="edit_amount">Amount (Per Lakh/Year)</label>
                            <div class="controls">
                                <input type="text" id="edit_amount" class="form-control" name="amount" x-model="editAmount">&nbsp;&nbsp;Rs.
                            </div>
                        </div>

                        <div class="control-group">
                            <label class="control-label" for="edit_service_tax">Service Tax</label>
                            <div class="controls">
                                <input type="text" id="edit_service_tax" class="form-control" name="service_tax" x-model="editServiceTax">&nbsp;&nbsp;%
                            </div>
                        </div>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" @click="editModalOpen = false">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toastContainer" class="toast-container"></div>
</section>

<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast-item toast-${type}`;
        toast.innerHTML = `<span>${message}</span><button type="button" class="toast-close" aria-label="Close">x</button>`;
        toast.querySelector('.toast-close').addEventListener('click', function () { toast.remove(); });
        container.appendChild(toast);
        setTimeout(function () { toast.classList.add('show'); }, 10);
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 220);
        }, 3200);
    }

    function addplan() {
        const specSelect = document.getElementById('specializations');
        const specMsg = document.getElementById('choose_spec_message');
        const selectedSpecs = Array.from(specSelect.selectedOptions);

        const amountInput = document.getElementById('amount');
        const amountMsg = document.getElementById('price_message');
        const amount = parseFloat((amountInput?.value || '').trim());

        const taxInput = document.getElementById('service_tax');
        const taxMsg = document.getElementById('tax_message');
        const serviceTax = parseFloat((taxInput?.value || '').trim());

        let hasError = false;

        if (selectedSpecs.length === 0) {
            specMsg.textContent = 'Please select at least one specialization.';
            hasError = true;
        } else {
            specMsg.textContent = '';
        }

        if (isNaN(amount) || amount <= 0) {
            amountMsg.textContent = 'Amount must be greater than 0.';
            amountMsg.style.display = 'inline';
            hasError = true;
        } else {
            amountMsg.style.display = 'none';
        }

        if (isNaN(serviceTax) || serviceTax < 0 || serviceTax > 100) {
            taxMsg.textContent = 'Service tax must be between 0 and 100.';
            taxMsg.style.display = 'inline';
            hasError = true;
        } else {
            taxMsg.style.display = 'none';
        }

        if (hasError) {
            showToast('Please fill in all required fields correctly.', 'error');
            return false;
        }

        return true;
    }

    function editplan() {
        const specSelect = document.getElementById('edit_specializations');
        const selectedSpecs = Array.from(specSelect.selectedOptions);
        const amount = parseFloat((document.getElementById('edit_amount')?.value || '').trim());
        const serviceTax = parseFloat((document.getElementById('edit_service_tax')?.value || '').trim());

        if (selectedSpecs.length === 0 || isNaN(amount) || amount <= 0 || isNaN(serviceTax) || serviceTax < 0 || serviceTax > 100) {
            showToast('Please fill in all required fields correctly.', 'error');
            return false;
        }
        return true;
    }

    document.addEventListener('alpine:initialized', () => {
        Alpine.effect(() => {
            const data = Alpine.$data(document.querySelector('[x-data]'));
            if (data.editModalOpen && data.editSpecializations && data.editSpecializations.length > 0) {
                setTimeout(() => {
                    const select = document.getElementById('edit_specializations');
                    if (select) {
                        Array.from(select.options).forEach(option => {
                            option.selected = data.editSpecializations.includes(option.value);
                        });
                    }
                }, 100);
            }
        });
    });

    @if(session('success'))
        showToast(@json(session('success')), 'success');
    @endif
</script>
@endsection
