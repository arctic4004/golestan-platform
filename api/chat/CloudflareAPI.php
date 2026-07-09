<?php
// api/chat/CloudflareAPI.php
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
        $think_mode = $options['think'] ?? false;
        $search_mode = $options['search'] ?? false;
        
        // System prompt پایه
        $system_prompt = "شما دستیار هوشمند کافی‌نت گلستان در یاسوج هستید. همیشه به فارسی روان، دقیق و کامل پاسخ دهید. پاسخ‌هایتان را با ایموجی‌های مرتبط زیباتر کنید.";
        
        // حالت Think
        if ($think_mode) {
            $system_prompt .= "\n\n⚠️ **حالت تفکر عمیق فعال است.** قبل از پاسخ نهایی، فرآیند فکری خود را گام به گام توضیح بده:\n💭 **تحلیل:** سوال را تحلیل کن\n🧠 **استدلال:** گام‌های منطقی را بگو\n✅ **پاسخ نهایی:** جواب اصلی را بده";
        }
        
        // حالت Search
        if ($search_mode) {
            $system_prompt .= "\n\n🌐 **حالت جستجو فعال است.** طوری پاسخ بده که انگار به اینترنت دسترسی داری و اطلاعاتت کاملاً به‌روز است. اگر چیزی را نمی‌دانی، صادقانه بگو.";
        }
        
        $messages = [['role' => 'system', 'content' => $system_prompt]];
        
        // تاریخچه
        $recent = array_slice($history, -5);
        foreach ($recent as $msg) {
            $role = $msg['role'] === 'assistant' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }
        
        $messages[] = ['role' => 'user', 'content' => $message];
        
        // تنظیمات بر اساس حالت
        $max_tokens = $think_mode ? 2500 : 1500;
        $temperature = $think_mode ? 0.3 : 0.7;
        
        $ch = curl_init("https://api.cloudflare.com/client/v4/accounts/{$this->account_id}/ai/run/{$this->model}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_token,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'messages' => $messages,
                'max_tokens' => $max_tokens,
                'temperature' => $temperature
            ]),
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            throw new Exception('خطای اتصال: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            $error = json_decode($response, true);
            throw new Exception($error['errors'][0]['message'] ?? "HTTP $http_code");
        }
        
        $result = json_decode($response, true);
        
        return [
            'content' => $result['result']['response'],
            'tokens_used' => $result['result']['usage']['total_tokens'] ?? 0
        ];
    }
}