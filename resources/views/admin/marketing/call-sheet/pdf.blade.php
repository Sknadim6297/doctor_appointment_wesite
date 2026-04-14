<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Call Sheet</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 24px;
        }
        .header {
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid #1d4ed8;
        }
        .brand {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px 0;
        }
        .sub {
            color: #475569;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            vertical-align: top;
            text-align: left;
        }
        th {
            background: #eff6ff;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .meta {
            margin-top: 18px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .meta div {
            border: 1px solid #e2e8f0;
            padding: 10px;
            border-radius: 8px;
        }
        .label {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 4px;
        }
        .value {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <p class="brand">MediForum Admin</p>
        <p class="sub">Marketing Call Sheet</p>
    </div>

    <table>
        <tr>
            <th style="width: 180px;">Field</th>
            <th>Value</th>
        </tr>
        <tr><td>Doctor Name</td><td>{{ $callSheet->doctor_name ?? 'N/A' }}</td></tr>
        <tr>
            <td>Specialization</td>
            <td>
                @php
                    $specMap = \App\Models\Specialization::query()->pluck('name', 'id');
                    $specIds = collect($callSheet->call_sheet_specialization_ids ?? [])->map(fn($id) => (int) $id);
                    $specNames = $specIds->map(fn($id) => $specMap[$id] ?? null)->filter()->values();
                @endphp
                {{ $specNames->isNotEmpty() ? $specNames->implode(', ') : ($callSheet->specialization?->name ?? 'N/A') }}
            </td>
        </tr>
        <tr><td>Email</td><td>{{ $callSheet->doctor_email ?? 'N/A' }}</td></tr>
        <tr><td>Phone</td><td>{{ $callSheet->mobile1 ?? 'N/A' }}</td></tr>
        <tr><td>Membership No.</td><td>{{ $callSheet->customer_id_no ?? 'N/A' }}</td></tr>
        <tr><td>Created At</td><td>{{ optional($callSheet->created_at)->format('d/m/Y') ?? 'N/A' }}</td></tr>
        <tr><td>Address</td><td>{{ $callSheet->doctor_address ?? 'N/A' }}</td></tr>
        <tr><td>Clinic Address</td><td>{{ $callSheet->clinic_address ?? 'N/A' }}</td></tr>
    </table>

    <div class="meta">
        <div>
            <div class="label">Note</div>
            <div class="value">Generated from the current marketing call sheet record.</div>
        </div>
        <div>
            <div class="label">Doctor ID</div>
            <div class="value">{{ $callSheet->id }}</div>
        </div>
    </div>
</body>
</html>
