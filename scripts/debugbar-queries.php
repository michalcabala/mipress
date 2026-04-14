<?php

// Analyze Debugbar JSON files for duplicate/heavy queries
// Usage: php scripts/debugbar-queries.php [minQueries=10] [skip=0]
//   skip=N skips N matching files (to browse older recordings)
$dir = __DIR__.'/../storage/debugbar';
$files = glob($dir.'/*.json');
usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

$targetFile = $files[0];
$minQueries = (int) ($argv[1] ?? 10);
$skip = (int) ($argv[2] ?? 0);

$matched = 0;
$seen = [];
foreach (array_slice($files, 0, 200) as $f) {
    $data = json_decode(file_get_contents($f), true);
    $count = count($data['queries']['statements'] ?? []);
    $uri = $data['__meta']['uri'] ?? '?';
    if ($count >= $minQueries) {
        if ($matched < $skip) {
            $matched++;

            continue;
        }
        $targetFile = $f;
        break;
    }
}

$data = json_decode(file_get_contents($targetFile), true);
$statements = $data['queries']['statements'] ?? [];
$uri = $data['__meta']['uri'] ?? '?';
$method = $data['__meta']['method'] ?? '?';

echo '=== File: '.basename($targetFile)." ===\n";
echo "=== {$method} {$uri} — ".count($statements)." queries ===\n\n";

// Group by SQL to find duplicates
$grouped = [];
foreach ($statements as $i => $q) {
    $sql = $q['sql'] ?? '';
    if (str_starts_with($sql, 'Connection')) {
        continue;
    }
    $grouped[$sql][] = ['index' => $i, 'duration' => $q['duration_str'] ?? '?'];
}

// Show duplicates first
echo "--- DUPLICATE QUERIES ---\n";
$dupes = array_filter($grouped, fn ($g) => count($g) > 1);
foreach ($dupes as $sql => $occurrences) {
    echo count($occurrences).'x | '.substr($sql, 0, 200)."\n";
}

if (empty($dupes)) {
    echo "(none)\n";
}

echo "\n--- ALL QUERIES ---\n";
foreach ($statements as $i => $q) {
    $sql = $q['sql'] ?? '';
    if (str_starts_with($sql, 'Connection')) {
        continue;
    }
    $dur = $q['duration_str'] ?? '?';
    echo str_pad((string) ($i + 1), 3, ' ', STR_PAD_LEFT).". {$dur} | ".substr($sql, 0, 250)."\n";
}
