<?php

$sql = file_get_contents(__DIR__ . '/../storage/app/legacy_tbl_user.sql');
$sql = str_replace('`tbl_user`', '`legacy_tbl_user`', $sql);
$sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_user|tbl_user)`?.*?;\s*/is', '', $sql) ?? $sql;
$sql = str_replace(["\r\n", "\r"], "\n", $sql);
$parts = preg_split('/;\s*\n/', $sql) ?: [];
$parts = array_values(array_filter(array_map(static fn ($p) => rtrim(trim($p), ';'), $parts), static fn ($p) => $p !== ''));

echo substr($parts[0], 0, 200);
