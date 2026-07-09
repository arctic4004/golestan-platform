<?php
// api/chat/send.php - نسخه کامل با Knowledge Base
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

$auth = new Auth();

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'لطفاً وارد شوید']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = $auth->getUser();

// Rate limiting
$stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$stmt->execute([$_SESSION['user_id']]);
$hourly_messages = $stmt->fetch()['count'];

if ($hourly_messages >= 50) {
    http_response_code(429);
    echo json_encode(['error' => 'محدودیت تعداد درخواست. لطفاً کمی صبر کنید.']);
    exit;
}

if ($user['credits'] < 1) {
    http_response_code(402);
    echo json_encode(['error' => 'اعتبار کافی نیست.']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$conversation_id = $input['conversation_id'] ?? null;
$model = $input['model'] ?? 'llama-4';
$think = $input['think'] ?? false;
$search = $input['search'] ?? false;

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'پیام نمی‌تواند خالی باشد']);
    exit;
}

// ========== Knowledge Base ==========
$knowledge_context = '';
$kb_file = $_SERVER['DOCUMENT_ROOT'] . '/knowledge/cafenet_knowledge.json';

if (file_exists($kb_file)) {
    $kb = json_decode(file_get_contents($kb_file), true);
    $services = $kb['cafenet_knowledge']['services'] ?? [];
    
    $msg_lower = mb_strtolower($message);
    $matched = [];
    
    foreach ($services as $s) {
        $all_kw = array_merge($s['keywords'] ?? [], $s['synonyms'] ?? []);
        $all_kw = array_map('mb_strtolower', $all_kw);
        
        foreach ($all_kw as $kw) {
            if (mb_strpos($msg_lower, $kw) !== false) {
                $matched[$s['id']] = $s;
                break;
            }
        }
        if (count($matched) >= 5) break;
    }
    
    if (!empty($matched)) {
        $knowledge_context = "\n\n📋 **اطلاعات خدمات کافی‌نت گلستان:**\n";
        foreach ($matched as $s) {
            $knowledge_context .= "• **{$s['service']}** ({$s['category']})\n";
            $knowledge_context .= "  💰 قیمت: " . number_format($s['price_toman']) . " تومان\n";
            $knowledge_context .= "  ⏱ زمان: {$s['estimated_time']}\n";
            $knowledge_context .= "  📄 مدارک: " . implode('، ', $s['documents']) . "\n";
            if (!empty($s['notes'])) $knowledge_context .= "  💡 {$s['notes']}\n";
            if (!empty($s['government_system'])) $knowledge_context .= "  🏛 سامانه: {$s['government_system']}\n";
            $knowledge_context .= "\n";
        }
    }
}

try {
    if (!$conversation_id) {
        $title = mb_substr($message, 0, 50);
        $stmt = $db->prepare("INSERT INTO conversations (user_id, title) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title]);
        $conversation_id = $db->lastInsertId();
    } else {
        $stmt = $db->prepare("SELECT id FROM conversations WHERE id = ? AND user_id = ?");
        $stmt->execute([$conversation_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'دسترسی غیرمجاز']);
            exit;
        }
    }
    
    // Save user message
    $stmt = $db->prepare("INSERT INTO messages (conversation_id, user_id, role, content) VALUES (?, ?, 'user', ?)");
    $stmt->execute([$conversation_id, $_SESSION['user_id'], $message]);
    
    // Get history
    $stmt = $db->prepare("SELECT role, content FROM messages WHERE conversation_id = ? ORDER BY created_at ASC LIMIT 10");
    $stmt->execute([$conversation_id]);
    $history = $stmt->fetchAll();
    
    // اضافه کردن Knowledge به پیام
    $ai_message = $message;
    if (!empty($knowledge_context)) {
        $ai_message = $message . "\n\n" . $knowledge_context . "\nلطفاً بر اساس اطلاعات بالا پاسخ دقیق بده.";
    }
    
    // Send to AI
    require_once __DIR__ . '/DeepSeekAPI.php';
    $ai = new DeepSeekAPI();
    $response = $ai->sendMessage($ai_message, $history, $model, [
        'think' => $think,
        'search' => $search
    ]);
    
    $response_text = $response['content'];
    $tokens_used = $response['tokens_used'];
    
    // Save assistant response
    $stmt = $db->prepare("INSERT INTO messages (conversation_id, user_id, role, content, tokens_used) VALUES (?, ?, 'assistant', ?, ?)");
    $stmt->execute([$conversation_id, $_SESSION['user_id'], $response_text, $tokens_used]);
    
    // Update conversation
    $stmt = $db->prepare("UPDATE conversations SET message_count = message_count + 2, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$conversation_id]);
    
    // Deduct credits
    $credits_to_deduct = max(1, min(ceil($tokens_used / 100), 10));
    $auth->updateCredits($_SESSION['user_id'], -$credits_to_deduct);
    
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversation_id,
        'message' => $response_text,
        'credits_remaining' => $_SESSION['credits'] - $credits_to_deduct,
        'kb_matches' => count($matched ?? [])
    ]);
    
} catch (Exception $e) {
    error_log("Chat Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'خطا در پردازش. لطفاً دوباره تلاش کنید.']);
}