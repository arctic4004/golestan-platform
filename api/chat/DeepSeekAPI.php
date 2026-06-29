<?php
class DeepSeekAPI {
    
    public function sendMessage($message, $history = [], $model = null, $options = []) {
        // اول Cloudflare (Llama 4)
        try {
            require_once __DIR__ . '/CloudflareAPI.php';
            $cf = new CloudflareAPI();
            return $cf->sendMessage($message, $history, $model, $options);
        } catch (Exception $e) {
            error_log("Cloudflare Error: " . $e->getMessage());
        }
        
        // بعد Gemini
        try {
            require_once __DIR__ . '/GeminiAPI.php';
            $gemini = new GeminiAPI();
            return $gemini->sendMessage($message, $history, $model, $options);
        } catch (Exception $e) {
            error_log("Gemini Error: " . $e->getMessage());
        }
        
        // هیچکدوم کار نکرد
        return $this->getLocalResponse($message);
    }
    
    private function getLocalResponse($message) {
        $msg = mb_strtolower(trim($message));
        
        $responses = [
            'سلام' => 'سلام! وقت بخیر. من دستیار هوش مصنوعی کافی‌نت گلستان هستم. چطور می‌تونم کمک کنم؟ 😊',
            'قیمت' => "💰 قیمت خدمات:\n💻 تعمیر کامپیوتر: از ۲۰۰ هزارتومن\n🔒 امنیت شبکه: از ۱ میلیون\n🌐 طراحی سایت: از ۸ میلیون\n🎨 ساخت عکس با AI: رایگان\n\n📞 تماس: ۰۹۱۷۷۴۱۸۲۸۶",
            'آدرس' => '📍 یاسوج، پاسداران، بین گلستان ۳ و ۴\n📞 ۰۹۱۷۷۴۱۸۲۸۶',
            'ساعت' => '⏰ ساعت کاری: ۹ صبح تا ۱۰ شب - همه روزه',
            'خدمات' => '🛠️ خدمات:\n💻 تعمیر کامپیوتر و لپ‌تاپ\n🔒 امنیت شبکه و نصب آنتی‌ویروس\n🌐 طراحی سایت و فروشگاه\n🎨 ساخت عکس با هوش مصنوعی\n📱 برنامه‌نویسی موبایل',
            'پشتیبانی' => '📞 ۰۹۱۷۷۴۱۸۲۸۶\n📧 info@golestanyasuj.ir',
        ];
        
        foreach ($responses as $key => $response) {
            if (mb_strpos($msg, $key) !== false) {
                return ['content' => $response, 'tokens_used' => 0];
            }
        }
        
        return [
            'content' => '⚠️ سرویس هوش مصنوعی موقتاً در دسترس نیست.\n📞 لطفاً با پشتیبانی تماس بگیرید: ۰۹۱۷۷۴۱۸۲۸۶',
            'tokens_used' => 0
        ];
    }
}
