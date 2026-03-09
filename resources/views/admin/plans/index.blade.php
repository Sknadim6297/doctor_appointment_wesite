@extends('admin.layouts.app')

@section('title', 'Normal Plan')
@section('page-title', 'Normal Plan Management')

@section('content')
<section class="section-card" x-data="{
    addModalOpen: false,
    editModalOpen: false,
    editId: null,
    editCoverage: '',
    editYearly: '',
    openEdit(id, coverage, yearly) {
        this.editId = id;
        this.editCoverage = coverage;
        this.editYearly = yearly;
        this.editModalOpen = true;
    }
}">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Normal plan({{ $totalPlans ?? 0 }})</h3>
        <button type="button" @click="addModalOpen = true" class="btn-brand !px-4 !py-2 text-sm">
            <i class="ri-pencil-line"></i>
            <span>Add Plan</span>
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Legal Bond Amount</th>
                    <th>Monthly</th>
                    <th>Yearly</th>
                    <th>2 Year (5% Discount)</th>
                    <th>3 Year</th>
                    <th>4 Year (10% Discount)</th>
                    <th>5 Year</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    @php
                        $yearly = (float) $plan->yearly_amount;
                        $monthly = $yearly / 10;
                        $twoYear = $yearly * 2 * 0.95;
                        $threeYear = $yearly * 3;
                        $fourYear = $yearly * 4 * 0.90;
                        $fiveYear = $yearly * 5;
                        $coverage = rtrim(rtrim(number_format((float) $plan->coverage_lakh, 2, '.', ''), '0'), '.');
                        $fmt = fn($v) => 'Rs ' . number_format($v, 0) . '/-';
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>Rs {{ $coverage }}Lakh</td>
                        <td>{{ $fmt($monthly) }}</td>
                        <td>{{ $fmt($yearly) }}</td>
                        <td>{{ $fmt($twoYear) }}</td>
                        <td>{{ $fmt($threeYear) }}</td>
                        <td>{{ $fmt($fourYear) }}</td>
                        <td>{{ $fmt($fiveYear) }}</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button"
                                        @click="openEdit({{ $plan->id }}, '{{ $coverage }}', '{{ number_format((float)$plan->yearly_amount, 2, '.', '') }}')"
                                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </button>
                                <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" onsubmit="return confirm('Delete this plan?');">
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
                        <td colspan="9" class="text-center text-slate-500">No normal plans found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="addModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="addModalOpen = false">
            <form action="{{ route('admin.plans.store') }}" method="POST" class="form-horizontal" id="add_plan_form" onsubmit="return addplan()">
                @csrf
                <div class="modal-header">
                    <h3>Add Subscription</h3>
                    <button type="button" class="close" @click="addModalOpen = false">x</button>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <div class="control-group" id="price_control">
                            <label class="control-label" for="price">Coverage</label>
                            <div class="controls">
                                <input type="text" id="price" class="form-control" name="coverage" onblur="price_onblur()" value="{{ old('coverage') }}">&nbsp;&nbsp;Lakh
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

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 px-4" x-show="editModalOpen" x-transition.opacity x-cloak>
        <div class="modal-content w-full max-w-2xl" @click.away="editModalOpen = false">
            <form :action="`{{ url('/admin/plans') }}/${editId}`" method="POST" class="form-horizontal" id="edit_plan_form" onsubmit="return editplan()">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h3>Edit Subscription</h3>
                    <button type="button" class="close" @click="editModalOpen = false">x</button>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <div class="control-group">
                            <label class="control-label" for="edit_price">Coverage</label>
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

    function price_onblur() {
        const input = document.getElementById('price');
        const msg = document.getElementById('price_message');
        const value = parseFloat((input?.value || '').trim());
        if (isNaN(value) || value <= 0) {
            msg.textContent = 'Coverage must be greater than 0.';
            msg.style.display = 'inline';
            return false;
        }
        msg.style.display = 'none';
        return true;
    }

    function addplan() {
        const priceInput = document.getElementById('price');
        const priceMsg = document.getElementById('price_message');
        const coverage = parseFloat((priceInput?.value || '').trim());

        const yearlyInput = document.getElementById('plan_period');
        const subMsg = document.getElementById('sub_message');
        const yearly = parseFloat((yearlyInput?.value || '').trim());

        let hasError = false;

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
            showToast('Please enter valid coverage and yearly amount.', 'error');
            return false;
        }

        return true;
    }

    function editplan() {
        const coverage = parseFloat((document.getElementById('edit_price')?.value || '').trim());
        const yearly = parseFloat((document.getElementById('edit_plan_period')?.value || '').trim());

        if (isNaN(coverage) || coverage <= 0 || isNaN(yearly) || yearly <= 0) {
            showToast('Coverage and yearly amount must be greater than 0.', 'error');
            return false;
        }
        return true;
    }

    @if(session('success'))
        showToast(@json(session('success')), 'success');
    @endif
</script>
@endsection
