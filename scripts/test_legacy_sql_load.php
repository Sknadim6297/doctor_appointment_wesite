<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$sql = file_get_contents(__DIR__ . '/../storage/app/legacy_tbl_user.sql');
$sql = str_replace('`tbl_user`', '`legacy_tbl_user`', $sql);
$sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_user|tbl_user)`?.*?;\s*/is', '', $sql) ?? $sql;
$sql = str_replace(["\r\n", "\r"], "\n", $sql);
$parts = preg_split('/;\s*\n/', $sql) ?: [];
$parts = array_values(array_filter(array_map(static fn ($p) => rtrim(trim($p), ';'), $parts), static fn ($p) => $p !== ''));

echo 'parts: ' . count($parts) . PHP_EOL;

DB::table('legacy_tbl_user')->truncate();

foreach ($parts as $i => $statement) {
    if (!preg_match('/^INSERT\s+INTO\s+`?legacy_tbl_user`?/i', $statement)) {
        echo "Part $i: SKIP (not insert), len=" . strlen($statement) . PHP_EOL;
        continue;
    }
    $rows = substr_count($statement, '),(') + 1;
    echo "Part $i: INSERT rows~$rows len=" . strlen($statement) . PHP_EOL;
    try {
        DB::unprepared($statement);
        echo "  OK\n";
    } catch (Throwable $e) {
        echo "  FAIL: " . $e->getMessage() . "\n";
    }
}

$min = DB::table('legacy_tbl_user')->min('id');
$max = DB::table('legacy_tbl_user')->max('id');
$cnt = DB::table('legacy_tbl_user')->count();
echo "staging: count=$cnt min=$min max=$max\n";
