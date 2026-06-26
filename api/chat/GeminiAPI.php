<?php
// api/chat/GeminiAPI.php
class GeminiAPI {
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';
    
    public function __construct() {
        $this->api_key = $this->getApiKey();
    }
    
    private function getApiKey() {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute(['deepseek_api_key']);
            $result = $stmt->fetch();
            if ($result && !empty($result['setting_value'])) {
                return $result['setting_value'];
            }
        } catch (Exception $e) {}
        
        throw new Exception('API Key not found');
    }
    
    public function sendMessage($message, $history = [], $model = null) {
        $system_prompt = "شما دستیار کافی‌نت گلستان در یاسوج هستید. همیشه به فارسی روان و با احترام پاسخ دهید.";
        
        $contents = [];
        
        $contents[] = ['role' => 'user', 'parts' => [['text' => $system_prompt]]];
        $contents[] = ['role' => 'model', 'parts' => [['text' => 'باشه، من به عنوان دستیار کافی‌نت گلستان به فارسی پاسخ میدم.']]];
        
        $recent = array_slice($history, -3);
        foreach ($recent as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }
        
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];
        
        $data = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 2000,
            ]
        ];
        
        $url = $this->api_url . '?key=' . $this->api_key;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            // غیرفعال کردن SSL (برای هاست‌های ایرانی)
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            throw new Exception('CURL Error: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            $error = json_decode($response, true);
            throw new Exception('Gemini Error: ' . ($error['error']['message'] ?? 'HTTP ' . $http_code));
        }
        
        $result = json_decode($response, true);
        
        return [
            'content' => $result['candidates'][0]['content']['parts'][0]['text'],
            'tokens_used' => $result['usageMetadata']['totalTokenCount'] ?? 0
        ];
    }
}