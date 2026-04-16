@extends('admin.layouts.app')

@section('title', $doctor->doctor_name ?? 'Doctor Details')
@section('page-title', 'Doctor Profile')

@section('content')
<style>
    .legacy-wrap { display: grid; grid-template-columns: 300px 1fr; gap: 1rem; }
    .legacy-card { border: 1px solid #dbe3ee; border-radius: 12px; background: #fff; overflow: hidden; }
    .legacy-card-head { background: #f8fbff; border-bottom: 1px solid #dbe3ee; padding: 0.75rem 1rem; font-weight: 700; color: #0f172a; }
    .legacy-card-body { padding: 1rem; }
    .profile-photo { width: 110px; height: 110px; border-radius: 999px; border: 3px solid #ef4444; object-fit: cover; }
    .quick-actions { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.75rem; justify-content: center; }
    .qbtn { border: 0; border-radius: 8px; padding: 0.4rem 0.6rem; font-size: 0.8rem; font-weight: 700; color: #fff; display: inline-flex; align-items: center; gap: 0.35rem; text-decoration: none; }
    .qbtn-green { background: #059669; }
    .qbtn-blue { background: #2563eb; }
    .qbtn-red { background: #dc2626; }
    .qbtn-amber { background: #d97706; }
    .qbtn-sky { background: #0284c7; }
    .meta-item { margin: 0.65rem 0; font-size: 0.88rem; }
    .meta-item b { color: #0f172a; }
    .about-row { border-bottom: 1px dashed #dbe3ee; padding: 0.6rem 0; }
    .about-row:last-child { border-bottom: none; }

    .legacy-tabs { display: flex; flex-wrap: wrap; gap: 0.35rem; padding: 0.75rem 0.75rem 0; border-bottom: 1px solid #dbe3ee; }
    .legacy-tab-btn { border: 0; background: #eef2ff; color: #374151; border-radius: 8px 8px 0 0; padding: 0.45rem 0.7rem; font-size: 0.83rem; font-weight: 700; cursor: pointer; }
    .legacy-tab-btn.active { background: #1d4ed8; color: #fff; }
    .legacy-tab-panel { display: none; padding: 1rem; }
    .legacy-tab-panel.active { display: block; }
    .kv-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .kv-item { border-bottom: 1px solid #e2e8f0; padding-bottom: 0.45rem; margin-bottom: 0.45rem; }
    .kv-key { font-size: 0.78rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
    .kv-val { font-size: 0.92rem; color: #0f172a; font-weight: 600; }

    .mini-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
    .mini-table th, .mini-table td { border: 1px solid #dbe3ee; padding: 0.45rem 0.5rem; vertical-align: top; }
    .mini-table th { background: #1e3a8a; color: #fff; font-size: 0.76rem; text-transform: uppercase; letter-spacing: 0.02em; }
    .empty-note { padding: 1rem; border: 1px dashed #cbd5e1; border-radius: 8px; color: #64748b; background: #f8fafc; }

    .modal-backdrop-lite { position: fixed; inset: 0; background: rgba(2, 6, 23, 0.65); display: none; align-items: center; justify-content: center; z-index: 80; padding: 1rem; }
    .modal-backdrop-lite.show { display: flex; }
    .modal-lite { width: 100%; max-width: 620px; border-radius: 14px; overflow: hidden; border: 1px solid #cbd5e1; background: #fff; }
    .modal-lite-head { display: flex; align-items: center; justify-content: space-between; padding: 0.8rem 1rem; border-bottom: 1px solid #e2e8f0; }
    .modal-lite-body { padding: 1rem; }
    .modal-lite-foot { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 0.8rem 1rem; border-top: 1px solid #e2e8f0; }
    .modal-input, .modal-textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0.55rem 0.7rem; }
    .modal-textarea { min-height: 110px; resize: vertical; }
    .success-pane { text-align: center; color: #1e73a5; }

    @media (max-width: 1024px) {
        .legacy-wrap { grid-template-columns: 1fr; }
    }
</style>

<div class="mb-4">
    <a href="{{ route('admin.doctors.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 mb-4">
        <i class="ri-arrow-left-line"></i>
        Back to Doctor List
    </a>

    <div class="legacy-wrap">
        <div class="space-y-3">
            <div class="legacy-card">
                <div class="legacy-card-body">
                    <div class="text-center">
                        <img class="profile-photo mx-auto" src="{{ asset('assets/images/user-image.png') }}" alt="Doctor image">

                        <div class="quick-actions">
                            <a href="{{ route('admin.enrollment.legacy-edit', $doctor->id) }}" class="qbtn qbtn-green" title="Edit"><i class="ri-pencil-line"></i> Edit</a>
                            <a href="{{ route('admin.enrollment.legacy-renewal', ['doctor' => $doctor->id, 'renewType' => 'renewal']) }}" class="qbtn qbtn-red" title="Renew"><i class="ri-refresh-line"></i> Renew</a>
                            <button type="button" class="qbtn qbtn-blue" onclick="openMailModal()" title="Send Mail"><i class="ri-mail-line"></i> Send Mail</button>
                            <button type="button" class="qbtn qbtn-sky" onclick="openSmsModal()" title="Send SMS"><i class="ri-message-2-line"></i> Send SMS</button>
                            <button type="button" class="qbtn qbtn-amber" id="resend_bond_{{ $doctor->id }}" onclick="resendBond({{ $doctor->id }}, '{{ $doctor->doctor_email }}')" title="Resend bond"><i class="ri-check-line"></i> Resend Bond</button>
                            <button type="button" class="qbtn qbtn-blue" id="resend_money_reciept_{{ $doctor->id }}" onclick="resendReceipt({{ $doctor->id }}, '{{ $doctor->doctor_email }}')" title="Resend money receipt"><i class="ri-send-plane-line"></i> Resend Receipt</button>
                        </div>
                    </div>

                    <h3 class="mt-3 text-center text-lg font-bold text-slate-900">{{ $doctor->doctor_name ?? 'N/A' }}</h3>
                    <p class="text-center text-sm text-slate-600"><b>{{ $doctor->specialization->name ?? 'N/A' }}</b></p>

                    <p class="meta-item text-center"><b>Money Receipt No:</b> {{ $doctor->money_rc_no ?? 'Pending' }}</p>
                    <p class="meta-item text-center"><b>Membership No:</b> {{ $doctor->customer_id_no ?? 'N/A' }}</p>
                    <p class="meta-item text-center"><b>{{ $doctor->doctor_email ?? 'info@medeforum.com' }}</b></p>
                </div>
            </div>

            <div class="legacy-card">
                <div class="legacy-card-head">About Doctor</div>
                <div class="legacy-card-body">
                    <div class="about-row"><b>Mobile no.</b><div>{{ $doctor->mobile1 ?? 'N/A' }}{{ $doctor->mobile2 ? ' / ' . $doctor->mobile2 : '' }}</div></div>
                    <div class="about-row"><b>Insurance Coverage</b><div>Rs. {{ number_format((float)($doctor->payment_amount ?? 0), 0) }}</div></div>
                    <div class="about-row"><b>Premium amount</b><div>Rs. {{ number_format((float)($doctor->service_amount ?? 0), 0) }}</div></div>
                    <div class="about-row"><b>Cheque Amount</b><div>Rs. {{ number_format((float)($doctor->total_amount ?? 0), 0) }}/-</div></div>
                    <div class="about-row"><b>Next Renewal date</b><div>{{ $renewalDate->format('d/m/Y') }}</div></div>
                    <div class="about-row"><b>Policy Date</b><div>{{ optional($doctor->payment_cash_date)->format('d/m/Y') ?? 'N/A' }}</div></div>
                </div>
            </div>
        </div>

        <div class="legacy-card" data-active-tab="{{ $activeTab ?? 'details' }}">
            <div class="legacy-tabs">
                <button class="legacy-tab-btn" data-tab="details">Details</button>
                <button class="legacy-tab-btn" data-tab="documents">Documents</button>
                <button class="legacy-tab-btn" data-tab="cases">Cases</button>
                <button class="legacy-tab-btn" data-tab="policies">Policies</button>
                <button class="legacy-tab-btn" data-tab="posts">Posts</button>
                <button class="legacy-tab-btn" data-tab="premium">Premium/Policy</button>
                <button class="legacy-tab-btn" data-tab="receipts">Money Receipt</button>
                <button class="legacy-tab-btn" data-tab="bonds">Previous Bonds</button>
                <a class="qbtn qbtn-blue ml-auto" target="_blank" href="{{ route('admin.receipts.view', $doctor->id) }}"><i class="ri-eye-line"></i> Renewal bond</a>
            </div>

            <div id="tab-details" class="legacy-tab-panel">
                <div class="kv-grid">
                    <div>
                        <div class="kv-item"><div class="kv-key">Broker/Agent name</div><div class="kv-val">{{ $doctor->agent_name ?? 'N/A' }}</div></div>
                        <div class="kv-item"><div class="kv-key">Agent phone No</div><div class="kv-val">{{ $doctor->agent_phone_no ?? 'N/A' }}</div></div>
                        <div class="kv-item"><div class="kv-key">Policy No</div><div class="kv-val">{{ $doctor->money_rc_no ?? 'N/A' }}</div></div>
                        <div class="kv-item"><div class="kv-key">Address</div><div class="kv-val">{{ $doctor->doctor_address ?? 'N/A' }}</div></div>
                        <div class="kv-item"><div class="kv-key">Date of birth</div><div class="kv-val">{{ optional($doctor->dob)->format('d/m/Y') ?? 'N/A' }}</div></div>
                        <div class="kv-item"><div class="kv-key">Qualification</div><div class="kv-val">{{ $doctor->qualification ?? 'N/A' }}</div></div>
                    </div>
                    <div>
                        <div class="kv-item"><div class="kv-key">Qualification year</div><div class="kv-val">{{ is_array($doctor->qualification_year) ? implode(', ', $doctor->qualification_year) : ($doctor->qualification_year ?? 'N/A') }}</div></div>
                        <div class="kv-item"><div class="kv-key">Medical Registration</div><div class="kv-val">{{ $doctor->medical_registration_no ?? 'N/A' }}</div></div>
                        <div class="kv-item"><div class="kv-key">Year of Registration</div><div class="kv-val">{{ $doctor->year_of_reg ?? 'N/A' }}</div></div>
                        <div class="kv-item"><div class="kv-key">Payment Mode</div><div class="kv-val">{{ $doctor->payment_mode ?? 'N/A' }}</div></div>
                        <div class="kv-item"><div class="kv-key">Plan</div><div class="kv-val">{{ $planName }}</div></div>
                        <div class="kv-item"><div class="kv-key">Payment date</div><div class="kv-val">{{ optional($doctor->payment_cash_date)->format('d/m/Y') ?? 'N/A' }}</div></div>
                    </div>
                </div>
            </div>

            <div id="tab-documents" class="legacy-tab-panel">
                <div class="flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('admin.posts') }}" class="qbtn qbtn-blue"><i class="ri-upload-2-line"></i> Upload document</a>
                </div>
                <div class="empty-note">Document upload/edit panel will be wired to a dedicated doctor-document module. Existing post records are visible under the Posts tab.</div>
            </div>

            <div id="tab-cases" class="legacy-tab-panel">
                <div class="flex flex-wrap gap-2 mb-3">
                    <a href="{{ route('admin.cases') }}" class="qbtn qbtn-blue"><i class="ri-add-line"></i> New Case</a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>SL No</th>
                            <th>Case No</th>
                            <th>Category</th>
                            <th>Stage</th>
                            <th>Court</th>
                            <th>Next Date</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cases as $case)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $case->case_number ?? 'N/A' }}</td>
                                <td>{{ $case->case_cat ?? 'N/A' }}</td>
                                <td>{{ $case->stage ?? 'N/A' }}</td>
                                <td>{{ $case->court ?? 'N/A' }}{{ $case->court_year ? ' (' . $case->court_year . ')' : '' }}</td>
                                <td>{{ optional($case->next_date)->format('d/m/Y') ?? 'N/A' }}</td>
                                <td>Rs. {{ number_format((float) ($case->direct_payment_amount ?? 0), 0) }}</td>
                                <td>
                                    <a href="{{ route('admin.cases') }}" class="qbtn qbtn-green" title="Open cases list"><i class="ri-eye-line"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="empty-note">No cases are linked with this doctor yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div id="tab-policies" class="legacy-tab-panel">
                <div class="mb-3">
                    <a href="{{ route('admin.policy-receipt.legacy-create', $doctor->id) }}" class="qbtn qbtn-blue"><i class="ri-add-line"></i> Add policy</a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>SL No</th>
                            <th>Policy No</th>
                            <th>Receive Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($policyReceipts as $policy)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $policy->policy_no ?? 'N/A' }}</td>
                                <td>{{ optional($policy->receive_date)->format('d/m/Y') ?? 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('admin.policy-receipt.show', $policy->id) }}" class="qbtn qbtn-green" target="_blank"><i class="ri-eye-line"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No policy available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div id="tab-posts" class="legacy-tab-panel">
                <div class="mb-3">
                    <a href="{{ route('admin.posts') }}" class="qbtn qbtn-blue"><i class="ri-add-line"></i> New Post</a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>SL No</th>
                            <th>Date of post</th>
                            <th>Consignment no.</th>
                            <th>Post by</th>
                            <th>Received date</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($posts as $post)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ optional($post->post_doc_date)->format('d/m/Y') ?? 'N/A' }}</td>
                                <td>{{ $post->post_doc_consignment_no ?? 'N/A' }}</td>
                                <td>{{ $post->post_doc_by ?? 'N/A' }}</td>
                                <td>{{ optional($post->post_doc_recieved_date)->format('d/m/Y') ?? 'N/A' }}</td>
                                <td>{{ $post->post_doc_remark ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No post data available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div id="tab-premium" class="legacy-tab-panel">
                <div class="mb-3 flex flex-wrap gap-2">
                    <a href="{{ route('admin.premium-amount.index', ['search' => $doctor->doctor_name]) }}" class="qbtn qbtn-blue"><i class="ri-money-dollar-circle-line"></i> Premium list</a>
                    <a href="{{ route('admin.policy-receipt.legacy-create', $doctor->id) }}" class="qbtn qbtn-green"><i class="ri-file-add-line"></i> Submit policy received</a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Renewal date</th>
                            <th>Premium amount</th>
                            <th>Policy received count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $doctor->doctor_name ?? 'N/A' }}</td>
                            <td>{{ $renewalDate->format('d/m/Y') }}</td>
                            <td>Rs. {{ number_format((float)($doctor->service_amount ?? 0), 0) }}</td>
                            <td>{{ $policyReceipts->count() }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="tab-receipts" class="legacy-tab-panel">
                <div class="mb-3">
                    <a href="{{ route('admin.receipts') }}?search={{ urlencode($doctor->doctor_name ?? '') }}" class="qbtn qbtn-blue"><i class="ri-add-line"></i> Add / Edit money receipt</a>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Money receipt no.</th>
                            <th>Dr. name</th>
                            <th>Mem. no</th>
                            <th>Payment AMT</th>
                            <th>Ins amt</th>
                            <th>Plan</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $doctor->money_rc_no ?? 'Pending' }}</td>
                            <td>{{ $doctor->doctor_name ?? 'N/A' }}</td>
                            <td>{{ $doctor->customer_id_no ?? 'N/A' }}</td>
                            <td>Rs. {{ number_format((float)($doctor->payment_amount ?? 0), 0) }}</td>
                            <td>Rs. {{ number_format((float)($doctor->service_amount ?? 0), 0) }}</td>
                            <td>{{ $planName }}</td>
                            <td>
                                @if($doctor->money_rc_no)
                                    <a href="{{ route('admin.receipts.view', $doctor->id) }}" target="_blank" class="qbtn qbtn-green"><i class="ri-eye-line"></i></a>
                                @else
                                    N/A
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="tab-bonds" class="legacy-tab-panel">
                <div class="empty-note">There are no previous bond available.</div>
            </div>
        </div>
    </div>
</div>

<div id="mailModal" class="modal-backdrop-lite" aria-hidden="true">
    <div class="modal-lite" role="dialog" aria-modal="true" aria-labelledby="sendmail_title">
        <div class="modal-lite-head">
            <h4 class="text-base font-semibold" id="sendmail_title">Send mail to {{ $doctor->doctor_name ?? 'Doctor' }}</h4>
            <button type="button" class="text-slate-500" onclick="closeMailModal()">x</button>
        </div>
        <div class="modal-lite-body test_response_mail"></div>
        <div class="modal-lite-body send_mail_body">
            <div class="mb-3">
                <label class="text-sm font-semibold">Mail to:</label>
                <span class="modal-input block mt-1" id="display_email">{{ $doctor->doctor_email ?? 'info@medeforum.com' }}</span>
                <input type="hidden" id="email" name="email" value="{{ $doctor->doctor_email ?? '' }}">
            </div>
            <div>
                <label class="text-sm font-semibold">Message:</label>
                <textarea class="modal-textarea mt-1" id="message" name="message"></textarea>
            </div>
        </div>
        <div class="modal-lite-body send_mail_success success-pane" style="display:none;"></div>
        <div class="modal-lite-foot send_mail_footer">
            <button type="button" class="btn btn-default" onclick="closeMailModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="sendmail_action()">Send mail</button>
        </div>
    </div>
</div>

<div id="smsModal" class="modal-backdrop-lite" aria-hidden="true">
    <div class="modal-lite" role="dialog" aria-modal="true" aria-labelledby="sendsms_title">
        <div class="modal-lite-head">
            <h4 class="text-base font-semibold" id="sendsms_title">Send SMS to {{ $doctor->doctor_name ?? 'Doctor' }}</h4>
            <button type="button" class="text-slate-500" onclick="closeSmsModal()">x</button>
        </div>
        <div class="modal-lite-body test_response_sms"></div>
        <div class="modal-lite-body send_sms_body">
            <div class="mb-3">
                <label class="text-sm font-semibold">SMS to:</label>
                <span class="modal-input block mt-1" id="display_mobile_no">{{ $doctor->mobile1 ?? '' }}</span>
                <input type="hidden" id="sms_mobile_no" name="sms_mobile_no" value="{{ $doctor->mobile1 ?? '' }}">
                <input type="hidden" id="sms_full_name" name="sms_full_name" value="{{ $doctor->doctor_name ?? '' }}">
            </div>
        </div>
        <div class="modal-lite-body send_sms_success success-pane" style="display:none;"></div>
        <div class="modal-lite-foot send_sms_footer">
            <button type="button" class="btn btn-default" onclick="closeSmsModal()">Close</button>
            <button type="button" class="btn btn-primary" onclick="sendsms_action()">Send SMS</button>
        </div>
    </div>
</div>

<script>
(function () {
    const csrfToken = '{{ csrf_token() }}';
    const doctorId = {{ $doctor->id }};
    const doctorEmail = '{{ $doctor->doctor_email }}';
    const doctorPhone = '{{ $doctor->mobile1 }}';

    function activateTab(name) {
        document.querySelectorAll('.legacy-tab-btn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.tab === name);
        });

        document.querySelectorAll('.legacy-tab-panel').forEach((panel) => {
            panel.classList.toggle('active', panel.id === 'tab-' + name);
        });
    }

    document.querySelectorAll('.legacy-tab-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
            activateTab(this.dataset.tab);
        });
    });

    const wrapper = document.querySelector('.legacy-card[data-active-tab]');
    activateTab(wrapper ? wrapper.dataset.activeTab : 'details');

    window.openMailModal = function () {
        const modal = document.getElementById('mailModal');
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.querySelector('.send_mail_success').style.display = 'none';
        document.querySelector('.send_mail_body').style.display = 'block';
        document.querySelector('.send_mail_footer').style.display = 'flex';
    };

    window.closeMailModal = function () {
        const modal = document.getElementById('mailModal');
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    };

    window.sendmail_action = function () {
        if (!doctorEmail) {
            alert('No email address on file for this doctor.');
            return;
        }

        fetch(`/admin/doctors/${doctorId}/send-mail`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: doctorEmail,
                message: document.getElementById('message').value || ''
            })
        })
        .then((r) => r.json())
        .then((data) => {
            document.querySelector('.send_mail_body').style.display = 'none';
            document.querySelector('.send_mail_footer').style.display = 'none';
            const pane = document.querySelector('.send_mail_success');
            pane.style.display = 'block';
            pane.innerHTML = '<i class="ri-thumb-up-line" style="font-size:3rem;"></i><h4>Email successfully sent.</h4><p>' + (data.message || '') + '</p>';
        })
        .catch((err) => alert('Error: ' + err.message));
    };

    window.openSmsModal = function () {
        const modal = document.getElementById('smsModal');
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.querySelector('.send_sms_success').style.display = 'none';
        document.querySelector('.send_sms_body').style.display = 'block';
        document.querySelector('.send_sms_footer').style.display = 'flex';
    };

    window.closeSmsModal = function () {
        const modal = document.getElementById('smsModal');
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    };

    window.sendsms_action = function () {
        if (!doctorPhone) {
            alert('No phone number on file for this doctor.');
            return;
        }

        fetch(`/admin/doctors/${doctorId}/send-sms`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                mobile: doctorPhone,
                full_name: '{{ $doctor->doctor_name }}'
            })
        })
        .then((r) => r.json())
        .then((data) => {
            document.querySelector('.send_sms_body').style.display = 'none';
            document.querySelector('.send_sms_footer').style.display = 'none';
            const pane = document.querySelector('.send_sms_success');
            pane.style.display = 'block';
            pane.innerHTML = '<i class="ri-thumb-up-line" style="font-size:3rem;"></i><h4>SMS Successfully Sent.</h4><p>' + (data.message || '') + '</p><button class="btn btn-success" onclick="closeSmsModal()">Close</button>';
        })
        .catch((err) => alert('Error: ' + err.message));
    };

    window.sendMail = function (doctorId, email) {
        openMailModal();
    };

    window.sendSms = function (doctorId, phone) {
        openSmsModal();
    };

    window.resend_bond = function (doctorId, email) {
        resendBond(doctorId, email);
    };

    window.resend_money_reciept = function (doctorId, email) {
        resendReceipt(doctorId, email);
    };

    window.resendBond = function (doctorId, email) {
        if (!email) {
            alert('No email address on file for this doctor.');
            return;
        }

        fetch(`/admin/doctors/${doctorId}/resend-bond`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then((r) => r.json())
        .then((data) => alert(data.message || 'Bond resend action completed.'))
        .catch((err) => alert('Error: ' + err.message));
    };

    window.resendReceipt = function (doctorId, email) {
        if (!email) {
            alert('No email address on file for this doctor.');
            return;
        }

        fetch(`/admin/doctors/${doctorId}/resend-receipt`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        })
        .then((r) => r.json())
        .then((data) => alert(data.message || 'Receipt resend action completed.'))
        .catch((err) => alert('Error: ' + err.message));
    };
})();
</script>
@endsection
