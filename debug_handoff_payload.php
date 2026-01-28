<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$row = Illuminate\Support\Facades\DB::table('jobs')
    ->where('queue', 'handoff')
    ->orderBy('id', 'desc')
    ->first();

if (!$row) {
    echo "No pending jobs in queue=handoff\n";
    exit(0);
}

$payload = json_decode($row->payload ?? '', true);
$command = $payload['data']['command'] ?? '';

echo "JOB id={$row->id} attempts={$row->attempts} available_at={$row->available_at}\n";
echo "displayName=" . ($payload['displayName'] ?? 'n/a') . "\n";

echo "\nCOMMAND SNIPPET\n";
echo "--------------\n";
$pos = strpos($command, 'clientNumber');
if ($pos === false) {
    echo "'clientNumber' not found in serialized command\n";
    echo substr($command, 0, 800) . (strlen($command) > 800 ? "\n... (truncated)\n" : "\n");
    exit(0);
}

$start = max(0, $pos - 120);
$snippet = substr($command, $start, 600);
echo $snippet . "\n";
