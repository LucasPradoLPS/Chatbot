<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

// Try making an actual call
$key = config('services.openai.key');
$response = Http::withToken($key)->withHeaders(['OpenAI-Beta' => 'assistants=v2'])->post("https://api.openai.com/v1/threads");

echo "Response type: " . get_class($response) . "\n";
echo "Response methods:\n";

// Check available methods
$methods = get_class_methods($response);
foreach (['json', 'collect', 'toArray', 'object', 'status', 'body'] as $method) {
    if (in_array($method, $methods)) {
        try {
            $result = $response->$method();
            echo "  $method(): " . gettype($result) . "\n";
            if (is_array($result) || is_object($result)) {
                var_dump($result);
            } else {
                echo "    Value: $result\n";
            }
        } catch (\Exception $e) {
            echo "  $method(): Error - " . $e->getMessage() . "\n";
        }
    }
}
