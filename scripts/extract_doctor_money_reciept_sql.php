<?php

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
        if (($part['type'] ?? '') === 'text' && str_contains($candidate, 'INSERT INTO `tbl_doctor_money_reciept`')) {
            if (strlen($candidate) > $bestLen) {
                $bestLen = strlen($candidate);
                $text = $candidate;
            }
        }
    }
}

if ($text === '') {
    fwrite(STDERR, "Money receipt SQL dump not found in transcript.\n");
    exit(1);
}

$start = strpos($text, 'INSERT INTO `tbl_doctor_money_reciept`');
$chunk = substr($text, $start);
$end = strrpos($chunk, ';');
$insert = $end !== false ? substr($chunk, 0, $end + 1) : $chunk;

$insert = str_replace('`tbl_doctor_money_reciept`', '`legacy_tbl_doctor_money_reciept`', $insert);

$out = dirname(__DIR__) . '/storage/app/legacy_tbl_doctor_money_reciept.sql';
file_put_contents($out, $insert . "\n");

preg_match_all('/\(\d+,/', $insert, $m);
echo 'Wrote ' . strlen($insert) . ' bytes, ~' . count($m[0]) . " rows to {$out}\n";

if (preg_match('/INSERT INTO `legacy_tbl_doctor_money_reciept`\s*\(([^)]+)\)/', $insert, $cols)) {
    echo 'Columns: ' . $cols[1] . "\n";
}
