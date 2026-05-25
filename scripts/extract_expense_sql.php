<?php

/**
 * Extract full legacy expense SQL dumps from the agent transcript.
 * Uses the largest user message containing tbl_expensive INSERT data.
 */

$transcript = 'C:/Users/SK NADIM/.cursor/projects/c-xampp-htdocs-mediforum-admin/agent-transcripts/0dc06794-11b8-42db-b289-654b74ee412c/0dc06794-11b8-42db-b289-654b74ee412c.jsonl';
$lines = file($transcript, FILE_IGNORE_NEW_LINES);
$text = '';
$bestLen = 0;

foreach ($lines as $line) {
    $row = json_decode($line, true);
    if (! is_array($row) || ($row['role'] ?? '') !== 'user') {
        continue;
    }
    foreach ($row['message']['content'] ?? [] as $part) {
        $candidate = $part['text'] ?? '';
        if (($part['type'] ?? '') !== 'text' || ! str_contains($candidate, 'INSERT INTO `tbl_expensive`')) {
            continue;
        }
        if (strlen($candidate) > $bestLen) {
            $bestLen = strlen($candidate);
            $text = $candidate;
        }
    }
}

if ($text === '') {
    fwrite(STDERR, "Expense SQL dump not found in transcript.\n");
    exit(1);
}

function extractInsertStatement(string $text, string $table, bool $useLastSemicolon = false): ?string
{
    $needle = "INSERT INTO `{$table}`";
    $start = strpos($text, $needle);
    if ($start === false) {
        return null;
    }

    $chunk = substr($text, $start);
    $end = $useLastSemicolon ? strrpos($chunk, ';') : strpos($chunk, ';');
    if ($end === false) {
        return null;
    }

    return substr($chunk, 0, $end + 1);
}

$out = dirname(__DIR__).'/storage/app';

$categoryInsert = extractInsertStatement($text, 'tbl_expensive_category', useLastSemicolon: false);
if ($categoryInsert !== null) {
    file_put_contents($out.'/legacy_tbl_expensive_category.sql', $categoryInsert."\n");
    echo 'Category INSERT: '.strlen($categoryInsert)." bytes\n";
}

// Must not match tbl_expensive_category — search after category block.
$expenseStart = strpos($text, 'INSERT INTO `tbl_expensive` (');
if ($expenseStart === false) {
    $expenseStart = strpos($text, 'INSERT INTO `tbl_expensive` VALUES');
}
$expenseInsert = null;
if ($expenseStart !== false) {
    $chunk = substr($text, $expenseStart);
    $end = strrpos($chunk, ';');
    $expenseInsert = $end !== false ? substr($chunk, 0, $end + 1) : null;
}
if ($expenseInsert !== null) {
    file_put_contents($out.'/legacy_tbl_expensive.sql', $expenseInsert."\n");
    preg_match_all('/\(\d+,/', $expenseInsert, $m);
    echo 'Expense INSERT: '.strlen($expenseInsert).' bytes, ~'.count($m[0])." rows\n";
}

echo "Wrote dumps to storage/app\n";
