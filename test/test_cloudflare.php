<?php
// test_cloudflare.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 تست Cloudflare Workers AI</h2>";

$account_id = '3ff489207f28602e2652ae16b157e89f';
$api_token = 'cfut_fHdZnWM2SgapvNgrKfXX4l7Cb1bLTyclGPHpHnWb8674ddd2';

// مدل‌های جدیدتر رو تست کن
$models = [
    '@cf/meta/llama-4-scout-17b-16e-instruct',
    '@cf/meta/llama-3.1-8b-instruct',
    '@cf/deepseek-ai/deepseek-r1-distill-qwen-32b',
    '@cf/qwen/qwen1.5-14b-chat-awq'
];

foreach ($models as $model) {
    echo "<h3>📤 تست مدل: " . basename($model) . "</h3>";
    
    $url = "https://api.cloudflare.com/client/v4/accounts/{$account_id}/ai/run/{$model}";
    
    $data = [
        'messages' => [
            ['role' => 'system', 'content' => 'شما دستیار فارسی زبان هستید. همیشه به فارسی پاسخ دهید.'],
            ['role' => 'user', 'content' => 'سلام! خودت رو به فارسی معرفی کن.']
        ],
        'max_tokens' => 300
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_token,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP: $http_code</p>";
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        echo "<p style='color:green;'>✅ این مدل کار میکنه!</p>";
        echo "<div style='background:#e8f5e9;padding:10px;border-radius:8px;'>";
        echo nl2br(htmlspecialchars($result['result']['response'] ?? ''));
        echo "</div>";
        echo "<p><strong>از این مدل استفاده کن:</strong> <code>$model</code></p>";
        break;
    } else {
        echo "<p style='color:red;'>❌ خطا</p>";
        echo "<pre style='font-size:11px;background:#ffe6e6;padding:5px;'>" . substr($response, 0, 200) . "</pre>";
    }
}
?>