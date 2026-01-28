<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('jobs')
    ->select(['id', 'queue', 'attempts', 'available_at', 'created_at', 'payload'])
    ->where('queue', 'handoff')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

if ($rows->isEmpty()) {
    echo "No jobs found in queue=handoff\n";
    exit(0);
}

echo "Last 10 jobs (queue=handoff)\n";
echo "===========================\n";
foreach ($rows as $job) {
    $payload = json_decode($job->payload, true);
    $name = $payload['displayName'] ?? ($payload['data']['commandName'] ?? 'unknown');
    echo "id={$job->id} queue={$job->queue} attempts={$job->attempts} available_at={$job->available_at} created_at={$job->created_at} name={$name}\n";
}
