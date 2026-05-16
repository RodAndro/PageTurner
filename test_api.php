<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Services\GeminiAIService;

echo "Testing Gemini API Service...\n";

$service = new GeminiAIService();
echo "API Key Available: " . ($service->isAvailable() ? 'YES' : 'NO') . "\n";

try {
    echo "Testing getBookRecommendations...\n";
    $result = $service->getBookRecommendations('fantasy books');
    echo "Result: " . print_r($result, true) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
