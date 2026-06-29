<?php
class DeepSeekAPI {
    
    public function sendMessage($message, $history = [], $model = null, $options = []) {
        // اول HuggingFace
        try {
            require_once __DIR__ . '/HuggingFaceAPI.php';
            $hf = new HuggingFaceAPI();
            return $hf->chat($message, $history);
        } catch (Exception $e) {
            error_log("HuggingFace Error: " . $e->getMessage());
        }
        
        // بعد Cloudflare
        try {
            require_once __DIR__ . '/CloudflareAPI.php';
            $cf = new CloudflareAPI();
            return $cf->sendMessage($message, $history, $model, $options);
        } catch (Exception $e) {
            error_log("Cloudflare Error: " . $e->getMessage());
        }
        
        // هیچکدوم کار نکرد
        return $this->getLocalResponse($message);
    }
    
    private function getLocalResponse($message) {
        $msg = mb_strtolower(trim($message));
        
        $responses = [
            'سلام' => 'سلام! وقت بخیر. من دستیار هوش مصنوعی کافی‌نت گلستان هستم. چطور می‌تونم کمک کنم؟ 😊',
            'قیمت' => "💰 قیمت خدمات:\n💻 کامپیوتر: از ۲۰۰ هزارتومن\n🔒 امنیت: از ۱ میلیون\n🌐 طراحی سایت: از ۸ میلیون\n🎨 ساخت عکس با AI: رایگان\n\n📞 تماس: ۰۹۱۷۷۴۱۸۲۸۶",
            'آدرس' => '📍 یاسوج، پاسداران، بین گلستان ۳ و ۴\n📞 ۰۹۱۷۷۴۱۸۲۸۶',
            'ساعت' => '⏰ ساعت کاری: ۹ صبح تا ۱۰ شب\n📅 همه روزه',
            'خدمات' => '🛠️ خدمات ما:\n💻 تعمیر کامپیوتر\n🔒 امنیت شبکه\n🌐 طراحی سایت\n🎨 ساخت عکس با AI\n📱 برنامه‌نویسی موبایل',
            'پشتیبانی' => '📞 پشتیبانی: ۰۹۱۷۷۴۱۸۲۸۶\n📧 ایمیل: info@golestanyasuj.ir\n🆔 تلگرام: @GolestanNet',
        ];
        
        foreach ($responses as $key => $response) {
            if (mb_strpos($msg, $key) !== false) {
                return ['content' => $response, 'tokens_used' => 0];
            }
        }
        
        return [
            'content' => '⚠️ سرویس هوش مصنوعی موقتاً در دسترس نیست. لطفاً دوباره تلاش کنید.\n📞 پشتیبانی: ۰۹۱۷۷۴۱۸۲۸۶',
            'tokens_used' => 0
        ];
    }
}
