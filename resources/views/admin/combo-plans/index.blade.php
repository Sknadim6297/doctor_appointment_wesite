@extends('admin.layouts.app')

@section('title', 'Combo Plan')
@section('page-title', 'Combo Plan Management')

@section('content')
<section class="section-card" x-data="{
    addModalOpen: false,
    editModalOpen: false,
    editId: null,
    editCoverage: '',
    editYearly: '',
    editSpecializations: [],
    openEdit(id, coverage, yearly, specializations) {
        this.editId = id;
        this.editCoverage = coverage;
        this.editYearly = yearly;
        this.editSpecializations = specializations;
        this.editModalOpen = true;
    }
}">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Combo Plan ({{ $totalPlans ?? 0 }})</h3>
        <button type="button" @click="addModalOpen = true" class="btn-brand !px-4 !py-2 text-sm">
            <i class="ri-pencil-line"></i>
            <span>Add Plan</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table" style="table-layout: auto; min-width: 100%;">
            <thead>
                <tr>
                    <th style="width: 60px;">SL No.</th>
                    <th style="min-width: 300px; max-width: 400px;">Specialization</th>
                    <th style="width: 140px;">Legal Bond Amount</th>
                    <th style="width: 110px;">Monthly</th>
                    <th style="width: 110px;">Yearly</th>
                    <th style="width: 130px;">2 Year (5% Discount)</th>
                    <th style="width: 130px;">3 Year (5% Discount)</th>
                    <th style="width: 140px;">4 Year (10% Discount)</th>
                    <th style="width: 140px;">5 Year (10% Discount)</th>
                    <th style="width: 180px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    @php
                        $yearly = (float) $plan->yearly_amount;
                        $monthly = $yearly / 10;
                        $twoYear = $yearly * 2 * 0.95;
                        $threeYear = $yearly * 3 * 0.95;
                        $fourYear = $yearly * 4 * 0.90;
                        $fiveYear = $yearly * 5 * 0.90;
                        $coverage = rtrim(rtrim(number_format((float) $plan->coverage_lakh, 2, '.', ''), '0'), '.');
                        $fmt = fn($v) => 'Rs ' . number_format($v, 0) . '/-';
                        $specs = is_array($plan->specializations) ? implode(', ', $plan->specializations) : $plan->specializations;
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td style="max-width: 400px; word-wrap: break-word; white-space: normal;">{{ $specs }}</td>
                        <td>Rs {{ $coverage }}Lakh</td>
                        <td>{{ $fmt($monthly) }}</td>
                        <td>{{ $fmt($yearly) }}</td>
                        <td>{{ $fmt($twoYear) }}</td>
                        <td>{{ $fmt($threeYear) }}</td>
                        <td>{{ $fmt($fourYear) }}</td>
                        <td>{{ $fmt($fiveYear) }}</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2" style="min-width: 160px;">
                                <button type="button"
                                        @click="openEdit({{ $plan->id }}, '{{ $coverage }}', '{{ number_format((float)$plan->yearly_amount, 2, '.', '') }}', {{ json_encode($plan->specializations) }})"
                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </button>
                                <form method="POST" action="{{ route('admin.combo-plans.destroy', $plan) }}" onsubmit="return confirm('Delete this plan?');">
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
                        <td colspan="10" class="text-center text-slate-500">No combo plans found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Add Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="addModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="addModalOpen = false">
            <form action="{{ route('admin.combo-plans.store') }}" method="POST" class="form-horizontal" id="add_plan_form" onsubmit="return addplan()">
                @csrf
                <div class="modal-header">
                    <h3>Add Plan</h3>
                    <button type="button" class="close" @click="addModalOpen = false">x</button>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <div class="control-group" id="spec_control">
                            <label class="control-label" for="specializations">Specialization</label>
                            <div class="controls">
                                <select id="specializations" class="form-control" multiple name="specializations[]" size="8" style="height: auto;">
                                    @foreach($specializations as $spec)
                                        <option value="{{ $spec->name }}">{{ $spec->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-slate-600">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                                <br>
                                <span class="help-inline" id="spec_message" style="display:none; color:red"></span>
                            </div>
                        </div>
                        <div class="control-group" id="price_control">
                            <label class="control-label" for="price">Coverage (Per Lakh Per Year)</label>
                            <div class="controls">
                                <input type="text" id="price" class="form-control" name="coverage" value="{{ old('coverage') }}">&nbsp;&nbsp;Lakh
                                <br>
                                <span class="help-inline" id="price_message" style="display:none; color:red"></span>
                            </div>
                        </div>
                        <div class="control-group" id="resume_download_control">
                            <label class="control-label" for="plan_period">Yearly Amount</label>
                            <div class="controls">
                                <input type="text" id="plan_period" class="form-control" name="yearly_amount" value="{{ old('yearly_amount') }}">
                                <br>
                                <span class="help-inline" id="sub_message" style="display:none; color:red"></span>
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

    <!-- Edit Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="editModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="editModalOpen = false">
            <form :action="`{{ url('/admin/combo-plans') }}/${editId}`" method="POST" class="form-horizontal" id="edit_plan_form" onsubmit="return editplan()">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h3>Edit Plan</h3>
                    <button type="button" class="close" @click="editModalOpen = false">x</button>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <div class="control-group">
                            <label class="control-label" for="edit_specializations">Specialization</label>
                            <div class="controls">
                                <select id="edit_specializations" class="form-control" multiple name="specializations[]" size="8" style="height: auto;">
                                    @foreach($specializations as $spec)
                                        <option value="{{ $spec->name }}">{{ $spec->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-slate-600">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="edit_price">Coverage (Per Lakh Per Year)</label>
                            <div class="controls">
                                <input type="text" id="edit_price" class="form-control" name="coverage" x-model="editCoverage">&nbsp;&nbsp;Lakh
                            </div>
                        </div>
                        <div class="control-group">
                            <label class="control-label" for="edit_plan_period">Yearly Amount</label>
                            <div class="controls">
                                <input type="text" id="edit_plan_period" class="form-control" name="yearly_amount" x-model="editYearly">
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
        const specMsg = document.getElementById('spec_message');
        const selectedSpecs = Array.from(specSelect.selectedOptions);

        const priceInput = document.getElementById('price');
        const priceMsg = document.getElementById('price_message');
        const coverage = parseFloat((priceInput?.value || '').trim());

        const yearlyInput = document.getElementById('plan_period');
        const subMsg = document.getElementById('sub_message');
        const yearly = parseFloat((yearlyInput?.value || '').trim());

        let hasError = false;

        // Validate specializations
        if (selectedSpecs.length === 0) {
            specMsg.textContent = 'Please select at least one specialization.';
            specMsg.style.display = 'inline';
            hasError = true;
        } else {
            specMsg.style.display = 'none';
        }

        // Validate coverage
        if (isNaN(coverage) || coverage <= 0) {
            priceMsg.textContent = 'Coverage must be greater than 0.';
            priceMsg.style.display = 'inline';
            hasError = true;
        } else {
            priceMsg.style.display = 'none';
        }

        // Validate yearly amount
        if (isNaN(yearly) || yearly <= 0) {
            subMsg.textContent = 'Yearly Amount must be greater than 0.';
            subMsg.style.display = 'inline';
            hasError = true;
        } else {
            subMsg.style.display = 'none';
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
        const coverage = parseFloat((document.getElementById('edit_price')?.value || '').trim());
        const yearly = parseFloat((document.getElementById('edit_plan_period')?.value || '').trim());

        if (selectedSpecs.length === 0 || isNaN(coverage) || coverage <= 0 || isNaN(yearly) || yearly <= 0) {
            showToast('Please fill in all required fields correctly.', 'error');
            return false;
        }
        return true;
    }

    // Pre-select specializations when editing
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
