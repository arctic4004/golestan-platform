<?php
// api/chat/send.php
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

// Rate limiting
$database = new Database();
$db = $database->getConnection();
$user = $auth->getUser();

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
    
    // Send to AI
    require_once __DIR__ . '/DeepSeekAPI.php';
    $ai = new DeepSeekAPI();
    $response = $ai->sendMessage($message, $history, $model, [
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
        'credits_remaining' => $_SESSION['credits'] - $credits_to_deduct
    ]);
    
} catch (Exception $e) {
    error_log("Chat Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'خطا در پردازش. لطفاً دوباره تلاش کنید.']);
}