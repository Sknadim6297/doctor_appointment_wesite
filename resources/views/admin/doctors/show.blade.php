@extends('admin.layouts.app')

@section('title', $doctor->doctor_name ?? 'Doctor Details')
@section('page-title', 'Doctor Profile')

@section('content')
<style>
    .doctor-profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
    }
    .doctor-profile-card {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e2e8f0;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .doctor-tabs {
        display: flex;
        gap: 0.5rem;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .doctor-tab-btn {
        padding: 0.75rem 1.5rem;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        color: #64748b;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        position: relative;
        top: 2px;
    }
    .doctor-tab-btn:hover {
        color: #0f172a;
    }
    .doctor-tab-btn.active {
        color: #667eea;
        border-bottom-color: #667eea;
    }
    .doctor-tab-content {
        display: none;
    }
    .doctor-tab-content.active {
        display: block;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
    }
    .info-item {
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 1rem;
    }
    .info-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .info-value {
        font-size: 1rem;
        font-weight: 500;
        color: #0f172a;
        margin-top: 0.25rem;
    }
    .status-badge {
        display: inline-block;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
    }
    .status-renewal { background: #dbeafe; color: #0369a1; }
    .status-due-soon { background: #fef08a; color: #78350f; }
    .status-overdue { background: #fecaca; color: #7c2d12; }
    .action-bar {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 1.5rem;
    }
    .action-btn {
        padding: 0.6rem 1rem;
        border-radius: 0.5rem;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .action-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .action-btn-primary { background: #667eea; color: white; }
    .action-btn-success { background: #10b981; color: white; }
    .action-btn-warning { background: #f59e0b; color: white; }
    .action-btn-danger { background: #ef4444; color: white; }
    .action-btn-info { background: #3b82f6; color: white; }
</style>

<div class="mb-4" x-data="{ section: 'details' }">
    {{-- Back Button --}}
    <a href="{{ route('admin.doctors.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 mb-4">
        <i class="ri-arrow-left-line"></i>
        Back to Doctor List
    </a>

    {{-- Doctor Header --}}
    <div class="doctor-profile-header">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">{{ $doctor->doctor_name ?? 'Unknown' }}</h1>
                <div class="flex gap-4 flex-wrap">
                    <div>
                        <p class="text-sm opacity-90">Email</p>
                        <p class="font-semibold">{{ $doctor->doctor_email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Phone</p>
                        <p class="font-semibold">{{ $doctor->mobile1 ?? 'N/A' }} / {{ $doctor->mobile2 ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm opacity-90">Specialization</p>
                        <p class="font-semibold">{{ $doctor->specialization->name ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm opacity-90 mb-2">Renewal Status</p>
                <span class="status-badge status-{{ $renewalStatus }}">
                    @switch($renewalStatus)
                        @case('upcoming') Upcoming Renewal @break
                        @case('due_soon') Due Soon @break
                        @case('overdue_recent') Recently Overdue @break
                        @default Overdue @break
                    @endswitch
                </span>
                <p class="text-sm opacity-90 mt-2">{{ $daysUntilRenewal > 0 ? 'Due in ' . abs($daysUntilRenewal) . ' days' : 'Overdue by ' . abs($daysUntilRenewal) . ' days' }}</p>
            </div>
        </div>
    </div>

    {{-- Action Bar --}}
    <div class="action-bar">
        <button class="action-btn action-btn-success" onclick="sendMail({{ $doctor->id }}, '{{ $doctor->doctor_email }}')">
            <i class="ri-mail-line"></i> Send Email
        </button>
        <button class="action-btn action-btn-info" onclick="sendSms({{ $doctor->id }}, '{{ $doctor->mobile1 }}')">
            <i class="ri-message-2-line"></i> Send SMS
        </button>
        <button class="action-btn action-btn-warning" onclick="resendBond({{ $doctor->id }}, '{{ $doctor->doctor_email }}')">
            <i class="ri-send-plane-line"></i> Resend Bond
        </button>
        <button class="action-btn action-btn-primary" onclick="resendReceipt({{ $doctor->id }}, '{{ $doctor->doctor_email }}')">
            <i class="ri-mail-send-line"></i> Resend Receipt
        </button>
        <a href="#" class="action-btn action-btn-primary">
            <i class="ri-pencil-line"></i> Edit
        </a>
        <a href="#" class="action-btn action-btn-danger">
            <i class="ri-delete-bin-line"></i> Delete
        </a>
    </div>

    {{-- Tabs --}}
    <div class="doctor-profile-card">
        <div class="doctor-tabs">
            <button class="doctor-tab-btn active" onclick="switchTab('details')">
                <i class="ri-information-line"></i> Details
            </button>
            <button class="doctor-tab-btn" onclick="switchTab('documents')">
                <i class="ri-file-list-line"></i> Documents
            </button>
            <button class="doctor-tab-btn" onclick="switchTab('cases')">
                <i class="ri-folder-open-line"></i> Cases
            </button>
            <button class="doctor-tab-btn" onclick="switchTab('policies')">
                <i class="ri-file-shield-line"></i> Policies
            </button>
            <button class="doctor-tab-btn" onclick="switchTab('posts')">
                <i class="ri-article-line"></i> Posts
            </button>
            <button class="doctor-tab-btn" onclick="switchTab('premium')">
                <i class="ri-money-dollar-circle-line"></i> Premium/Policy
            </button>
            <button class="doctor-tab-btn" onclick="switchTab('receipts')">
                <i class="ri-receipt-line"></i> Receipts
            </button>
            <button class="doctor-tab-btn" onclick="switchTab('bonds')">
                <i class="ri-file-text-line"></i> Bonds
            </button>
        </div>

        {{-- Details Tab --}}
        <div id="details-tab" class="doctor-tab-content active">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Personal Information --}}
                <div>
                    <h3 class="text-lg font-bold mb-4 pb-2 border-b-2 border-blue-500">Personal Information</h3>
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value">{{ optional($doctor->dob)->format('d M Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Aadhaar Card</div>
                        <div class="info-value">{{ $doctor->aadhar_card_no ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">PAN Card</div>
                        <div class="info-value">{{ $doctor->pan_card_no ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value">{{ $doctor->doctor_address ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Clinic Address</div>
                        <div class="info-value">{{ $doctor->clinic_address ?? 'N/A' }}</div>
                    </div>
                </div>

                {{-- Professional Information --}}
                <div>
                    <h3 class="text-lg font-bold mb-4 pb-2 border-b-2 border-green-500">Professional Information</h3>
                    <div class="info-item">
                        <div class="info-label">Qualification</div>
                        <div class="info-value">{{ $doctor->qualification ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Qualification Year</div>
                        <div class="info-value">
                            @if(is_array($doctor->qualification_year) && count($doctor->qualification_year) > 0)
                                {{ implode(', ', $doctor->qualification_year) }}
                            @else
                                {{ $doctor->qualification_year ?? 'N/A' }}
                            @endif
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Medical Registration No</div>
                        <div class="info-value">{{ $doctor->medical_registration_no ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Year of Registration</div>
                        <div class="info-value">{{ $doctor->year_of_reg ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Specialization</div>
                        <div class="info-value">{{ $doctor->specialization->name ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- Enrollment Information --}}
            <div class="mt-6">
                <h3 class="text-lg font-bold mb-4 pb-2 border-b-2 border-purple-500">Enrollment Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="info-item">
                        <div class="info-label">Customer ID No</div>
                        <div class="info-value font-mono">{{ $doctor->customer_id_no ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Money Receipt No</div>
                        <div class="info-value">{{ $doctor->money_rc_no ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Plan Type</div>
                        <div class="info-value font-semibold">{{ $planName }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Mode</div>
                        <div class="info-value">{{ $doctor->payment_mode ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Insurance Coverage</div>
                        <div class="info-value font-semibold">₹{{ number_format($doctor->payment_amount, 0) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Premium Amount</div>
                        <div class="info-value font-semibold">₹{{ number_format($doctor->service_amount, 0) }}</div>
                    </div>
                </div>
            </div>

            {{-- Broker/Agent Information --}}
            <div class="mt-6">
                <h3 class="text-lg font-bold mb-4 pb-2 border-b-2 border-orange-500">Broker / Agent Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="info-item">
                        <div class="info-label">Agent Name</div>
                        <div class="info-value">{{ $doctor->agent_name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Agent Phone No</div>
                        <div class="info-value">{{ $doctor->agent_phone_no ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>

            {{-- Enrollment Dates --}}
            <div class="mt-6">
                <h3 class="text-lg font-bold mb-4 pb-2 border-b-2 border-indigo-500">Enrollment Dates</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="info-item">
                        <div class="info-label">Enrollment Date</div>
                        <div class="info-value">{{ optional($doctor->created_at)->format('d M Y') ?? 'N/A' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Next Renewal Date</div>
                        <div class="info-value font-semibold">{{ $renewalDate->format('d M Y') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documents Tab --}}
        <div id="documents-tab" class="doctor-tab-content">
            <div class="text-center py-12 text-gray-500">
                <i class="ri-file-line text-4xl mb-4 inline-block"></i>
                <p>Document management to be implemented</p>
            </div>
        </div>

        {{-- Cases Tab --}}
        <div id="cases-tab" class="doctor-tab-content">
            <div class="text-center py-12 text-gray-500">
                <i class="ri-folder-open-line text-4xl mb-4 inline-block"></i>
                <p>Cases management to be implemented</p>
            </div>
        </div>

        {{-- Policies Tab --}}
        <div id="policies-tab" class="doctor-tab-content">
            <div class="text-center py-12 text-gray-500">
                <i class="ri-file-shield-line text-4xl mb-4 inline-block"></i>
                <p>Policies management to be implemented</p>
            </div>
        </div>

        {{-- Posts Tab --}}
        <div id="posts-tab" class="doctor-tab-content">
            <div class="text-center py-12 text-gray-500">
                <i class="ri-article-line text-4xl mb-4 inline-block"></i>
                <p>Posts management to be implemented</p>
            </div>
        </div>

        {{-- Premium/Policy Tab --}}
        <div id="premium-tab" class="doctor-tab-content">
            <div class="text-center py-12 text-gray-500">
                <i class="ri-money-dollar-circle-line text-4xl mb-4 inline-block"></i>
                <p>Premium/Policy management to be implemented</p>
            </div>
        </div>

        {{-- Receipts Tab --}}
        <div id="receipts-tab" class="doctor-tab-content">
            <div class="text-center py-12 text-gray-500">
                <i class="ri-receipt-line text-4xl mb-4 inline-block"></i>
                <p>Receipts management to be implemented</p>
            </div>
        </div>

        {{-- Bonds Tab --}}
        <div id="bonds-tab" class="doctor-tab-content">
            <div class="text-center py-12 text-gray-500">
                <i class="ri-file-text-line text-4xl mb-4 inline-block"></i>
                <p>Bonds management to be implemented</p>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.doctor-tab-content').forEach(el => {
        el.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.doctor-tab-btn').forEach(el => {
        el.classList.remove('active');
    });
    
    // Show selected tab
    const tabEl = document.getElementById(tabName + '-tab');
    if (tabEl) {
        tabEl.classList.add('active');
    }
    
    // Add active to clicked button
    event.target.classList.add('active');
}

function sendMail(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    
    fetch(`/admin/doctors/${doctorId}/send-mail`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => alert(data.message))
    .catch(err => alert('Error: ' + err.message));
}

function sendSms(doctorId, phone) {
    if (!phone) {
        alert('No phone number on file for this doctor.');
        return;
    }
    
    fetch(`/admin/doctors/${doctorId}/send-sms`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => alert(data.message))
    .catch(err => alert('Error: ' + err.message));
}

function resendBond(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    
    fetch(`/admin/doctors/${doctorId}/resend-bond`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => alert(data.message))
    .catch(err => alert('Error: ' + err.message));
}

function resendReceipt(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    
    fetch(`/admin/doctors/${doctorId}/resend-receipt`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => alert(data.message))
    .catch(err => alert('Error: ' + err.message));
}
</script>
@endsection
