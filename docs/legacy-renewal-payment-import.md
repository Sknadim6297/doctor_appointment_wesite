# Legacy renewal payment import

Imports rows from phpMyAdmin dump table `tbl_renewal_payment` into:

- `renewal_histories` (latest row per doctor by payment date)
- `enrollments` (`last_renewal_date`, `next_renewal_date`, `renewal_date`)
- `policy_receipts` (`policy_no`, `last_renewed_date`, `renewal_plan`, `plan_amount` when column exists)
- `enrollments` (`plan` 1=Normal / 2=HighRisk / 3=Combo, `policy_no`, `payment_amount`, `last_renewal_date`, `renewal_date`)

## Prerequisites

1. Run migration:

```bash
php artisan migrate
```

2. Place full SQL dump at `storage/app/legacy_tbl_renewal_payment.sql` (or pass `--file=`).

3. Enrollments must have `legacy_user_id` matching `doctor_id` in the dump.

## Commands

```bash
# Dry run (no DB writes)
php artisan legacy:import-renewal-payment --dry-run

# Load SQL into staging table (truncates staging first)
php artisan legacy:import-renewal-payment --file=storage/app/legacy_tbl_renewal_payment.sql

# Sync to live tables
php artisan legacy:import-renewal-payment

# Keep every payment row as renewal history (not only latest per doctor)
php artisan legacy:import-renewal-payment --keep-histories
```

## Options

| Option | Description |
|--------|-------------|
| `--file=` | Path to SQL file (default: `storage/app/legacy_tbl_renewal_payment.sql`) |
| `--dry-run` | Parse and count only |
| `--keep-histories` | Import all rows per doctor (default: one latest per doctor) |
| `--truncate` | Truncate `legacy_tbl_renewal_payment` before load |

## Plan mapping

`plan_id` in dump (0–3) maps to `renewal_histories.plan_type`:

| plan_id | plan_type |
|---------|-----------|
| 0 | insurance |
| 1 | combo |
| 2 | yearly_plan |
| 3 | two_year |

`payment_mode` text (e.g. `One Year`, `yearly_plan`) is normalized to plan type when possible.

## Invalid dates

Rows with unparseable `payment_date` (e.g. `1908-08-10`, empty) are skipped; errors are listed per doctor in the command output.

## Related

- `php artisan legacy:import-renewal-history` — older `tbl_renew_history` import (same pattern)
- Manual receipt UI: `POST admin/receipts/legacy-update` (`DoctorController::receiptsLegacyUpdate`)
