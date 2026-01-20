<?php
require __DIR__ . '/vendor/autoload.php';

// Use environment variables instead
$key = env('OPENAI_API_KEY');
$assistantId = env('OPENAI_ASSISTANT_ID');

use Illuminate\Support\Facades\Http;

// Test 1: Create thread
echo "Test 1: Creating thread...\n";
$threadResponse = Http::withToken($key)
    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
    ->post("https://api.openai.com/v1/threads");

$thread = $threadResponse->json();
$threadId = $thread['id'] ?? null;

echo "Thread ID: " . ($threadId ? $threadId : 'FAILED') . "\n";
var_dump($thread);

// Test 2: Add message
echo "\nTest 2: Adding message...\n";
$msgResponse = Http::withToken($key)
    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
    ->post("https://api.openai.com/v1/threads/{$threadId}/messages", [
        'role' => 'user',
        'content' => 'Oi',
    ]);

$msg = $msgResponse->json();
echo "Message added\n";
var_dump($msg);

// Test 3: Create run
echo "\nTest 3: Creating run...\n";
$runResponseObj = Http::withToken($key)
    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
    ->post("https://api.openai.com/v1/threads/{$threadId}/runs", [
        'assistant_id' => $assistantId,
    ]);

$runResponse = $runResponseObj->json();
$runId = $runResponse['id'] ?? null;

echo "Run ID: " . ($runId ? $runId : 'FAILED') . "\n";
var_dump($runResponse);

// Test 4: Check run status
echo "\nTest 4: Checking run status...\n";
$statusResponseObj = Http::timeout(30)->withToken($key)
    ->withHeaders(['OpenAI-Beta' => 'assistants=v2'])
    ->get("https://api.openai.com/v1/threads/{$threadId}/runs/{$runId}");

$status = $statusResponseObj->json();
echo "Status: " . ($status['status'] ?? 'UNKNOWN') . "\n";
var_dump($status);
