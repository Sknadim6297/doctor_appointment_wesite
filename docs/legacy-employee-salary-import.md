# Legacy employee salary import (`tbl_employee_salary`)

**Manage Salary** uses `salary_records` linked to staff via `users.legacy_user_id`.

| Legacy column | App column |
|---------------|------------|
| `salary_id` | `salary_records.id` |
| `employee_id` | `salary_records.user_id` (via `users.legacy_user_id`) |
| `month` / `year` | `salary_month` / `salary_year` |
| `intensive` | `incentive` |
| `intensive_for` | `incentive_for` |
| `additional_deduction` | `additional_deduct` |
| `total_salary` | `net_salary` |
| `checque_no` | `cheque_no` |

Duplicate legacy rows for the same employee + month + year keep only the row with the highest `salary_id`.

## SQL dump

Place the phpMyAdmin export at:

`storage/app/legacy_tbl_employee_salary.sql`

Re-extract from a chat transcript:

```bash
php scripts/extract_employee_salary_sql.php
```

## Import

```bash
php artisan migrate
php artisan legacy:import-users   # or legacy staff import — users need legacy_user_id
php artisan legacy:import-employee-salary --replace
```

Options: `--sync-only`, `--dry-run`, `--no-truncate`, `--replace`.

Rows are skipped when `employee_id` has no matching `users.legacy_user_id`.
