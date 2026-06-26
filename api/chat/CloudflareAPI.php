<?php
// api/chat/CloudflareAPI.php
class CloudflareAPI {
    private $api_token;
    private $account_id = '66b43b4fe65858aebd524af96cd93d54';
    private $model = '@cf/meta/llama-4-scout-17b-16e-instruct';
    
    public function __construct() {
        $this->api_token = $this->getApiToken();
    }
    
    private function getApiToken() {
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
        throw new Exception('API Token not found');
    }
    
    public function sendMessage($message, $history = [], $model = null, $options = []) {
        $think_mode = $options['think'] ?? false;
        $search_mode = $options['search'] ?? false;
        
        // تاریخ شمسی ساده
        $gregorian_year = date('Y');
        $jalali_year = $gregorian_year - 621; // تبدیل تقریبی
        $today = date('Y/m/d');
        $jalali_date = ($jalali_year) . date('/m/d');
        
        // پرامپت سیستم
        $system_prompt = "شما دستیار هوش مصنوعی کافی‌نت گلستان در یاسوج هستید. همیشه به فارسی روان، دقیق و کامل پاسخ دهید.
        
📅 اطلاعات مهم:
- تاریخ امروز: {$today} میلادی (حدوداً {$jalali_date} شمسی - سال " . ($jalali_year) . " خورشیدی)
- زمان فعلی: " . date('H:i') . "
- شما یک مدل زبانی هستید که دانش شما تا اوایل ۲۰۲۴ میلادی (اواخر ۱۴۰۲ شمسی) به‌روز است.
- اگر سوال درباره رویدادهای بعد از این تاریخ است، صادقانه بگویید که اطلاعاتتان به‌روز نیست.
- برای سوالات ریاضی، منطقی و عمومی می‌توانید پاسخ دقیق دهید.";
        
        if ($think_mode) {
            $system_prompt .= "\n\n⚠️ حالت تفکر عمیق فعال است. لطفاً قبل از پاسخ نهایی، فرآیند فکری خود را به صورت گام به گام توضیح دهید. ابتدا تحلیل کنید، سپس استدلال کنید، و در نهایت پاسخ نهایی را ارائه دهید.\n\nفرمت پاسخ:\n💭 **تحلیل:** (تحلیل سوال)\n🧠 **استدلال:** (گام‌های فکری)\n✅ **پاسخ نهایی:** (جواب اصلی)";
        }
        
        if ($search_mode) {
            $system_prompt .= "\n\n⚠️ حالت جستجو فعال است. لطفاً طوری پاسخ دهید که انگار به اینترنت دسترسی دارید و اطلاعات به‌روز دارید.";
        }
        
        $messages = [['role' => 'system', 'content' => $system_prompt]];
        
        $recent = array_slice($history, -5);
        foreach ($recent as $msg) {
            $role = $msg['role'] === 'assistant' ? 'assistant' : 'user';
            $messages[] = ['role' => $role, 'content' => $msg['content']];
        }
        
        $messages[] = ['role' => 'user', 'content' => $message];
        
        $url = "https://api.cloudflare.com/client/v4/accounts/{$this->account_id}/ai/run/{$this->model}";
        
        $data = [
            'messages' => $messages,
            'max_tokens' => $think_mode ? 3000 : 2000,
            'temperature' => $think_mode ? 0.3 : 0.7
        ];
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->api_token,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 40
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            $error = json_decode($response, true);
            throw new Exception($error['errors'][0]['message'] ?? 'HTTP ' . $http_code);
        }
        
        $result = json_decode($response, true);
        
        return [
            'content' => $result['result']['response'],
            'tokens_used' => $result['result']['usage']['total_tokens'] ?? 0
        ];
    }
}