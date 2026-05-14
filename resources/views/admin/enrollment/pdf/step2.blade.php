<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 20px 24px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.45;
            margin: 0;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .cover-top {
            margin-top: 40px;
            text-align: center;
        }

        .brand {
            font-family: Arial Black, Arial, sans-serif;
            font-size: 17px;
            font-weight: 700;
        }

        .sub-brand {
            font-size: 15px;
        }

        .membership-box {
            margin: 18px auto 0;
            width: 35%;
            border: 1px solid #000;
            padding: 8px 12px;
            text-align: center;
            font-size: 12px;
        }

        .section-title {
            font-family: Arial Black, Arial, sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 10px;
        }

        .main-table td,
        .main-table th,
        .receipt-table td,
        .receipt-table th,
        .membership-table td,
        .membership-table th {
            border: 1px solid #000;
            padding: 6px 7px;
            vertical-align: top;
        }

        .membership-table {
            border: 4px solid #000;
        }

        .membership-table td {
            border: 4px solid #000;
        }

        .terms {
            font-size: 10px;
            line-height: 1.5;
        }

        .receipt-head {
            text-align: center;
            margin: 36px 0 14px;
        }

        .muted {
            color: #374151;
        }

        .page-break {
            page-break-before: always;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>
<body>
@php
    $doctorAddress = trim((string) $doctor_address);
    $doctorAddressLine = wordwrap($doctorAddress, 25, "<br>", true) . ',' . $state . ',' . "<br>" . $city . ',' . $postcode;
    $coverageAmount = ($plan_id === 3) ? 'AS PER <br>INSURANCE T/C' : ('INR' . $coverage_id . '00000');
    $maxCompensation = $plan_id === 3 ? ($coverage_id . '00000') : '';
    $paymentMethodLabel = $payment_method === '1' ? 'CHEQUE' : 'CASH';
@endphp

<div class="page">
    <table>
        <tr>
            <td style="border:none; text-align:center;">
                <div class="cover-top">
                    <div class="brand">MEDICAL DEFENCE FORUM (MEDEFORUM)</div>
                    <div class="sub-brand">MEDICAL DEFENCE FORUM OF INDIA,<br>1 S.P MUKHERJEE ROAD<br>KOLKATA-700028 WEST BENGAL<br>PHONE: (33) 60503303 FAX: 60503303<br>EMAIL: info@medeforum.com</div>
                    <div style="font-family: Arial Black, Arial, sans-serif; font-weight: bold; font-size: 15px; margin-top: 8px;">
                        PROFESSIONAL INDEMNITY MEMBERSHIP<br>
                        MEMBERSHIP<br>NO.: {{ $customer_id }}
                    </div>
                    <div class="membership-box">
                        PERIOD OF MEMBERSHIP<br>
                        From {{ $enrollment_date->format('H:i') }} hrs of {{ $enrollment_date->format('d/m/Y') }}<br>
                        to midnight of {{ $renewal_date->format('d/m/Y') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align:center; margin-top: 26px;">
        <div style="font-size:12px; font-family:'Cambria (Body)';"><i>Member</i></div>
        <div style="font-family: Arial Black, Arial, sans-serif; font-size: 18px; font-weight: 700; margin-top: 4px;">{{ $doctor_name }}</div>
        <div style="font-family: Arial, Helvetica, sans-serif; font-size: 18px; margin-top: 2px;">Contact No: {{ $doctor_mobile_no }}</div>
        <div style="font-family: Arial, Helvetica, sans-serif; font-size: 18px; margin-top: 2px;">{!! $doctorAddressLine !!}</div>
    </div>

    <div style="margin-top: 18px; padding-left: 25%;">
        <div style="font-family:'Cambria (Body)'; font-size: 11px;">
            Agent Name: {{ $agent_name }}<br>
            Agent Code:<br>
            Mobile/Landline:<br>
            Number/E mail: {{ $agent_phone_no }}
        </div>
    </div>

    <div style="margin-top: 42px; padding-left: 20%; font-size: 12px; font-family:'Cambria (Body)';">
        {{ url('/') }}.....{{ $generated_date->format('d/m/Y') }}
    </div>
</div>

<div class="page">
    <div class="section-title">LEGAL SERVICE MEMBERSHIP SCHEDULE</div>
    <table class="membership-table terms">
        <tr>
            <td>Membership Number</td>
            <td>{{ $customer_id }}</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>Membership Details</td>
            <td>Name</td>
            <td colspan="2">{{ $doctor_name }}</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>Tel No.</td>
            <td>{{ $doctor_mobile_no }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>Legal service</td>
            <td>{!! $coverageAmount !!}</td>
            <td>SCHEME</td>
            <td>{{ $plan_name }} PLAN</td>
        </tr>
        <tr>
            <td>Period of membership</td>
            <td>From <br>({{ $membership_period }})</td>
            <td>{{ $enrollment_date->format('H:i') }} hrs of <br> {{ $enrollment_date->format('d/m/Y') }}</td>
            <td>to midnight of {{ $renewal_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Limit of legal Service</td>
            <td>{!! $coverageAmount !!}</td>
            <td>Max Limit of Compensation</td>
            <td>{{ $maxCompensation }}</td>
        </tr>
        <tr>
            <td>STATE</td>
            <td>{{ $state }}</td>
            <td>PINCODE</td>
            <td>{{ $postcode }}</td>
        </tr>
        <tr>
            <td>Registration No.</td>
            <td>{{ $medical_reg_no }}</td>
            <td>Registration year</td>
            <td>{{ $year_of_reg }}</td>
        </tr>
        <tr>
            <td>District Forum</td>
            <td>COV</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>State Forum</td>
            <td>COV</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>National Forum</td>
            <td>COV</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>Line of specialisation</td>
            <td>{{ $speciliazition_name }}</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>Qualification</td>
            <td>{{ strtoupper($doctor_qualification) }}</td>
            <td>Qualification year</td>
            <td>{{ $doctor_qualification_year }}</td>
        </tr>
    </table>

    <table style="margin-top: 22px; width: 100%;">
        <tr>
            <td style="border:none; width: 58%;"></td>
            <td style="border:none; width: 42%; vertical-align: top;">
                <div class="terms">
                    <span style="font-size:11px; font-family:'Calibri (Body)';">Respected Member</span><br>
                    Thank you for being our member of MEDEFORUM LEGAL SERVICES. We are offering you the following service under
                    <br><br>
                    Terms and condition applied by Medeforum. As you are a member of our organization, your profession is under legal service agreement with us.
                    <br><br>
                    a) Legal defence cost paid by Medeforum as per your membership bond.<br>
                    b) Medeforum legal service include district, state, consumer forum.<br>
                    c) Medeforum legal service include civil court, human right commission, criminal court and medical council of India & Optometries<br><br>
                    TERMS AND CONDITION:<br>
                    1) HOW TO SUBMIT A MEDICO LEGAL CLAIM WITH MEDEFORUM:<br>
                    a) Original membership certificate for the period of treatment for which patient made a complaint against you.<br>
                    b) Letter to Medeforum describing the entire treatment done by the Member for which complain made against you.<br>
                    c) Vakaulatnama sign by the Member for authorising the lawyer to defend the case in the court.<br>
                    d) Any previous case arising before the membership period is not under the service.<br>
                    Membership period means the period commencing from the effective date and hour as shown in the membership schedule and terminating at midnight on expiry date as shown on your membership schedule.<br>
                    The forum does not assume any financial liability.<br><br>
                    2) Forum does not cover any service under the circumstances given below:-<br>
                    b) Service rendered while influence of intoxicants or narcotics.<br>
                    c) Third party public liability.<br>
                    d) Claims made against the member arising from cosmetic plastic surgery, hair transplant or any beautification.<br>
                    e) Claim arising from conditions related to HTLVIII / AIDS and similar conditions.<br>
                    f) Assume by the membership by agreement and which would not have attached in the absence of such agreement.<br>
                    g) Arising out of deliberate, wilful or intentional non compliance of any statutory provision.<br>
                    h) Arising out of personal injuries such as libel, slander, wrongful arrest, wrongful detention, defamation etc. and mental injury, anguish or shock.<br>
                    i) Arising out of fines, penalties, punitive or exemplary damages.
                </div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 42px;">
        <tr>
            <td style="border:none;">
                <span style="font-size:11px; font-family:'Calibri (Body)';">For medical defence forum of India</span><br>
                <b><span style="font-size:13px; font-family:'Calibri (Body)';">Authorised signatory</span></b><br>
                <span style="font-size:11px; font-family:'Calibri (Body)';">{{ $generated_date->format('d/m/Y') }} by Medeforum Kolkata division<br>as per the terms and condition of medical defence forum of India</span>
            </td>
        </tr>
    </table>
</div>

<div class="page page-break">
    <div class="receipt-head">
        <h3 style="margin:0; font-family: Arial Black, Arial, sans-serif; font-size: 19px;">
            MEDICAL DEFENCE FORUM (MEDEFORUM)<br>
            MEDEFORUM MONEY RECEIPT
        </h3>
    </div>

    <table class="receipt-table" style="font-size: 13px; width: 100%;">
        <tr>
            <td rowspan="2" style="vertical-align: text-top; width: 22%;"><b>Issuing Office<br>code/Address :</b></td>
            <td rowspan="2" style="width: 40%;">MEDEFORUM<br>MEDICAL DEFENCE FORUM OF INDIA,<br>1 S.P MUKHERJEE ROAD KOLKATA-700028 WEST BENGAL</td>
            <td style="width: 16%;">Receipt<br>Number :</td>
            <td>{{ $recipet_no }}</td>
        </tr>
        <tr>
            <td>Collection <br>Date :</td>
            <td>
                @if($payment_method === '1')
                    {{ \Carbon\Carbon::parse($cheque_rec_date)->format('d/m/Y') }}
                @else
                    {{ \Carbon\Carbon::parse($cash_rec_date)->format('d/m/Y') }}
                @endif
            </td>
        </tr>
    </table>

    <p style="padding-left:15%; width: 80%; font-size:14px; font-family:'Calibri (Body)';">
        Received with thanks from {{ $doctor_name }} (Customer ID : {{ $customer_id }}) a
        sum of<br> Rs.{{ number_format($amount, 2) }} ({{ strtoupper($amount_words) }}) as per detail given here under
    </p>

    <table class="main-table" style="font-size: 13px; width: 100%;">
        <tr>
            <th>SL<br>NO</th>
            <th>Membership Number</th>
            <th>Membership Type</th>
            <th>Particulars</th>
            <th>Total Amount</th>
        </tr>
        <tr>
            <td style="text-align:center;">1.</td>
            <td>{{ $customer_id }}</td>
            <td>PROFESSIONAL INDEMNITY<br>{{ strtoupper($plan_name) }} PLAN</td>
            <td>TOTAL SUBSCRIPTION FOR<br>{{ strtoupper($payment_mode_label) }}</td>
            <td>{{ number_format($amount, 2) }}</td>
        </tr>
        <tr>
            <td style="text-align:center;">2.</td>
            <td>{{ $customer_id }}</td>
            <td>LEGAL SERVICE<br>{{ strtoupper($plan_name) }} PLAN</td>
            <td>{!! $plan_id === 3 ? 'AS PER <br>INSURANCE T/C' : 'TOTAL SUBSCRIPTION<br>' . $coverage_id . '00000' !!}</td>
            <td>0.00</td>
        </tr>
    </table>

    <table style="padding-left: 14%; font-size:14px; font-family:'Calibri (Body)'; margin-top: 12px;">
        <tr><td>Total (Rounded Off)</td><td>:</td><td>{{ number_format($amount, 2) }}</td></tr>
        <tr><td>Stamp Duty:</td><td>:</td><td>0.00</td></tr>
        <tr><td>Bank Charges:</td><td>:</td><td>0.00</td></tr>
        <tr><td>Total Amount:</td><td>:</td><td>{{ number_format($amount, 2) }}</td></tr>
    </table>

    <table class="main-table" style="font-size: 13px; width: 100%; margin-top: 14px;">
        @if($payment_method === '1')
            <tr><td colspan="8"><b>Instrument Detail</b></td></tr>
            <tr>
                <th>SL<br>NO</th>
                <th>Payment ID</th>
                <th>Mode of<br>Payment</th>
                <th>Instrument<br>Number</th>
                <th>Instrument<br>Date</th>
                <th>Bank<br>Name</th>
                <th>Branch<br>Name</th>
                <th>Tagged<br>Amount</th>
            </tr>
            <tr>
                <td style="text-align:center;">1.</td>
                <td>{{ $recipet_no }}</td>
                <td>{{ $payment_method_label }}</td>
                <td>{{ $cheque_no }}</td>
                <td>{{ \Carbon\Carbon::parse($cheque_rec_date)->format('d/m/Y') }}</td>
                <td>{{ $bank_name }}</td>
                <td>{{ $branch_name }}</td>
                <td>{{ number_format($amount, 2) }}</td>
            </tr>
        @else
            <tr><td colspan="5"><b>Cash Detail</b></td></tr>
            <tr>
                <th>SL<br>NO</th>
                <th>Payment ID</th>
                <th>Mode of<br>Payment</th>
                <th>Cash<br>Date</th>
                <th>Total<br>Amount</th>
            </tr>
            <tr>
                <td style="text-align:center;">1.</td>
                <td>{{ $recipet_no }}</td>
                <td>{{ $payment_method_label }}</td>
                <td>{{ \Carbon\Carbon::parse($cash_rec_date)->format('d/m/Y') }}</td>
                <td>{{ number_format($amount, 2) }}</td>
            </tr>
        @endif
    </table>

    <table style="margin-top: 22px; font-size:13px; font-family:'Calibri (Body)';">
        <tr><td>For MEDEFORUM</td></tr>
        <tr><td>MEDICAL DEFENCE FORUM</td></tr>
        <tr><td>Authorised Signatory</td></tr>
        <tr><td>{{ $generated_date->format('d/m/Y') }} by Medeforum Legal division</td></tr>
    </table>

    <table style="margin-left: 50%; font-size:13px; font-family:'Calibri (Body)'; margin-top: -76px; width: 45%;">
        <tr><td>as per the terms and condition of medical defence forum of India</td></tr>
        <tr><td>Cashier Initial</td></tr>
        <tr>
            <td>
                Note:<br>
                1. Receipt valid subject to realisation of cheque<br>
                2. Please quote membership no., collection date.<br>
                And date in all correspondences.
            </td>
        </tr>
    </table>
</div>
</body>
</html>