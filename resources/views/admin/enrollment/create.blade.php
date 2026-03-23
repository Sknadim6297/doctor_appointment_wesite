@extends('admin.layouts.app')

@section('title', 'New Enrollment')
@section('page-title', 'Doctor Enrollment Entry')

@section('content')
@php $enrollment = $enrollment ?? null; @endphp
<div
    class="enrollment-shell"
    x-data='enrollmentForm({
        selectedCountry: @json((int) old('country', optional($enrollment)->country ?? $defaultCountryId)),
        selectedState: @json((int) old('state', optional($enrollment)->state ?? $defaultStateId)),
        selectedCity: @json((int) old('city', optional($enrollment)->city ?? $defaultCityId)),
        selectedSpecialization: @json((int) old('specialization_id', optional($enrollment)->specialization_id ?? 0)),
        selectedPlan: @json((int) old('plan', optional($enrollment)->plan ?? 0)),
        selectedCoverage: @json((int) old('coverage_id', optional($enrollment)->coverage_id ?? 0)),
        selectedPaymentMode: @json((string) old('payment_mode', optional($enrollment)->payment_mode ?? '')),
        showPaymentDetails: @json(old('add_payment_details') || old('payment_method') || old('payment_cheque') || old('payment_upi_transaction_id') || isset($enrollment)),
        showServiceAmount: @json(old('service_amount', optional($enrollment)->service_amount ?? null) !== null),
        showTotalAmount: @json(old('total_amount', optional($enrollment)->total_amount ?? null) !== null),
        paymentMethod: @json((string) old('payment_method', optional($enrollment)->payment_method ?? '2')),
    })'
    x-init="init()"
>

    {{-- Flash / Validation errors --}}
    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
            <p class="mb-1 text-sm font-semibold text-red-700">Please fix the following errors:</p>
            <ul class="list-disc pl-5 text-sm text-red-600">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $enrollment ? route('admin.enrollment.update', optional($enrollment)->id) : route('admin.enrollment.store') }}" id="enrollmentForm" novalidate>
        @csrf
        @if($enrollment)
            @method('PUT')
        @endif

        {{-- ─────────────── OFFICIAL USE ─────────────── --}}
        <section class="enrollment-panel mb-8">
            <div class="panel-heading">
                <div>
                    <p class="panel-eyebrow">Doctor Management</p>
                    <h4 class="form-section-title">For Official Use</h4>
                </div>
                <p class="panel-note">Capture internal enrollment identifiers before proposer details.</p>
            </div>
            <div class="form-grid">

                <div class="form-group">
                    <label class="form-label">Customer ID No <span class="text-red-500">*</span></label>
                          <input type="text" name="customer_id_no" id="customer_id_no"
                              value="{{ old('customer_id_no', optional($enrollment)->customer_id_no ?? '') }}"
                           class="form-input @error('customer_id_no') border-red-400 @enderror"
                           placeholder="e.g. IND-19000786-M03-0955">
                    @error('customer_id_no')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Money Receipt No <span class="text-red-500">*</span></label>
                          <input type="text" name="money_rc_no" id="money_rc_no"
                              value="{{ old('money_rc_no', optional($enrollment)->money_rc_no ?? '') }}"
                           class="form-input @error('money_rc_no') border-red-400 @enderror"
                           placeholder="Receipt number">
                    @error('money_rc_no')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Broker / Agent Name <span class="text-red-500">*</span></label>
                          <input type="text" name="agent_name" id="agent_name"
                              value="{{ old('agent_name', optional($enrollment)->agent_name ?? '') }}"
                           class="form-input @error('agent_name') border-red-400 @enderror"
                           placeholder="Agent name">
                    @error('agent_name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Agent Phone No <span class="text-red-500">*</span></label>
                          <input type="text" name="agent_phone_no" id="agent_phone_no"
                              value="{{ old('agent_phone_no', optional($enrollment)->agent_phone_no ?? '') }}"
                           class="form-input @error('agent_phone_no') border-red-400 @enderror"
                           placeholder="10-digit mobile">
                    @error('agent_phone_no')<p class="form-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </section>

        {{-- ─────────────── PROPOSER DETAILS ─────────────── --}}
        <section class="enrollment-panel mb-8">
            <div class="panel-heading">
                <div>
                    <p class="panel-eyebrow">Enrollment Form</p>
                    <h4 class="form-section-title">Proposer's Details</h4>
                </div>
                <p class="panel-note">Basic contact, identity, and qualification information for the proposer.</p>
            </div>
            <div class="form-grid">

                <div class="form-group lg:col-span-2">
                    <label class="form-label">Name of the Proposer <span class="text-red-500">*</span></label>
                          <input type="text" name="doctor_name" id="doctor_name"
                              value="{{ old('doctor_name', optional($enrollment)->doctor_name ?? '') }}"
                           class="form-input @error('doctor_name') border-red-400 @enderror"
                           placeholder="Full name">
                    @error('doctor_name')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group lg:col-span-2">
                    <label class="form-label">Address <span class="text-red-500">*</span></label>
                          <input type="text" name="doctor_address" id="doctor_address"
                              value="{{ old('doctor_address', optional($enrollment)->doctor_address ?? '') }}"
                           class="form-input @error('doctor_address') border-red-400 @enderror"
                           placeholder="Street address">
                    @error('doctor_address')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                {{-- Country --}}
                <div class="form-group">
                    <label class="form-label">Country <span class="text-red-500">*</span></label>
                    <input type="hidden" name="country_name" id="country_name" value="{{ old('country_name', optional($enrollment)->country_name ?? '') }}">
                    <select name="country" id="country" class="form-input @error('country') border-red-400 @enderror"
                            @change="onCountryChange($event.target.value, $event.target.options[$event.target.selectedIndex].text)">
                        <option value="0">--- Select Country ---</option>
                        @foreach($countries as $id => $name)
                            <option value="{{ $id }}" {{ old('country', optional($enrollment)->country ?? $defaultCountryId) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('country')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                {{-- State --}}
                <div class="form-group">
                    <label class="form-label">State <span class="text-red-500">*</span></label>
                    <input type="hidden" name="state_name" id="state_name" value="{{ old('state_name', optional($enrollment)->state_name ?? '') }}">
                    <select name="state" id="state" class="form-input @error('state') border-red-400 @enderror"
                            @change="onStateChange($event.target.value, $event.target.options[$event.target.selectedIndex].text)">
                        <option value="0">--- Select State ---</option>
                        @foreach($states as $sid => $sname)
                            <option value="{{ $sid }}" {{ old('state', optional($enrollment)->state ?? $defaultStateId) == $sid ? 'selected' : '' }}>{{ $sname }}</option>
                        @endforeach
                    </select>
                    @error('state')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                {{-- City --}}
                <div class="form-group">
                    <label class="form-label">City <span class="text-red-500">*</span></label>
                    <input type="hidden" name="city_name" id="city_name" value="{{ old('city_name', optional($enrollment)->city_name ?? '') }}">
                    <select name="city" id="city" class="form-input @error('city') border-red-400 @enderror"
                            @change="onCityChange($event.target.value, $event.target.options[$event.target.selectedIndex].text)">
                        <option value="0">--- Select City ---</option>
                        @foreach($cities as $cid => $cname)
                            <option value="{{ $cid }}" {{ old('city', optional($enrollment)->city ?? $defaultCityId) == $cid ? 'selected' : '' }}>{{ $cname }}</option>
                        @endforeach
                    </select>
                    @error('city')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Postcode <span class="text-red-500">*</span></label>
                          <input type="text" name="postcode" id="postcode"
                              value="{{ old('postcode', optional($enrollment)->postcode ?? '') }}"
                           class="form-input @error('postcode') border-red-400 @enderror"
                           placeholder="6-digit PIN">
                    @error('postcode')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Mobile 1 <span class="text-red-500">*</span></label>
                          <input type="text" name="mobile1" id="mobile1"
                              value="{{ old('mobile1', optional($enrollment)->mobile1 ?? '') }}"
                           class="form-input @error('mobile1') border-red-400 @enderror"
                           placeholder="Primary mobile">
                    @error('mobile1')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Mobile 2 / Phone</label>
                          <input type="text" name="mobile2" id="mobile2"
                              value="{{ old('mobile2', optional($enrollment)->mobile2 ?? '') }}"
                           class="form-input"
                           placeholder="Alternate number">
                </div>

                <div class="form-group">
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                          <input type="email" name="doctor_email" id="doctor_email"
                              value="{{ old('doctor_email', optional($enrollment)->doctor_email ?? '') }}"
                           class="form-input @error('doctor_email') border-red-400 @enderror"
                           placeholder="email@example.com"
                           autocomplete="off">
                    @error('doctor_email')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Date of Birth <span class="text-red-500">*</span></label>
                          <input type="date" name="dob" id="dob"
                              value="{{ old('dob', optional(optional($enrollment)->dob)->format('Y-m-d') ?? '') }}"
                           class="form-input @error('dob') border-red-400 @enderror">
                    @error('dob')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Qualification <span class="text-red-500">*</span></label>
                          <input type="text" name="qualification" id="qualification"
                              value="{{ old('qualification', optional($enrollment)->qualification ?? '') }}"
                           class="form-input @error('qualification') border-red-400 @enderror"
                           placeholder="e.g. MBBS, MD">
                    @error('qualification')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Qualification Year(s) <span class="text-red-500">*</span></label>
                    <select name="qualification_year[]" id="qualification_year"
                            class="form-input @error('qualification_year') border-red-400 @enderror"
                            multiple size="3">
                        <option value="0">Select year(s)</option>
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ in_array($yr, (array) old('qualification_year', optional($enrollment)->qualification_year ?? [])) ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                    <p class="form-helper">Hold Ctrl / Cmd to select multiple years</p>
                    @error('qualification_year')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Medical Registration No <span class="text-red-500">*</span></label>
                          <input type="text" name="medical_registration_no" id="medical_registration_no"
                              value="{{ old('medical_registration_no', optional($enrollment)->medical_registration_no ?? '') }}"
                           class="form-input @error('medical_registration_no') border-red-400 @enderror"
                           placeholder="Registration number">
                    @error('medical_registration_no')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Year of Registration <span class="text-red-500">*</span></label>
                    <select name="year_of_reg" id="year_of_reg"
                            class="form-input @error('year_of_reg') border-red-400 @enderror">
                        <option value="0">Select year</option>
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" {{ old('year_of_reg', optional($enrollment)->year_of_reg ?? '') == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                    @error('year_of_reg')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group lg:col-span-2">
                    <label class="form-label">Clinic Address <span class="text-red-500">*</span></label>
                    <textarea name="clinic_address" id="clinic_address" rows="3"
                              class="form-input @error('clinic_address') border-red-400 @enderror"
                              placeholder="Full clinic / hospital address">{{ old('clinic_address', optional($enrollment)->clinic_address ?? '') }}</textarea>
                    @error('clinic_address')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Aadhaar Card No</label>
                          <input type="text" name="aadhar_card_no" id="aadhar_card_no"
                              value="{{ old('aadhar_card_no', optional($enrollment)->aadhar_card_no ?? '') }}"
                           class="form-input"
                           placeholder="12-digit Aadhaar">
                </div>

                <div class="form-group">
                    <label class="form-label">PAN Card No</label>
                          <input type="text" name="pan_card_no" id="pan_card_no"
                              value="{{ old('pan_card_no', optional($enrollment)->pan_card_no ?? '') }}"
                           class="form-input"
                           placeholder="10-digit PAN">
                </div>

            </div>
        </section>

        {{-- ─────────────── PAYMENT DETAILS ─────────────── --}}
        <section class="enrollment-panel mb-8">
            <div class="panel-heading">
                <div>
                    <p class="panel-eyebrow">Billing</p>
                    <h4 class="form-section-title">Payment Details</h4>
                </div>
                <p class="panel-note">Select specialization, plan, and optional payment settlement information.</p>
            </div>
            <div class="form-grid">

                <div class="form-group lg:col-span-2">
                    <label class="form-label">Are You a (Specialization) <span class="text-red-500">*</span></label>
                    <select name="specialization_id" id="specialization_id"
                            class="form-input @error('specialization_id') border-red-400 @enderror"
                            @change="onSpecializationChange($event.target.value)">
                        <option value="0">--- Select Specialization ---</option>
                        @foreach($specializations as $spec)
                            <option value="{{ $spec->id }}" {{ old('specialization_id', optional($enrollment)->specialization_id ?? '') == $spec->id ? 'selected' : '' }}>
                                {{ $spec->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('specialization_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Mode <span class="text-red-500">*</span></label>
                    <select name="payment_mode" id="payment_mode"
                            class="form-input @error('payment_mode') border-red-400 @enderror"
                            @change="onPaymentModeChange($event.target.value)">
                        <option value="0">--- Select Payment Mode ---</option>
                        @foreach(['Monthly EMI','One Year','Two Year','Three Year','Four Year','Five Year'] as $mode)
                            <option value="{{ $mode }}" {{ old('payment_mode', optional($enrollment)->payment_mode ?? '') === $mode ? 'selected' : '' }}>{{ $mode }}</option>
                        @endforeach
                    </select>
                    @error('payment_mode')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Plan <span class="text-red-500">*</span></label>
                    <input type="hidden" name="plan_name" id="plan_name" value="{{ old('plan_name', optional($enrollment)->plan_name ?? '') }}">
                    <select name="plan" id="plan"
                            class="form-input @error('plan') border-red-400 @enderror"
                            @change="onPlanChange($event.target.value, $event.target.options[$event.target.selectedIndex].text)">
                        <option value="0">--- Select Plan ---</option>
                        <option value="1" {{ old('plan', optional($enrollment)->plan ?? '') == 1 ? 'selected' : '' }}>Normal</option>
                        <option value="2" {{ old('plan', optional($enrollment)->plan ?? '') == 2 ? 'selected' : '' }}>High Risk</option>
                        <option value="3" {{ old('plan', optional($enrollment)->plan ?? '') == 3 ? 'selected' : '' }}>Combo</option>
                    </select>
                    @error('plan')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Coverage / Legal Service <span class="text-red-500">*</span></label>
                    <select name="coverage_id" id="coverage"
                            class="form-input @error('coverage_id') border-red-400 @enderror"
                            @change="onCoverageChange($event.target.value, $event.target.options[$event.target.selectedIndex].dataset.amount)">
                        <option value="0">--- Select Coverage ---</option>
                    </select>
                    @error('coverage_id')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group" x-show="showServiceAmount" x-cloak>
                    <label class="form-label">Insurance Amount <span class="text-red-500">*</span></label>
                          <input type="text" name="service_amount" id="service_amount"
                              value="{{ old('service_amount', optional($enrollment)->service_amount ?? '') }}"
                           class="form-input @error('service_amount') border-red-400 @enderror"
                           placeholder="0.00">
                    @error('service_amount')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label class="form-label">Medeforum Amount <span class="text-red-500">*</span></label>
                          <input type="text" name="payment_amount" id="payment_amount"
                              value="{{ old('payment_amount', optional($enrollment)->payment_amount ?? '') }}"
                           class="form-input @error('payment_amount') border-red-400 @enderror"
                           placeholder="0.00">
                    @error('payment_amount')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group" x-show="showTotalAmount" x-cloak>
                    <label class="form-label">Total Amount <span class="text-red-500">*</span></label>
                          <input type="text" name="total_amount" id="total_amount"
                              value="{{ old('total_amount', optional($enrollment)->total_amount ?? '') }}"
                           class="form-input @error('total_amount') border-red-400 @enderror"
                           placeholder="0.00">
                    @error('total_amount')<p class="form-error">{{ $message }}</p>@enderror
                </div>

            </div>

            {{-- Add payment details toggle --}}
            <div class="mt-6 border-t border-slate-200 pt-5">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                          <input type="checkbox" name="add_payment_details" id="add_payment_details" value="1"
                              class="h-4 w-4 rounded border-slate-300 text-blue-600"
                              {{ old('add_payment_details', isset($enrollment) ? 1 : null) ? 'checked' : '' }}
                              @change="showPaymentDetails = $event.target.checked">
                    Add payment details
                </label>
            </div>

            {{-- Payment details section --}}
            <div x-show="showPaymentDetails" x-transition x-cloak
                 class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="form-group lg:col-span-2">
                        <label class="form-label">Payment Method</label>
                        <div class="flex flex-wrap gap-4 pt-1">
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="radio" name="payment_method" value="1"
                                        {{ old('payment_method', optional($enrollment)->payment_method ?? '') == '1' ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600"
                                       @change="paymentMethod = '1'"> Cheque
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                                                <input type="radio" name="payment_method" value="2" {{ old('payment_method', optional($enrollment)->payment_method ?? '2') == '2' ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600"
                                       @change="paymentMethod = '2'"> Cash
                            </label>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="radio" name="payment_method" value="3"
                                        {{ old('payment_method', optional($enrollment)->payment_method ?? '') == '3' ? 'checked' : '' }}
                                       class="h-4 w-4 text-blue-600"
                                       @change="paymentMethod = '3'"> UPI
                            </label>
                        </div>
                    </div>

                    {{-- Cheque fields --}}
                    <template x-if="paymentMethod === '1'">
                        <div class="contents">
                            <div class="form-group">
                                <label class="form-label">Cheque No <span class="text-red-500">*</span></label>
                                    <input type="text" name="payment_cheque" id="payment_cheque"
                                        value="{{ old('payment_cheque', optional($enrollment)->payment_cheque ?? '') }}"
                                        class="form-input" placeholder="Cheque number">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Bank <span class="text-red-500">*</span></label>
                                    <input type="text" name="payment_bank_name" id="payment_bank_name"
                                        value="{{ old('payment_bank_name', optional($enrollment)->payment_bank_name ?? '') }}"
                                        class="form-input" placeholder="Bank name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Branch <span class="text-red-500">*</span></label>
                                    <input type="text" name="payment_branch_name" id="payment_branch_name"
                                        value="{{ old('payment_branch_name', optional($enrollment)->payment_branch_name ?? '') }}"
                                        class="form-input" placeholder="Branch name">
                            </div>
                        </div>
                    </template>

                    {{-- UPI fields --}}
                    <template x-if="paymentMethod === '3'">
                        <div class="contents">
                            <div class="form-group">
                                <label class="form-label">UPI Transaction ID <span class="text-red-500">*</span></label>
                                    <input type="text" name="payment_upi_transaction_id" id="payment_upi_transaction_id"
                                        value="{{ old('payment_upi_transaction_id', optional($enrollment)->payment_upi_transaction_id ?? '') }}"
                                        class="form-input" placeholder="Transaction ID">
                            </div>
                        </div>
                    </template>

                    <div class="form-group">
                        <label class="form-label">Payment Date <span class="text-red-500">*</span></label>
                           <input type="date" name="payment_cash_date" id="payment_cash_date"
                               value="{{ old('payment_cash_date', optional(optional($enrollment)->payment_cash_date)->format('Y-m-d') ?? '') }}"
                               class="form-input @error('payment_cash_date') border-red-400 @enderror">
                        @error('payment_cash_date')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>

            {{-- Bond to email --}}
            <div class="mt-4">
                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                          <input type="checkbox" name="bond_to_mail" id="bond_to_mail" value="Y"
                              class="h-4 w-4 rounded border-slate-300 text-blue-600"
                              {{ old('bond_to_mail', optional($enrollment)->bond_to_mail ? 'Y' : '') === 'Y' ? 'checked' : '' }}>
                    Send bond to email for this enrollment
                </label>
            </div>

        </section>

        {{-- ─────────────── FORM ACTIONS ─────────────── --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
            <a href="{{ route('admin.enrollment') }}" class="btn btn-default">Cancel</a>
            <button type="submit" id="doctor_submit_btn" class="btn-brand px-6 py-2.5">
                <i class="ri-save-line mr-1"></i> Save and Continue
            </button>
        </div>

    </form>
</div>

{{-- ─────────────── Inline styles to support custom classes ─────────────── --}}
<style>
    .enrollment-shell {
        border: 1px solid #dbe7ff;
        border-radius: 28px;
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 18%);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
        padding: 1.5rem;
    }

    .enrollment-panel {
        border: 1px solid #e2e8f0;
        border-radius: 22px;
        background: #ffffff;
        padding: 1.25rem;
        box-shadow: 0 12px 32px rgba(148, 163, 184, 0.12);
    }

    .panel-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 0.9rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .panel-eyebrow {
        margin: 0 0 0.25rem;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #2563eb;
    }

    .panel-note {
        margin: 0;
        max-width: 24rem;
        font-size: 0.85rem;
        line-height: 1.5;
        color: #64748b;
    }

    .form-section-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }

    .form-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: #334155;
    }

    .form-input {
        width: 100%;
        min-height: 46px;
        border: 1px solid #cbd5e1;
        border-radius: 14px;
        background: #fff;
        padding: 0.72rem 0.9rem;
        font-size: 0.92rem;
        color: #0f172a;
        outline: none;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .form-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
    }

    .form-input[multiple] {
        min-height: 124px;
        padding-top: 0.6rem;
        padding-bottom: 0.6rem;
    }

    textarea.form-input {
        min-height: 110px;
        resize: vertical;
    }

    .form-helper {
        margin: 0.1rem 0 0;
        font-size: 0.78rem;
        color: #64748b;
    }

    .form-error {
        font-size: 0.78rem;
        color: #dc2626;
    }

    @media (min-width: 640px) {
        .form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 1024px) {
        .form-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .enrollment-shell {
            padding: 1rem;
            border-radius: 20px;
        }

        .enrollment-panel {
            padding: 1rem;
            border-radius: 18px;
        }

        .panel-heading {
            flex-direction: column;
        }
    }
</style>

<script>
function enrollmentForm(config) {
    return {
        showPaymentDetails: config.showPaymentDetails ?? false,
        showServiceAmount: config.showServiceAmount ?? false,
        showTotalAmount: config.showTotalAmount ?? false,
        paymentMethod: config.paymentMethod ?? '2',
        selectedPlan: config.selectedPlan ?? 0,
        selectedPaymentMode: config.selectedPaymentMode ?? '',
        selectedCountry: config.selectedCountry ?? 0,
        selectedState: config.selectedState ?? 0,
        selectedCity: config.selectedCity ?? 0,
        selectedSpecialization: config.selectedSpecialization ?? 0,
        selectedCoverage: config.selectedCoverage ?? 0,

        init() {
            this.syncHiddenNames();

            if (this.selectedPlan && this.selectedPlan !== 0) {
                this.loadCoverage();
            }

            const serviceAmount = document.getElementById('service_amount');
            if (serviceAmount) {
                serviceAmount.addEventListener('input', () => this.recalcTotal());
            }

            const paymentAmount = document.getElementById('payment_amount');
            if (paymentAmount) {
                paymentAmount.addEventListener('input', () => this.recalcTotal());
            }
        },

        syncHiddenNames() {
            const countrySelect = document.getElementById('country');
            const stateSelect = document.getElementById('state');
            const citySelect = document.getElementById('city');

            if (countrySelect?.selectedIndex >= 0) {
                document.getElementById('country_name').value = countrySelect.options[countrySelect.selectedIndex].text !== '--- Select Country ---'
                    ? countrySelect.options[countrySelect.selectedIndex].text
                    : '';
            }

            if (stateSelect?.selectedIndex >= 0) {
                document.getElementById('state_name').value = stateSelect.options[stateSelect.selectedIndex].text !== '--- Select State ---'
                    ? stateSelect.options[stateSelect.selectedIndex].text
                    : '';
            }

            if (citySelect?.selectedIndex >= 0) {
                document.getElementById('city_name').value = citySelect.options[citySelect.selectedIndex].text !== '--- Select City ---'
                    ? citySelect.options[citySelect.selectedIndex].text
                    : '';
            }
        },

        onCountryChange(countryId, countryName) {
            this.selectedCountry = Number(countryId);
            this.selectedState = 0;
            this.selectedCity = 0;
            document.getElementById('country_name').value = countryName;
            document.getElementById('state_name').value = '';
            document.getElementById('city_name').value = '';
            this.loadStates(countryId);
        },

        loadStates(countryId) {
            const stateSelect = document.getElementById('state');
            const citySelect  = document.getElementById('city');

            stateSelect.innerHTML = '<option value="0">Loading...</option>';
            citySelect.innerHTML  = '<option value="0">--- Select City ---</option>';

            if (!countryId || countryId == 0) {
                stateSelect.innerHTML = '<option value="0">--- Select State ---</option>';
                return;
            }

            fetch(`{{ route('admin.ajax.states', ['countryId' => '__ID__'], false) }}`.replace('__ID__', countryId))
                .then(r => r.json())
                .then(states => {
                    stateSelect.innerHTML = '<option value="0">--- Select State ---</option>';
                    states.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value       = s.id;
                        opt.textContent = s.name;
                        stateSelect.appendChild(opt);
                    });

                    if (this.selectedState) {
                        stateSelect.value = String(this.selectedState);
                    }
                })
                .catch(() => {
                    stateSelect.innerHTML = '<option value="0">--- Select State ---</option>';
                });
        },

        onStateChange(stateId, stateName) {
            this.selectedState = Number(stateId);
            this.selectedCity = 0;
            document.getElementById('state_name').value = stateName;
            document.getElementById('city_name').value = '';
            this.loadCities(stateId);
        },

        loadCities(stateId) {
            const citySelect = document.getElementById('city');
            citySelect.innerHTML = '<option value="0">Loading...</option>';

            if (!stateId || stateId == 0) {
                citySelect.innerHTML = '<option value="0">--- Select City ---</option>';
                return;
            }

            fetch(`{{ route('admin.ajax.cities', ['stateId' => '__ID__'], false) }}`.replace('__ID__', stateId))
                .then(r => r.json())
                .then(cities => {
                    citySelect.innerHTML = '<option value="0">--- Select City ---</option>';
                    cities.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value       = c.id;
                        opt.textContent = c.name;
                        citySelect.appendChild(opt);
                    });

                    if (this.selectedCity) {
                        citySelect.value = String(this.selectedCity);
                        const selectedOption = citySelect.options[citySelect.selectedIndex];
                        if (selectedOption) {
                            document.getElementById('city_name').value = selectedOption.text;
                        }
                    }
                })
                .catch(() => {
                    citySelect.innerHTML = '<option value="0">--- Select City ---</option>';
                });
        },

        onCityChange(cityId, cityName) {
            this.selectedCity = Number(cityId);
            document.getElementById('city_name').value = cityName;
        },

        onSpecializationChange(specializationId) {
            this.selectedSpecialization = Number(specializationId);
            if (this.selectedPlan && this.selectedPlan !== 0) {
                this.loadCoverage();
            }
        },

        onPaymentModeChange(mode) {
            this.selectedPaymentMode = mode;
            this.loadCoverage();
        },

        onPlanChange(planId, planName) {
            this.selectedPlan = Number(planId);
            document.getElementById('plan_name').value = planName;
            document.getElementById('coverage').innerHTML = '<option value="0">--- Select Coverage ---</option>';
            document.getElementById('payment_amount').value = '';
            this.loadCoverage();
        },

        loadCoverage() {
            if (!this.selectedPlan || this.selectedPlan == 0) return;

            const coverageSelect = document.getElementById('coverage');
            coverageSelect.innerHTML = '<option value="0">Loading...</option>';

            const url = new URL('{{ route('admin.ajax.coverage', [], false) }}', window.location.origin);
            url.searchParams.set('plan', this.selectedPlan);
            url.searchParams.set('payment_mode', this.selectedPaymentMode);
            url.searchParams.set('specialization_id', this.selectedSpecialization || 0);

            fetch(url)
                .then(r => r.json())
                .then(options => {
                    coverageSelect.innerHTML = '<option value="0">--- Select Coverage ---</option>';

                    if (!Array.isArray(options) || options.length === 0) {
                        const emptyOption = document.createElement('option');
                        emptyOption.value = '0';
                        emptyOption.textContent = 'No coverage found for selected Plan/Specialization';
                        coverageSelect.appendChild(emptyOption);
                        return;
                    }

                    options.forEach(opt => {
                        const el = document.createElement('option');
                        el.value              = opt.id;
                        el.textContent        = opt.name + ' — ₹' + parseFloat(opt.amount).toLocaleString('en-IN', {minimumFractionDigits: 2});
                        el.dataset.amount     = opt.amount;
                        coverageSelect.appendChild(el);
                    });

                    if (this.selectedCoverage) {
                        coverageSelect.value = String(this.selectedCoverage);
                        const selectedOption = coverageSelect.options[coverageSelect.selectedIndex];
                        if (selectedOption?.dataset.amount) {
                            this.onCoverageChange(this.selectedCoverage, selectedOption.dataset.amount);
                        }
                    }
                })
                .catch(() => {
                    coverageSelect.innerHTML = '<option value="0">--- Select Coverage ---</option>';
                });
        },

        onCoverageChange(covId, amount) {
            if (amount) {
                document.getElementById('payment_amount').value = parseFloat(amount).toFixed(2);
                this.showTotalAmount = true;
                this.showServiceAmount = true;
                this.recalcTotal();
            }
        },

        recalcTotal() {
            const med  = parseFloat(document.getElementById('payment_amount').value) || 0;
            const svc  = parseFloat(document.getElementById('service_amount')?.value) || 0;
            const tot  = document.getElementById('total_amount');
            if (tot) tot.value = (med + svc).toFixed(2);
        },
    };
}
</script>
@endsection
