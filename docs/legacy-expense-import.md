# Legacy expense import (`tbl_expensive_category`, `tbl_expensive`)

Account Management screens **Manage Expense Category** and **Manage Expense** use:

| Legacy table | Laravel table | ID column preserved |
|--------------|---------------|---------------------|
| `tbl_expensive_category` | `expense_categories` | `expensive_cat_id` → `id` |
| `tbl_expensive` | `expenses` | `expense_id` → `id` |

`tbl_expensive.expense_cat_id` maps to `expenses.expense_category_id`. Rows with unknown categories or invalid dates/amounts are skipped.

## SQL dumps

Place phpMyAdmin dumps at:

- `storage/app/legacy_tbl_expensive_category.sql`
- `storage/app/legacy_tbl_expensive.sql`

The expense dump must be the **full** export (~3000+ rows). A truncated file (e.g. only the first `INSERT` chunk) will import far fewer records. To re-extract from a chat transcript:

```bash
php scripts/extract_expense_sql.php
```

## Import

```bash
php artisan migrate
php artisan legacy:import-expenses --replace
```

Options:

- `--only=categories` or `--only=expenses`
- `--sync-only` — sync from staging tables only
- `--dry-run` — preview counts
- `--no-truncate` — append to staging instead of truncating before load

`customer_name` often contains doctor names from the legacy app; there is no `doctor_id` on the legacy expense table.
