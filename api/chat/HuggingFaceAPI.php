<?php
class HuggingFaceAPI
{
    private $token;
    private $api_url = 'https://api-inference.huggingface.co/models/';

    // مدل‌های رایگان
    private $chat_model = 'Qwen/Qwen2.5-7B-Instruct';
    private $image_model = 'black-forest-labs/FLUX.1-schnell';
    private $analyze_model = 'Salesforce/blip-image-captioning-large';

    public function __construct()
    {
        $this->token = $this->getToken();
    }

    private function getToken()
    {
        // از دیتابیس
        try {
            require_once __DIR__ . '/../../config/database.php';
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute(['huggingface_token']);
            $result = $stmt->fetch();
            if ($result && !empty($result['setting_value'])) return $result['setting_value'];
        } catch (Exception $e) {
        }

        throw new Exception('HuggingFace token not found in database');
    }

    public function chat($message, $history = [])
    {
        $system_prompt = "شما دستیار هوشمند کافی‌نت گلستان هستید. همیشه به فارسی روان و دقیق پاسخ دهید. پاسخ‌ها را با ایموجی زیبا کنید.";

        $prompt = $system_prompt . "\n\n";
        foreach (array_slice($history, -5) as $msg) {
            $prompt .= ($msg['role'] === 'user' ? 'کاربر' : 'دستیار') . ": " . $msg['content'] . "\n";
        }
        $prompt .= "کاربر: " . $message . "\nدستیار:";

        $response = $this->callAPI($this->chat_model, [
            'inputs' => $prompt,
            'parameters' => [
                'max_new_tokens' => 1000,
                'temperature' => 0.7,
                'return_full_text' => false
            ]
        ]);

        return [
            'content' => $response[0]['generated_text'] ?? 'متأسفانه خطایی رخ داد.',
            'tokens_used' => 0
        ];
    }

    public function generateImage($prompt)
    {
        $response = $this->callAPI($this->image_model, ['inputs' => $prompt]);
        return $response; // باینری عکس
    }

    public function analyzeImage($image_url)
    {
        $response = $this->callAPI($this->analyze_model, ['inputs' => $image_url]);
        return $response[0]['generated_text'] ?? '';
    }

    private function callAPI($model, $data)
    {
        $ch = curl_init($this->api_url . $model);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 503) {
            // مدل در حال بارگذاری - دوباره تلاش کن
            sleep(5);
            return $this->callAPI($model, $data);
        }

        if ($http_code !== 200) {
            throw new Exception('API Error: HTTP ' . $http_code);
        }

        return json_decode($response, true);
    }
}
