<?php
// api/chat/DeepSeekAPI.php
class DeepSeekAPI {
    
    public function sendMessage($message, $history = [], $model = null, $options = []) {
        // Cloudflare AI
        try {
            if (!class_exists('CloudflareAPI')) {
                require_once __DIR__ . '/CloudflareAPI.php';
            }
            $cf = new CloudflareAPI();
            return $cf->sendMessage($message, $history, $model, $options);
        } catch (Exception $e) {
            error_log("AI Error: " . $e->getMessage());
        }
        
        return $this->getLocalResponse($message);
    }
    
    private function getLocalResponse($message) {
        $msg = mb_strtolower(trim($message));
        
        $responses = [
            'سلام' => 'سلام! وقت بخیر. من دستیار هوش مصنوعی کافی‌نت گلستان هستم. چطور می‌تونم کمک کنم؟ 😊',
            'قیمت' => "💰 قیمت خدمات:\n💻 کامپیوتر: از ۲۰۰ هزارتومن\n🔒 امنیت: از ۱ میلیون\n🌐 طراحی سایت: از ۸ میلیون\n🎨 ساخت عکس با AI: رایگان\n\n📞 تماس: ۰۹۱۷۷۴۱۸۲۸۶",
            'آدرس' => '📍 یاسوج، پاسداران، بین گلستان ۳ و ۴\n📞 ۰۹۱۷۷۴۱۸۲۸۶',
        ];
        
        foreach ($responses as $key => $response) {
            if (mb_strpos($msg, $key) !== false) {
                return ['content' => $response, 'tokens_used' => 0];
            }
        }
        
        return [
            'content' => '⚠️ سرویس هوش مصنوعی موقتاً در دسترس نیست. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید: ۰۹۱۷۷۴۱۸۲۸۶',
            'tokens_used' => 0
        ];
    }
}