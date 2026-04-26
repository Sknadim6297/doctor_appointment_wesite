<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Slip</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #111; }
        .card { border: 1px solid #ccc; border-radius: 8px; padding: 16px; }
        h2 { margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #f1f5f9; }
        .right { text-align: right; }
        .actions { margin-bottom: 16px; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print();">Print</button>
    </div>

    <div class="card">
        <h2>Salary Slip</h2>
        <p><b>Employee:</b> {{ $salary->employee?->name ?? 'N/A' }}</p>
        <p><b>Employee no:</b> {{ $salary->employee?->employee_no ?? 'N/A' }}</p>
        <p><b>Month/Year:</b> {{ $salary->salary_month }} / {{ $salary->salary_year }}</p>

        <table>
            <tr><th>Description</th><th class="right">Amount (Rs)</th></tr>
            <tr><td>Monthly salary</td><td class="right">{{ number_format((float) $salary->monthly_salary, 2) }}</td></tr>
            <tr><td>Incentive</td><td class="right">{{ number_format((float) $salary->incentive, 2) }}</td></tr>
            <tr><td>Office duty</td><td class="right">{{ number_format((float) $salary->office_duty, 2) }}</td></tr>
            <tr><td>Bonus</td><td class="right">{{ number_format((float) $salary->bonus, 2) }}</td></tr>
            <tr><td>Advance</td><td class="right">-{{ number_format((float) $salary->advance, 2) }}</td></tr>
            <tr><td>Additional deduct</td><td class="right">-{{ number_format((float) $salary->additional_deduct, 2) }}</td></tr>
            <tr><td>PF</td><td class="right">-{{ number_format((float) $salary->pf, 2) }}</td></tr>
            <tr><td>ESI</td><td class="right">-{{ number_format((float) $salary->esi, 2) }}</td></tr>
            <tr><td>P-Tax</td><td class="right">-{{ number_format((float) $salary->ptax, 2) }}</td></tr>
            <tr><th>Net salary</th><th class="right">{{ number_format((float) $salary->net_salary, 2) }}</th></tr>
        </table>

        <p style="margin-top: 12px;"><b>Cheque no:</b> {{ $salary->cheque_no ?: 'N/A' }}</p>
        <p><b>Bank name:</b> {{ $salary->bank_name ?: 'N/A' }}</p>
    </div>
</body>
</html>
