<?php
class DeepSeekAPI {
    
    public function sendMessage($message, $history = [], $model = null, $options = []) {
        // تست Cloudflare
        try {
            require_once __DIR__ . '/CloudflareAPI.php';
            $cf = new CloudflareAPI();
            $result = $cf->sendMessage($message, $history, $model, $options);
            if (!empty($result['content'])) return $result;
        } catch (Exception $e) {
            error_log("Cloudflare: " . $e->getMessage());
        }
        
        // تست Gemini
        try {
            require_once __DIR__ . '/GeminiAPI.php';
            $gemini = new GeminiAPI();
            $result = $gemini->sendMessage($message, $history, $model, $options);
            if (!empty($result['content'])) return $result;
        } catch (Exception $e) {
            error_log("Gemini: " . $e->getMessage());
        }
        
        return $this->getLocalResponse($message);
    }
    
    private function getLocalResponse($message) {
        $msg = mb_strtolower(trim($message));
        
        $responses = [
            'سلام' => 'سلام! وقت بخیر. من دستیار هوش مصنوعی کافی‌نت گلستان هستم. چطور می‌تونم کمک کنم؟ 😊',
            'قیمت' => "💰 قیمت خدمات:\n💻 تعمیر کامپیوتر: از ۲۰۰ هزارتومن\n🔒 امنیت شبکه: از ۱ میلیون\n🌐 طراحی سایت: از ۸ میلیون\n🎨 ساخت عکس با AI: رایگان\n\n📞 تماس: ۰۹۱۷۷۴۱۸۲۸۶",
            'آدرس' => '📍 یاسوج، پاسداران، بین گلستان ۳ و ۴\n📞 ۰۹۱۷۷۴۱۸۲۸۶',
            'ساعت' => '⏰ ساعت کاری: ۹ صبح تا ۱۰ شب - همه روزه',
            'خدمات' => '🛠️ خدمات ما:\n💻 تعمیر کامپیوتر و لپ‌تاپ\n🔒 امنیت شبکه\n🌐 طراحی سایت\n🎨 ساخت عکس با AI\n📱 برنامه‌نویسی',
            'پشتیبانی' => '📞 پشتیبانی: ۰۹۱۷۷۴۱۸۲۸۶\n📧 info@golestanyasuj.ir',
            'خداحافظ' => 'خدانگهدار! 🌹 هر وقت نیاز داشتید من اینجام.',
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