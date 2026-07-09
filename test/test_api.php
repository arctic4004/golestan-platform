<?php
// test_api.php
require_once 'config/database.php';
require_once 'api/chat/DeepSeekAPI.php';

try {
    $api = new DeepSeekAPI();
    $response = $api->sendMessage('سلام، حالت چطوره؟');
    echo "<h2>✅ اتصال به DeepSeek با موفقیت انجام شد!</h2>";
    echo "<pre>";
    print_r($response);
    echo "</pre>";
} catch (Exception $e) {
    echo "<h2>❌ خطا در اتصال</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}