<?php
// Use environment variables instead
$key = env('OPENAI_API_KEY') ?? $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY');
$assistantId = env('OPENAI_ASSISTANT_ID') ?? $_ENV['OPENAI_ASSISTANT_ID'] ?? getenv('OPENAI_ASSISTANT_ID');

function apiCall($method, $endpoint, $key, $data = null) {
    $ch = curl_init("https://api.openai.com/v1$endpoint");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $key,
        'OpenAI-Beta: assistants=v2',
        'Content-Type: application/json',
    ]);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => $response ? json_decode($response, true) : null,
        'error' => $error,
        'raw' => $response,
    ];
}

// Test 1: Create thread
echo "Test 1: Creating thread...\n";
$threadResult = apiCall('POST', '/threads', $key);
echo "HTTP Status: {$threadResult['status']}\n";
$thread = $threadResult['body'];
$threadId = $thread['id'] ?? null;
echo "Thread ID: " . ($threadId ? $threadId : 'FAILED') . "\n";
if (!$threadId) {
    var_dump($thread);
    exit(1);
}

echo "\n";

// Test 2: Add message
echo "Test 2: Adding message...\n";
$msgResult = apiCall('POST', "/threads/$threadId/messages", $key, [
    'role' => 'user',
    'content' => 'Oi',
]);
echo "HTTP Status: {$msgResult['status']}\n";
if ($msgResult['status'] !== 200) {
    var_dump($msgResult);
    exit(1);
}

echo "\n";

// Test 3: Create run
echo "Test 3: Creating run...\n";
$runResult = apiCall('POST', "/threads/$threadId/runs", $key, [
    'assistant_id' => $assistantId,
]);
echo "HTTP Status: {$runResult['status']}\n";
$runData = $runResult['body'];
$runId = $runData['id'] ?? null;
echo "Run ID: " . ($runId ? $runId : 'FAILED') . "\n";
echo "Run Status (initial): " . ($runData['status'] ?? 'UNKNOWN') . "\n";
if (!$runId) {
    var_dump($runData);
    exit(1);
}

echo "\n";

// Test 4: Poll for completion
echo "Test 4: Polling for run completion...\n";
$maxAttempts = 30;
for ($i = 0; $i < $maxAttempts; $i++) {
    echo "Attempt " . ($i + 1) . ": ";
    
    $statusResult = apiCall('GET', "/threads/$threadId/runs/$runId", $key);
    echo "HTTP Status: {$statusResult['status']} | ";
    
    $statusData = $statusResult['body'];
    $status = $statusData['status'] ?? 'UNKNOWN';
    echo "Run Status: {$status}\n";
    
    if ($status === 'completed') {
        echo "\n✓ Run completed!\n";
        break;
    } elseif ($status === 'failed') {
        echo "\n❌ Run failed!\n";
        var_dump($statusData);
        exit(1);
    }
    
    sleep(2);
}

echo "\n";

// Test 5: Get messages
echo "Test 5: Getting messages...\n";
$msgResult = apiCall('GET', "/threads/$threadId/messages", $key);
echo "HTTP Status: {$msgResult['status']}\n";
$messages = $msgResult['body'];
if (isset($messages['data']) && count($messages['data']) > 0) {
    $firstMessage = $messages['data'][0];
    echo "Latest Message Content: " . ($firstMessage['content'][0]['text']['value'] ?? 'EMPTY') . "\n";
} else {
    echo "No messages found\n";
    var_dump($messages);
}
