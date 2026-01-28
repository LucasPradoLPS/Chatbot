<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$uuid = $argv[1] ?? null;

$query = Illuminate\Support\Facades\DB::table('failed_jobs')->orderBy('failed_at', 'desc');
if ($uuid) {
    $query->where('uuid', $uuid);
}

$job = $query->first();

if (!$job) {
    echo $uuid
        ? "No failed_jobs record found for uuid: {$uuid}\n"
        : "No failed_jobs records found\n";
    exit(0);
}

echo $uuid ? "FAILED JOB\n" : "FAILED JOB (latest)\n";
echo "=================\n";
foreach ($job as $k => $v) {
    if ($k === 'payload' || $k === 'exception') {
        continue;
    }
    echo $k . ': ' . $v . "\n";
}

echo "\nEXCEPTION\n";
echo "---------\n";
echo $job->exception . "\n";

echo "\nPAYLOAD (truncated)\n";
echo "-------------------\n";
$payload = $job->payload ?? '';
echo substr($payload, 0, 4000) . (strlen($payload) > 4000 ? "\n... (truncated)\n" : "\n");
