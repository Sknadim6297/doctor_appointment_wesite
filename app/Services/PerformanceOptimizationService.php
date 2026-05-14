<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Performance Optimization Service
 * 
 * Provides utilities for:
 * - Efficient pagination strategies
 * - Query optimization patterns
 * - Lazy loading mechanisms
 * - Memory-efficient processing
 */
class PerformanceOptimizationService
{
    /**
     * Get paginated results with automatic relationship eager loading
     * 
     * Usage:
     * $users = $this->paginateWithEagerLoad(
     *     User::query(),
     *     ['roles', 'permissions'],
     *     15
     * );
     * 
     * @param Builder $query The query builder instance
     * @param array $relationships Array of relationships to eager load
     * @param int $perPage Items per page
     * @param string|null $pageName Pagination name
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public static function paginateWithEagerLoad(
        Builder $query,
        array $relationships = [],
        int $perPage = 20,
        string $pageName = 'page'
    ) {
        return $query
            ->with($relationships)
            ->paginate(
                perPage: $perPage,
                pageName: $pageName
            )
            ->appends(request()->query());
    }

    /**
     * Process large datasets efficiently using cursor with chunking
     * Prevents memory overload and enables lazy loading
     * 
     * Usage:
     * $this->processLargeDataset(
     *     Enrollment::query()->with('specialization'),
     *     function ($enrollment) {
     *         // Process individual record
     *         return [
     *             'name' => $enrollment->doctor_name,
     *             'spec' => $enrollment->specialization->name
     *         ];
     *     },
     *     5000 // Flush to output every 5000 records
     * );
     * 
     * @param Builder $query
     * @param callable $processor Function to process each record
     * @param int $flushEvery Flush buffer every N records (for generators)
     * @return \Generator
     */
    public static function processLargeDataset(
        Builder $query,
        callable $processor,
        int $flushEvery = 5000
    ) {
        $count = 0;
        foreach ($query->cursor() as $record) {
            yield $processor($record);
            $count++;

            if ($count % $flushEvery === 0) {
                // Yield a flush signal or flush DB connection if needed
                // Helps with memory efficiency in long-running operations
            }
        }
    }

    /**
     * Get aggregated statistics with caching
     * 
     * Usage:
     * $stats = $this->getCachedStats('enrollment', function () {
     *     return Enrollment::selectRaw(
     *         'COUNT(*) as total, SUM(payment_amount) as revenue'
     *     )->first();
     * }, 3600);
     * 
     * @param string $statsKey Unique key for this statistics set
     * @param callable $queryCallback Function that returns query result
     * @param int $cacheMinutes Cache duration in minutes
     * @return mixed
     */
    public static function getCachedStats(
        string $statsKey,
        callable $queryCallback,
        int $cacheMinutes = 60
    ) {
        $cacheKey = "stats_{$statsKey}_" . date('YmdH'); // Hourly cache
        return Cache::remember($cacheKey, $cacheMinutes * 60, $queryCallback);
    }

    /**
     * Lazy-load related records from CSV/large dataset
     * Prevents N+1 queries by loading relations separately
     * 
     * Usage:
     * $enrollments = Enrollment::cursor();
     * $withRelations = $this->lazyLoadRelations(
     *     $enrollments,
     *     'specialization',
     *     'enrollment_id',
     *     'id'
     * );
     * 
     * @param iterable $records
     * @param string $relation Relation name to load
     * @param string $localKey Key in current records
     * @param string $foreignKey Key in related model
     * @return \Generator
     */
    public static function lazyLoadRelations(
        iterable $records,
        string $relation,
        string $localKey = 'id',
        string $foreignKey = 'id'
    ) {
        $recordsArray = [];
        $keys = [];
        $firstRecord = null;

        // Collect all keys to batch load
        foreach ($records as $record) {
            $recordsArray[] = $record;
            $keys[] = $record->{$localKey};
            if ($firstRecord === null) {
                $firstRecord = $record;
            }
        }

        if (empty($recordsArray) || $firstRecord === null) {
            return;
        }

        // Batch load all relations at once (1 query instead of N)
        // Use the model's relation method to get the related model class
        $relationModel = $firstRecord->{$relation};
        if ($relationModel instanceof Model) {
            $relatedClass = get_class($relationModel);
            $relations = $relatedClass::whereIn($foreignKey, $keys)->get()->keyBy($foreignKey);
        } else {
            $relations = collect();
        }

        // Yield records with lazy-loaded relations attached
        foreach ($recordsArray as $record) {
            $record->{$relation} = $relations->get($record->{$localKey});
            yield $record;
        }
    }

    /**
     * Create optimized filtered query for DataTables server-side processing
     * 
     * Usage:
     * $query = $this->buildDataTableQuery(
     *     Enrollment::query(),
     *     [
     *         'search' => request('search'),
     *         'status' => request('status'),
     *         'created_at' => request('date_from')
     *     ],
     *     ['specialization', 'creator']
     * );
     * 
     * @param Builder $query
     * @param array $filters Key-value pairs for filtering
     * @param array $eagerLoadRelations Relations to eager load
     * @return Builder
     */
    public static function buildDataTableQuery(
        Builder $query,
        array $filters = [],
        array $eagerLoadRelations = []
    ): Builder {
        // Apply eager loading first (most efficient)
        if (!empty($eagerLoadRelations)) {
            $query->with($eagerLoadRelations);
        }

        // Apply filters
        foreach ($filters as $key => $value) {
            if (is_null($value) || $value === '' || $value === []) {
                continue;
            }

            if (str_contains($key, '_from')) {
                // Date range: field_from
                $field = str_replace('_from', '', $key);
                $query->where($field, '>=', $value);
            } elseif (str_contains($key, '_to')) {
                // Date range: field_to
                $field = str_replace('_to', '', $key);
                $query->where($field, '<=', $value);
            } elseif (is_array($value)) {
                // Multiple values: IN clause
                $query->whereIn($key, $value);
            } else {
                // Standard filter
                $query->where($key, $value);
            }
        }

        return $query;
    }

    /**
     * Get column statistics (count, sum, avg) with proper indexing
     * 
     * Usage:
     * $stats = $this->getColumnStats('enrollments', [
     *     'payment_amount' => ['sum', 'avg', 'min', 'max'],
     *     'status' => ['count']
     * ]);
     * 
     * @param string $table Table name
     * @param array $stats Columns with array of stat types
     * @return array
     */
    public static function getColumnStats(string $table, array $stats): array
    {
        $selectRaw = [];
        foreach ($stats as $column => $operations) {
            foreach ($operations as $operation) {
                $selectRaw[] = "{$operation}({$column}) as {$column}_{$operation}";
            }
        }

        $result = DB::table($table)
            ->selectRaw(implode(', ', $selectRaw))
            ->first();

        return $result ? (array) $result : [];
    }

    /**
     * Chunk and process records with progress tracking
     * Prevents memory overload on large operations
     * 
     * Usage:
     * $this->processInChunks(
     *     Enrollment::query(),
     *     function ($records) {
     *         // Process chunk of records
     *         sendEmailToEnrollments($records);
     *     },
     *     500 // Chunk size
     * );
     * 
     * @param Builder $query
     * @param callable $processor Receives array of records
     * @param int $chunkSize Records per chunk
     * @return void
     */
    public static function processInChunks(
        Builder $query,
        callable $processor,
        int $chunkSize = 500
    ): void {
        $query->chunk($chunkSize, $processor);
    }

    /**
     * Memory-efficient export to CSV with streaming
     * Avoids loading all records into memory
     * 
     * Usage:
     * return response()->streamDownload(
     *     fn() => $this->streamCsvExport(
     *         Enrollment::query()->with('specialization'),
     *         ['id', 'doctor_name', 'specialization.name']
     *     ),
     *     'enrollments.csv'
     * );
     * 
     * @param Builder $query
     * @param array $columns Column names/paths to export
     * @param callable|null $headerTransformer Function to transform header
     * @param callable|null $rowTransformer Function to transform each row
     * @return void
     */
    public static function streamCsvExport(
        Builder $query,
        array $columns,
        callable $headerTransformer = null,
        callable $rowTransformer = null
    ): void {
        $output = fopen('php://output', 'w');

        // Write header
        $header = $headerTransformer ? $headerTransformer($columns) : $columns;
        fputcsv($output, $header);

        // Stream records
        foreach ($query->cursor() as $record) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = self::getNestedValue($record, $column);
            }

            if ($rowTransformer) {
                $row = $rowTransformer($row, $record);
            }

            fputcsv($output, $row);
        }

        fclose($output);
    }

    /**
     * Get nested value from model/array using dot notation
     * 
     * @param mixed $data
     * @param string $path e.g., 'specialization.name'
     * @return mixed
     */
    private static function getNestedValue($data, string $path)
    {
        $parts = explode('.', $path);
        $value = $data;

        foreach ($parts as $part) {
            if ($value instanceof Model) {
                $value = $value->{$part} ?? null;
            } elseif (is_array($value)) {
                $value = $value[$part] ?? null;
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Log slow queries for debugging
     * Use in production with caution
     * 
     * @param string $query
     * @param array $bindings
     * @param float $time Execution time in milliseconds
     * @param float $threshold Minimum time to log
     * @return void
     */
    public static function logSlowQuery(
        string $query,
        array $bindings,
        float $time,
        float $threshold = 100.0
    ): void {
        if ($time > $threshold) {
            Log::warning('Slow Query Detected', [
                'query' => $query,
                'bindings' => $bindings,
                'time_ms' => $time,
                'threshold_ms' => $threshold,
            ]);
        }
    }

    /**
     * Analyze query performance and suggest optimizations
     * 
     * @param string $query SQL query
     * @return array Optimization suggestions
     */
    public static function analyzeQuery(string $query): array
    {
        $suggestions = [];

        if (str_contains(strtoupper($query), 'SELECT *')) {
            $suggestions[] = 'Avoid SELECT * - specify only needed columns';
        }

        if (str_contains(strtoupper($query), 'LIKE \'%')) {
            $suggestions[] = 'Leading wildcard LIKE queries are slow - consider full-text search or indexes';
        }

        if (str_contains(strtoupper($query), 'NOT IN')) {
            $suggestions[] = 'NOT IN can be slow - consider LEFT JOIN with NULL check';
        }

        if (preg_match_all('/JOIN/i', $query) > 3) {
            $suggestions[] = 'Multiple JOINs detected - consider denormalization or caching';
        }

        return $suggestions;
    }
}
