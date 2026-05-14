@extends('admin.layouts.app')

@section('title', 'Renewal Enrollment')
@section('page-title', 'Doctor Renewal Management')

@section('content')
<div class="content-wrapper">
    <!-- Content Header -->
    <section class="content-header py-4 px-6 bg-slate-50 border-b border-slate-200">
        <h1 class="text-2xl font-bold text-slate-800">
            <span class="text-slate-500">Renew Enrollment of</span>
            <span class="text-slate-900 font-extrabold">{{ $doctor->doctor_name }}</span>
        </h1>
    </section>

    <!-- Main Content -->
    <section class="content py-6 px-6">
        <div class="max-w-4xl mx-auto">
            <!-- Form Card -->
            <div class="bg-white rounded-lg shadow-md border border-slate-200">
                <div class="p-6 border-b border-slate-200">
                    <div id="validation" class="text-red-600 text-sm"></div>
                </div>

                <!-- Sub-admin / Office Use Info -->
                <div class="p-4 border-b border-slate-200 bg-slate-50">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-slate-600">Sub-admin</p>
                            <p class="font-semibold text-slate-800">{{ $subAdminName ?? auth()->user()->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-slate-600">Office Use Agent</p>
                            <p class="font-semibold text-slate-800">{{ $officeUseAgentName ?? 'Super Admin' }} {{ $officeUseAgentPhone ? ' / ' . $officeUseAgentPhone : '' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Form Start -->
                <form class="p-6 space-y-6" action="{{ route('admin.doctors.renewal-store', $doctor->id) }}" method="POST" id="renew_form_validation" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="url_slug" value="renewal">
                    <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">

                    <!-- Basic Information Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Basic Information</h3>

                        <!-- Policy No (Blank) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="policy_no" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Policy No. <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="policy_no" id="policy_no" value="" placeholder="Leave blank for new policy">
                                <span id="error_policy_no" class="text-red-600 text-xs"></span>
                            </div>

                            <!-- Customer ID No (Readonly) -->
                            <div>
                                <label for="customer_id_no" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Customer ID No. <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md bg-slate-100 cursor-not-allowed" 
                                    name="customer_id_no" id="customer_id_no" value="{{ $doctor->customer_id_no }}" readonly>
                                <span id="error_customer_id_no" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <!-- Money Receipt No -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="money_rc_no" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Money Receipt No. <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="money_rc_no" id="money_rc_no" value="{{ $doctor->money_rc_no ?? '' }}">
                                <span id="error_money_rc_no" class="text-red-600 text-xs"></span>
                            </div>

                            <!-- Broker / Agent Name -->
                            <div>
                                <label for="agent_name" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Broker / Agent Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="agent_name" id="agent_name" value="{{ old('agent_name', $doctor->agent_name ?? $officeUseAgentName ?? '') }}">
                                <span id="error_agent_name" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <!-- Agent Phone No -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="agent_phone_no" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Phone No. <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="agent_phone_no" id="agent_phone_no" value="{{ old('agent_phone_no', $doctor->agent_phone_no ?? $officeUseAgentPhone ?? '') }}">
                                <span id="error_agent_phone_no" class="text-red-600 text-xs"></span>
                            </div>

                            <!-- Doctor Name (Readonly) -->
                            <div>
                                <label for="doctor_name" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Name of the Proposer <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md bg-slate-100 cursor-not-allowed" 
                                    name="doctor_name" id="doctor_name" value="{{ $doctor->doctor_name }}" readonly>
                                <span id="error_doctor_name" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <!-- Renewal & Policy Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="renewal_date_rn" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Collection Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="renewal_date_rn" id="renewal_date_rn" value="{{ $doctor->renewal_date ? $doctor->renewal_date->format('Y-m-d') : now()->format('Y-m-d') }}">
                                <span id="error_renewal_date_rn" class="text-red-600 text-xs"></span>
                            </div>

                            <div>
                                <label for="policy_date" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Policy Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="policy_date" id="policy_date" value="{{ $doctor->policy_date ? $doctor->policy_date->format('Y-m-d') : now()->format('Y-m-d') }}">
                                <span id="error_policy_date" class="text-red-600 text-xs"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Contact Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="mobile1" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Mobile 1 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="mobile1" id="mobile1" value="{{ $doctor->mobile1 ?? '' }}">
                                <span id="error_mobile1" class="text-red-600 text-xs"></span>
                            </div>

                            <div>
                                <label for="mobile2" class="block text-sm font-semibold text-slate-700 mb-2">Mobile 2/Phone</label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="mobile2" id="mobile2" value="{{ $doctor->mobile2 ?? '' }}">
                                <span id="error_mobile2" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <div>
                            <label for="doctor_email" class="block text-sm font-semibold text-slate-700 mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                name="doctor_email" id="doctor_email" value="{{ $doctor->doctor_email ?? '' }}">
                            <span id="error_doctor_email" class="text-red-600 text-xs"></span>
                        </div>
                    </div>

                    <!-- Professional Information Section -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-bold text-slate-800 border-b pb-2">Professional Information</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="qualification" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Qualification <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="qualification" id="qualification" value="{{ is_array($doctor->qualification) ? implode(', ', array_map(fn($p) => is_array($p) ? ($p['name'] ?? '') : (string)$p, $doctor->qualification)) : ($doctor->qualification ?? '') }}">
                                <span id="error_qualification" class="text-red-600 text-xs"></span>
                            </div>

                            <div>
                                <label for="dob" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Date of Birth <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 datepicker" 
                                    name="dob" id="dob" value="{{ optional($doctor->dob)->format('d/m/Y') ?? '' }}" autocomplete="off">
                                <span id="error_dob" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="medical_registration_no" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Medical Registration No. <span class="text-red-500">*</span>
                                </label>
                                <input type="text" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                    name="medical_registration_no" id="medical_registration_no" value="{{ $doctor->medical_registration_no ?? '' }}">
                                <span id="error_medical_registration_no" class="text-red-600 text-xs"></span>
                            </div>

                            <div>
                                <label for="year_of_reg" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Year of Registration <span class="text-red-500">*</span>
                                </label>
                                <select name="year_of_reg" id="year_of_reg" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select year of registration</option>
                                    @for($year = date('Y'); $year >= 1950; $year--)
                                        <option value="{{ $year }}" {{ $doctor->year_of_reg == $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endfor
                                </select>
                                <span id="error_year_of_reg" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <div>
                            <label for="clinic_address" class="block text-sm font-semibold text-slate-700 mb-2">
                                Clinic Address <span class="text-red-500">*</span>
                            </label>
                            <textarea name="clinic_address" id="clinic_address" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                placeholder="Enter clinic address">{{ $doctor->clinic_address ?? '' }}</textarea>
                            <span id="error_clinic_address" class="text-red-600 text-xs"></span>
                        </div>
                    </div>

                    <!-- Payment Details Section -->
                    <div class="space-y-4 bg-slate-50 p-4 rounded-lg">
                        <h3 class="text-lg font-bold text-slate-800">Payment Details</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="speciliazition" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Are You a <span class="text-red-500">*</span>
                                </label>
                                <select name="speciliazition" id="speciliazition" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="onchange_spec();">
                                    <option value="0">---Select specialization---</option>
                                    @foreach($specializations as $specialization)
                                        <option value="{{ $specialization->id }}" {{ $doctor->specialization_id == $specialization->id ? 'selected' : '' }}>
                                            {{ $specialization->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span id="error_specialization" class="text-red-600 text-xs"></span>
                            </div>

                            <div>
                                <label for="payment_mode" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Payment Mode <span class="text-red-500">*</span>
                                </label>
                                <select name="payment_mode" id="payment_mode" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="onchange_payment_mode();">
                                    <option value="0">---Select Payment Mode---</option>
                                    <option value="Monthly EMI">Monthly EMI</option>
                                    <option value="One Year" selected>One Year</option>
                                    <option value="Two Year">Two Year</option>
                                    <option value="Three Year">Three Year</option>
                                    <option value="Four Year">Four Year</option>
                                    <option value="Five Year">Five Year</option>
                                </select>
                                <span id="error_payment_mode" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="plan" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Plan <span class="text-red-500">*</span>
                                </label>
                                <input type="hidden" name="plan_name" id="plan_name" value="">
                                <select name="plan" id="plan" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="change_plan_id_to_name(); return chng_plan(this.value);">
                                    <option value="0">---Select plan---</option>
                                    <option value="1" {{ $doctor->plan == 1 ? 'selected' : '' }}>Normal</option>
                                    <option value="2" {{ $doctor->plan == 2 ? 'selected' : '' }}>High Risk</option>
                                    <option value="3" {{ $doctor->plan == 3 ? 'selected' : '' }}>Combo</option>
                                </select>
                                <span id="error_plan" class="text-red-600 text-xs"></span>
                            </div>

                            <div>
                                <label for="coverage" id="coverage_text" class="block text-sm font-semibold text-slate-700 mb-2">
                                    Insurance Coverage <span class="text-red-500">*</span>
                                </label>
                                <select name="coverage" id="coverage" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">--Select Coverage--</option>
                                    <option value="5" {{ $doctor->coverage == 5 ? 'selected' : '' }}>5 Lakh</option>
                                    <option value="10" {{ $doctor->coverage == 10 ? 'selected' : '' }}>10 Lakh</option>
                                    <option value="20" {{ $doctor->coverage == 20 ? 'selected' : '' }}>20 Lakh</option>
                                    <option value="30" {{ $doctor->coverage == 30 ? 'selected' : '' }}>30 Lakh</option>
                                    <option value="40" {{ $doctor->coverage == 40 ? 'selected' : '' }}>40 Lakh</option>
                                    <option value="50" {{ $doctor->coverage == 50 ? 'selected' : '' }}>50 Lakh</option>
                                </select>
                                <span id="error_coverage" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div id="pre_amount_label" style="display: none;">
                                <label for="pre_amount" class="block text-sm font-semibold text-slate-700 mb-2">Insurance Amount</label>
                                <input type="text" name="service_amount" id="pre_amount" class="w-full px-3 py-2 border border-slate-300 rounded-md bg-slate-100" readonly>
                            </div>

                            <div>
                                <label for="payment_amount" class="block text-sm font-semibold text-slate-700 mb-2">Medeforum Amount</label>
                                <input type="text" name="payment_amount" id="payment_amount" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="{{ $doctor->payment_amount ?? '' }}">
                                <span id="error_payment_amount" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <div id="total_amount_label" style="display: none;">
                            <label for="total_amount" class="block text-sm font-semibold text-slate-700 mb-2">Total Amount</label>
                            <input type="text" name="total_amount" id="total_amount" class="w-full px-3 py-2 border border-slate-300 rounded-md bg-slate-100" readonly>
                        </div>
                    </div>

                    <!-- Payment Method Section -->
                    <div class="space-y-4 bg-slate-50 p-4 rounded-lg">
                        <h3 class="text-lg font-bold text-slate-800">Payment Method</h3>

                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="1" id="cheque1" class="mr-3" onclick="return show();">
                                <span class="text-slate-700 font-medium">Cheque</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="2" id="cash" class="mr-3" checked onclick="return hide();">
                                <span class="text-slate-700 font-medium">Cash</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="payment_method" value="3" id="upi" class="mr-3" onclick="return showUpi();">
                                <span class="text-slate-700 font-medium">UPI</span>
                            </label>
                            <span id="error_payment_method" class="text-red-600 text-xs"></span>
                        </div>

                        <!-- Cheque Details -->
                        <div id="cheque_check" style="display: none;" class="space-y-4 border-t pt-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="payment_cheque" class="block text-sm font-semibold text-slate-700 mb-2">
                                        Cheque No. <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="payment_cheque" id="payment_cheque" class="w-full px-3 py-2 border border-slate-300 rounded-md">
                                    <span id="error_cheque_no" class="text-red-600 text-xs"></span>
                                </div>

                                <div>
                                    <label for="payment_bank_name" class="block text-sm font-semibold text-slate-700 mb-2">
                                        Bank <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="payment_bank_name" id="payment_bank_name" class="w-full px-3 py-2 border border-slate-300 rounded-md">
                                    <span id="error_bank" class="text-red-600 text-xs"></span>
                                </div>

                                <div>
                                    <label for="payment_branch_name" class="block text-sm font-semibold text-slate-700 mb-2">
                                        Branch <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="payment_branch_name" id="payment_branch_name" class="w-full px-3 py-2 border border-slate-300 rounded-md">
                                    <span id="error_branch" class="text-red-600 text-xs"></span>
                                </div>
                            </div>
                        </div>

                        <!-- UPI Details -->
                        <div id="upi_check" style="display: none;" class="space-y-4 border-t pt-4">
                            <div>
                                <label for="upi_transaction_id" class="block text-sm font-semibold text-slate-700 mb-2">
                                    UPI Transaction ID <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="upi_transaction_id" id="upi_transaction_id" class="w-full px-3 py-2 border border-slate-300 rounded-md" placeholder="Enter UPI Transaction ID">
                                <span id="error_upi_transaction_id" class="text-red-600 text-xs"></span>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div>
                            <label for="payment_date" class="block text-sm font-semibold text-slate-700 mb-2">
                                Payment Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                name="payment_cash_date" id="payment_date" value="{{ now()->format('Y-m-d') }}">
                            <span id="error_payment_date" class="text-red-600 text-xs"></span>
                        </div>

                        <!-- Previous Bond -->
                        <div>
                            <label for="previous_bond" class="block text-sm font-semibold text-slate-700 mb-2">Previous Bond</label>
                            <input type="file" class="w-full px-3 py-2 border border-slate-300 rounded-md" name="previous_bond" id="previous_bond">
                            <p class="text-xs text-slate-500 mt-1">Upload the previous bond document if available</p>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-200">
                        <a href="{{ route('admin.doctors.index') }}" class="px-4 py-2 border border-red-500 text-red-500 font-semibold rounded-lg hover:bg-red-50 transition">
                            Cancel
                        </a>
                        <button type="submit" id="doctor_submit_btn" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition" onclick="return renew_doctor_validation();">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<script>
function show() {
    document.getElementById('cheque_check').style.display = 'block';
    document.getElementById('upi_check').style.display = 'none';
}

function hide() {
    document.getElementById('cheque_check').style.display = 'none';
    document.getElementById('upi_check').style.display = 'none';
}

function showUpi() {
    document.getElementById('cheque_check').style.display = 'none';
    document.getElementById('upi_check').style.display = 'block';
}

function onchange_spec() {
    chng_plan(document.getElementById('plan').value);
}

function onchange_payment_mode() {
    const mode = document.getElementById('payment_mode').value;
    if (!mode || mode === '0') return;
    // Additional logic can be added here
}

function change_plan_id_to_name() {
    const planSelect = document.getElementById('plan');
    const planName = planSelect.options[planSelect.selectedIndex] ? planSelect.options[planSelect.selectedIndex].text : '';
    document.getElementById('plan_name').value = planName;
}

function chng_plan(planId) {
    // Additional logic for plan changes
    return false;
}

function renew_doctor_validation() {
    console.log('Validation started');
    
    // Clear previous errors
    document.getElementById('validation').innerHTML = '';
    const errorElements = document.querySelectorAll('[id^="error_"]');
    errorElements.forEach(el => el.textContent = '');

    let isValid = true;
    let errors = [];

    // Required field validation
    const requiredFields = [
        { id: 'money_rc_no', label: 'Money Receipt No.' },
        { id: 'agent_name', label: 'Broker / Agent Name' },
        { id: 'agent_phone_no', label: 'Phone No.' },
        { id: 'renewal_date_rn', label: 'Collection Date' },
        { id: 'policy_date', label: 'Policy Date' },
        { id: 'mobile1', label: 'Mobile 1' },
        { id: 'doctor_email', label: 'Email' },
        { id: 'qualification', label: 'Qualification' },
        { id: 'dob', label: 'Date of Birth' },
        { id: 'medical_registration_no', label: 'Medical Registration No.' },
        { id: 'year_of_reg', label: 'Year of Registration' },
        { id: 'clinic_address', label: 'Clinic Address' },
        { id: 'speciliazition', label: 'Specialization' },
        { id: 'payment_mode', label: 'Payment Mode' },
        { id: 'plan', label: 'Plan' },
        { id: 'coverage', label: 'Insurance Coverage' },
        { id: 'payment_amount', label: 'Medeforum Amount' },
        { id: 'payment_date', label: 'Payment Date' }
    ];

    requiredFields.forEach(field => {
        const element = document.getElementById(field.id);
        if (!element) {
            console.warn('Field not found: ' + field.id);
            return;
        }

        const value = element.value ? element.value.trim() : '';
        
        if (!value || value === '0' || value === '') {
            isValid = false;
            errors.push(field.label + ' is required');
            
            // Map field IDs to error element IDs
            let errorId = 'error_' + field.id;
            if (field.id === 'speciliazition') {
                errorId = 'error_specialization';
            } else if (field.id === 'payment_date') {
                errorId = 'error_payment_date';
            }
            
            const errorEl = document.getElementById(errorId);
            if (errorEl) {
                errorEl.textContent = 'This field is required';
            }
        }
    });

    // Email validation
    const email = document.getElementById('doctor_email').value.trim();
    if (email && !isValidEmail(email)) {
        isValid = false;
        errors.push('Please enter a valid email address');
        document.getElementById('error_doctor_email').textContent = 'Invalid email format';
    }

    // Phone validation
    const phone = document.getElementById('agent_phone_no').value.trim();
    if (phone && !/^\d{10}$/.test(phone.replace(/\D/g, ''))) {
        isValid = false;
        errors.push('Phone number should be 10 digits');
        document.getElementById('error_agent_phone_no').textContent = 'Phone should be 10 digits';
    }

    // Mobile validation
    const mobile1 = document.getElementById('mobile1').value.trim();
    if (mobile1 && !/^\d{10}$/.test(mobile1.replace(/\D/g, ''))) {
        isValid = false;
        errors.push('Mobile 1 should be 10 digits');
        document.getElementById('error_mobile1').textContent = 'Mobile should be 10 digits';
    }

    // Payment method specific validations
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethod) {
        isValid = false;
        errors.push('Payment Method is required');
        document.getElementById('error_payment_method').textContent = 'Please select a payment method';
    } else {
        const method = paymentMethod.value;
        
        if (method === '1') {
            // Cheque validation
            if (!document.getElementById('payment_cheque').value.trim()) {
                isValid = false;
                errors.push('Cheque Number is required');
                document.getElementById('error_cheque_no').textContent = 'Cheque Number is required';
            }
            if (!document.getElementById('payment_bank_name').value.trim()) {
                isValid = false;
                errors.push('Bank Name is required');
                document.getElementById('error_bank').textContent = 'Bank Name is required';
            }
            if (!document.getElementById('payment_branch_name').value.trim()) {
                isValid = false;
                errors.push('Branch Name is required');
                document.getElementById('error_branch').textContent = 'Branch Name is required';
            }
        } else if (method === '3') {
            // UPI validation
            if (!document.getElementById('upi_transaction_id').value.trim()) {
                isValid = false;
                errors.push('UPI Transaction ID is required');
                document.getElementById('error_upi_transaction_id').textContent = 'UPI Transaction ID is required';
            }
        }
    }

    // Payment amount must be numeric and > 0
    const paymentAmount = document.getElementById('payment_amount').value.trim();
    if (paymentAmount) {
        if (isNaN(paymentAmount) || parseFloat(paymentAmount) <= 0) {
            isValid = false;
            errors.push('Payment Amount must be a positive number');
            document.getElementById('error_payment_amount').textContent = 'Must be a positive number';
        }
    }

    // Show errors if any
    if (!isValid) {
        console.log('Validation failed with errors:', errors);
        const errorDiv = document.getElementById('validation');
        const errorList = errors.map(err => '<li class="mb-1">• ' + err + '</li>').join('');
        errorDiv.innerHTML = '<div class="bg-red-50 border border-red-200 rounded p-3"><ul class="list-none">' + errorList + '</ul></div>';
        document.querySelector('.content-header').scrollIntoView({ behavior: 'smooth' });
        return false;
    }

    console.log('Validation passed - submitting form');
    return true;
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}
</script>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    if (typeof flatpickr !== 'undefined') {
        document.querySelectorAll('.datepicker').forEach(function (input) {
            flatpickr(input, { dateFormat: 'd/m/Y' });
        });
    }
</script>
@endsection
