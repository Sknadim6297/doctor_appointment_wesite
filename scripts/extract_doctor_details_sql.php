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
        if (($part['type'] ?? '') === 'text' && str_contains($candidate, 'INSERT INTO `tbl_doctor_details`')) {
            if (strlen($candidate) > $bestLen) {
                $bestLen = strlen($candidate);
                $text = $candidate;
            }
        }
    }
}

if ($text === '') {
    fwrite(STDERR, "Doctor details SQL dump not found in transcript.\n");
    exit(1);
}

$start = strpos($text, 'INSERT INTO `tbl_doctor_details`');
$chunk = substr($text, $start);
$end = strrpos($chunk, ';');
$insert = $end !== false ? substr($chunk, 0, $end + 1) : $chunk;

$out = dirname(__DIR__) . '/storage/app/legacy_tbl_doctor_details.sql';
file_put_contents($out, $insert . "\n");

preg_match_all('/\(\d+,/', $insert, $m);
echo 'Wrote ' . strlen($insert) . ' bytes, ~' . count($m[0]) . " rows to {$out}\n";
