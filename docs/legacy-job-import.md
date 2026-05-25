# Legacy job applications import (`tbl_job`)

Career/job applications from the old site map to `job_applications`.

| Legacy | App |
|--------|-----|
| `tbl_job.id` | `job_applications.id` |
| `name` | `name` |
| `created_on` | `applied_at` |

## Import

```bash
php artisan migrate
php artisan legacy:import-jobs --replace
```

SQL dump path: `storage/app/legacy_tbl_job.sql`

UI: **Employee Management → Job Applications**
