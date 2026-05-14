<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\DoctorDocument;
use App\Models\LegalCase;
use App\Models\PolicyReceipt;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Query Optimization Patterns Service
 * 
 * Provides common query patterns that are already optimized
 * to avoid N+1 issues and unnecessary queries.
 */
class QueryOptimizationPatternsService
{
    /**
     * Get enrollments with all related data efficiently
     * Loads: specialization, creator, approver
     * 
     * @param array $filters Optional filters
     * @return Builder
     */
    public static function getEnrollmentsWithRelations(array $filters = []): Builder
    {
        $query = Enrollment::query()
            ->with(['specialization', 'creator', 'approver'])
            ->select([
                'id', 'doctor_name', 'mobile1', 'specialization_id',
                'plan', 'status', 'created_by', 'approved_by', 'created_at'
            ]);

        return self::applyFilters($query, $filters);
    }

    /**
     * Get enrollment counts grouped by status
     * Single query instead of multiple COUNT queries
     * 
     * @return Collection
     */
    public static function getEnrollmentCountsByStatus(): Collection
    {
        return DB::table('enrollments')
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->keyBy('status');
    }

    /**
     * Get enrollment statistics (totals, averages, ranges)
     * Optimized with raw SQL aggregation
     * 
     * @return array
     */
    public static function getEnrollmentStatistics(): array
    {
        $stats = DB::table('enrollments')
            ->selectRaw('
                COUNT(*) as total,
                COUNT(DISTINCT specialization_id) as specialization_count,
                COUNT(DISTINCT plan) as plan_types,
                SUM(payment_amount) as total_revenue,
                AVG(payment_amount) as avg_amount,
                MIN(created_at) as oldest_enrollment,
                MAX(created_at) as newest_enrollment
            ')
            ->first();

        return (array) $stats;
    }

    /**
     * Get renewals due in date range
     * Efficient filtering with index usage
     * 
     * @param string $fromDate
     * @param string $toDate
     * @return Builder
     */
    public static function getRenewalsDue(string $fromDate, string $toDate): Builder
    {
        return Enrollment::query()
            ->with(['specialization', 'creator'])
            ->whereRaw('DATE_ADD(created_at, INTERVAL 1 YEAR) BETWEEN ? AND ?', [
                $fromDate, $toDate
            ])
            ->select(['id', 'doctor_name', 'specialization_id', 'created_by', 'created_at']);
    }

    /**
     * Get enrollments by creator (agent/employee)
     * Useful for per-agent dashboards
     * 
     * @param int $userId Creator user ID
     * @return Builder
     */
    public static function getEnrollmentsByCreator(int $userId): Builder
    {
        return Enrollment::query()
            ->where('created_by', $userId)
            ->with(['specialization'])
            ->orderByDesc('created_at');
    }

    /**
     * Get doctor documents with minimal queries
     * Eager loads enrollment relation
     * 
     * @param int $enrollmentId Optional enrollment filter
     * @return Builder
     */
    public static function getDoctorDocuments(int $enrollmentId = null): Builder
    {
        $query = DoctorDocument::query()
            ->with(['enrollment.specialization']);

        if ($enrollmentId) {
            $query->where('enrollment_id', $enrollmentId);
        }

        return $query;
    }

    /**
     * Get legal cases with enrollment context
     * Combines two important relations
     * 
     * @return Builder
     */
    public static function getLegalCasesWithEnrollment(): Builder
    {
        return LegalCase::query()
            ->with([
                'enrollment:id,doctor_name,specialization_id',
                'enrollment.specialization:id,name'
            ])
            ->select(['id', 'enrollment_id', 'case_status', 'created_at'])
            ->orderByDesc('created_at');
    }

    /**
     * Get policy receipts for enrollments
     * Efficiently loads related data
     * 
     * @return Builder
     */
    public static function getPolicyReceiptsWithDetails(): Builder
    {
        return PolicyReceipt::query()
            ->with([
                'enrollment:id,doctor_name,money_rc_no,payment_amount',
                'enrollment.specialization:id,name'
            ])
            ->select(['id', 'enrollment_id', 'receive_date', 'created_at'])
            ->orderByDesc('receive_date');
    }

    /**
     * Get admin users with all related data
     * Includes roles, privileges, activity counts
     * 
     * @return Builder
     */
    public static function getAdminUsersWithRelations(): Builder
    {
        return User::query()
            ->with([
                'roles:id,role_key,role_title',
                'privileges:id,user_id,page_key,is_allowed'
            ])
            ->withCount([
                'loginLogs',
                'privileges as allowed_privileges_count' => function ($q) {
                    $q->where('is_allowed', true);
                },
                'privileges as denied_privileges_count' => function ($q) {
                    $q->where('is_allowed', false);
                }
            ])
            ->select(['id', 'name', 'email', 'created_at']);
    }

    /**
     * Get monthly enrollment trends
     * Single aggregated query for performance
     * 
     * @param int $limit Number of months
     * @return Collection
     */
    public static function getMonthlyEnrollmentTrends(int $limit = 12): Collection
    {
        return DB::table('enrollments')
            ->selectRaw('
                DATE_TRUNC(created_at, MONTH) as month,
                COUNT(*) as count,
                SUM(payment_amount) as revenue
            ')
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit($limit)
            ->get();
    }

    /**
     * Get plan distribution stats
     * Single query instead of multiple COUNT queries
     * 
     * @return Collection
     */
    public static function getPlanDistribution(): Collection
    {
        $planNames = [1 => 'Normal', 2 => 'High Risk', 3 => 'Combo'];

        $stats = DB::table('enrollments')
            ->selectRaw('plan, COUNT(*) as count, SUM(payment_amount) as revenue')
            ->groupBy('plan')
            ->get()
            ->mapWithKeys(function ($row) use ($planNames) {
                return [
                    $planNames[$row->plan] ?? 'Unknown' => (array) $row
                ];
            });

        return $stats;
    }

    /**
     * Get expense report by category
     * Optimized aggregation
     * 
     * @param string $month Optional month filter (YYYY-MM)
     * @return Collection
     */
    public static function getExpenseReport(string $month = null): Collection
    {
        $query = DB::table('expenses')
            ->join(
                'expense_categories',
                'expenses.expense_category_id',
                '=',
                'expense_categories.id'
            )
            ->selectRaw('
                expense_categories.category_name,
                COUNT(*) as count,
                SUM(expenses.amount) as total,
                AVG(expenses.amount) as average
            ')
            ->groupBy('expense_categories.category_name');

        if ($month) {
            $query->whereRaw('DATE_FORMAT(expenses.created_at, "%Y-%m") = ?', [$month]);
        }

        return $query->get();
    }

    /**
     * Generic filter application helper
     * Supports common filter types
     * 
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    private static function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }

            if (str_contains($key, '_min')) {
                $column = str_replace('_min', '', $key);
                $query->where($column, '>=', $value);
            } elseif (str_contains($key, '_max')) {
                $column = str_replace('_max', '', $key);
                $query->where($column, '<=', $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * Get slow query log entries (if enabled)
     * Monitor performance issues
     * 
     * @return Collection
     */
    public static function getSlowQueryLog(): Collection
    {
        // Requires MySQL slow query log enabled
        // SET GLOBAL slow_query_log = 'ON';
        // SET GLOBAL long_query_time = 1;

        try {
            return collect(DB::select("
                SELECT * FROM mysql.slow_log 
                WHERE db = DATABASE()
                ORDER BY query_time DESC
                LIMIT 50
            "));
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Get table size and index information
     * Useful for monitoring database growth
     * 
     * @param string $table Table name
     * @return array
     */
    public static function getTableInfo(string $table): array
    {
        $stats = DB::select("SELECT 
            table_name,
            round(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
            table_rows as row_count
        FROM information_schema.TABLES 
        WHERE table_schema = DATABASE() 
        AND table_name = ?", [$table]);

        return $stats[0] ?? [];
    }

    /**
     * Get all indexes for a table
     * Audit index usage
     * 
     * @param string $table
     * @return Collection
     */
    public static function getTableIndexes(string $table): Collection
    {
        return collect(DB::select("SHOW INDEXES FROM {$table}"));
    }
}
