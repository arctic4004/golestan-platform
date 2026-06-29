<?php
class CloudflareAPI {
    private $api_token;
    private $account_id = '66b43b4fe65858aebd524af96cd93d54';
    private $model = '@cf/meta/llama-4-scout-17b-16e-instruct';
    
    public function __construct() {
        $this->api_token = $this->getApiToken();
        if (empty($this->api_token)) {
            throw new Exception('API Token not configured');
        }
    }
    
    private function getApiToken() {
        try {
            require_once __DIR__ . '/../../config/database.php';
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute(['deepseek_api_key']);
            $result = $stmt->fetch();
            if ($result && !empty($result['setting_value'])) {
                return $result['setting_value'];
            }
        } catch (Exception $e) {}
        
        return '';
    }
    
    public function sendMessage($message, $history = [], $model = null, $options = []) {
        $system_prompt = "شما دستیار هوشمند کافی‌نت گلستان هستید. همیشه به فارسی روان، دقیق و مفید پاسخ دهید. پاسخ‌هایتان را با ایموجی زیباتر کنید. نام کاربر را نمی‌دانید.";
        
        $messages = [['role' => 'system', 'content' => $system_prompt]];
        
        $recent = array_slice($history, -5);
        foreach ($recent as $msg) {
            $role = $msg['role'] === 'assistant' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }
        
        $messages[] = ['role' => 'user', 'content' => $message];
        
        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->account_id}/ai/run/{$this->model}";
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_token,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'messages' => $messages,
                'max_tokens' => 1500,
                'temperature' => 0.7
            ]),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            throw new Exception('Connection error: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            $error = json_decode($response, true);
            $error_msg = $error['errors'][0]['message'] ?? "HTTP $http_code";
            throw new Exception($error_msg);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['result']['response'])) {
            throw new Exception('Invalid API response');
        }
        
        return [
            'content' => $result['result']['response'],
            'tokens_used' => $result['result']['usage']['total_tokens'] ?? 0
        ];
    }
}
