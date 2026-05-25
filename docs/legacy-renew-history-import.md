# Legacy `tbl_renew_history` import

Import renewal rows from the old Mediforum database into this admin app.

## Plan mapping (`renew_plan_id`)

| Legacy `renew_plan_id` | App plan | Label |
|------------------------|----------|--------|
| 1 | `normal` | Normal |
| 2 | `high_risk` | High Risk |
| 3 | `combo` | Combo |

## Tables involved

| Table | Role |
|-------|------|
| `legacy_tbl_renew_history` | Staging copy of the SQL dump (all historical rows) |
| `renewal_histories` | One row per legacy renewal (audit trail) |
| `enrollments` | Matched by `legacy_user_id` = `renew_doctor_id` |
| `policy_receipts` | Latest policy number / last renewed date per enrollment |

## Latest renewal per doctor

When you run `--link --apply`, enrollment and policy fields are taken from the **newest** row per `renew_doctor_id` (by `renewed_date`, then `id`), not the last row in the SQL file. This matches production behaviour when the dump is not strictly chronological.

## Commands

```bash
php artisan migrate

# Full dump (adjust path)
php artisan legacy:import-renewal-history --file=C:\path\to\legacy_renew_history.sql --link --apply --truncate-histories

# Dry run (no writes to enrollments / policies)
php artisan legacy:import-renewal-history --file=C:\path\to\legacy_renew_history.sql --dry-run

# Staging + renewal_histories only (no enrollment updates)
php artisan legacy:import-renewal-history --file=C:\path\to\legacy_renew_history.sql --link
```

## Flags

| Flag | Effect |
|------|--------|
| `--link` | Import staging → `renewal_histories` |
| `--apply` | Update enrollments + policy receipts from **latest** renewal per doctor |
| `--truncate-histories` | Clear `renewal_histories` before import |
| `--dry-run` | Parse only; no DB writes |
| `--chunk=500` | Rows per batch |

## After import

- **Renewal history** admin screens read `renewal_histories`.
- **Enrollment** list/detail show `last_renewal_date`, `renewal_date`, plan, coverage from the latest legacy renewal.
- **Policy receipts** get `policy_no` / `last_renewed_date` when a receipt exists or when a policy number is present on the latest renewal.

Re-run import safely on the same dump with `--truncate-histories` if you need a clean `renewal_histories` table.
