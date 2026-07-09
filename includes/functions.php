<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    if (!empty($_SESSION['user_id'])) return true;
    
    if (!empty($_COOKIE['golestan_user'])) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([intval($_COOKIE['golestan_user'])]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['credits'] = $user['credits'];
            $_SESSION['wallet_balance'] = $user['wallet_balance'] ?? 0;
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['theme'] = $user['theme'] ?? 'light';
            $_SESSION['rank'] = $user['rank'] ?? 'bronze';
            $_SESSION['rank_score'] = $user['rank_score'] ?? 0;
            return true;
        }
    }
    return false;
}

function isAdmin() {
    return !empty($_SESSION['is_admin']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function getUserData($user_id) {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function logActivity($user_id, $action, $description = '') {
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch (Exception $e) {}
}

function getConversations($user_id, $limit = 10) {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT c.*, 
        (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM conversations c WHERE c.user_id = ? ORDER BY c.updated_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'لحظاتی پیش';
    if ($diff < 3600) return floor($diff / 60) . ' دقیقه قبل';
    if ($diff < 86400) return floor($diff / 3600) . ' ساعت قبل';
    return date('Y/m/d', strtotime($datetime));
}

// 🌞 تاریخ شمسی
function jalali_date($format = 'Y/m/d', $timestamp = null) {
    if ($timestamp === null) $timestamp = time();
    $g = date('Y,m,d', $timestamp);
    $g = explode(',', $g);
    $gy = (int)$g[0]; $gm = (int)$g[1]; $gd = (int)$g[2];
    
    $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * ((int)($days / 12053)));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) { $jy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
    if ($days < 186) { $jm = 1 + (int)($days / 31); $jd = 1 + ($days % 31); }
    else { $jm = 7 + (int)(($days - 186) / 30); $jd = 1 + (($days - 186) % 30); }
    
    $format = str_replace('Y', $jy, $format);
    $format = str_replace('m', str_pad($jm, 2, '0', STR_PAD_LEFT), $format);
    $format = str_replace('d', str_pad($jd, 2, '0', STR_PAD_LEFT), $format);
    return $format;
}

function jalali_time() {
    return jalali_date('Y/m/d H:i');
}

// 🌟 نمایش رنک با مدال
function rank_badge($rank, $score = 0) {
    $ranks = [
        'diamond' => ['icon' => '👑', 'name' => 'الماس', 'color' => '#0e7490', 'bg' => '#ecfeff'],
        'platinum' => ['icon' => '💎', 'name' => 'پلاتینیوم', 'color' => '#6d28d9', 'bg' => '#f5f3ff'],
        'gold' => ['icon' => '🥇', 'name' => 'طلایی', 'color' => '#a16207', 'bg' => '#fef9c3'],
        'silver' => ['icon' => '🥈', 'name' => 'نقره‌ای', 'color' => '#475569', 'bg' => '#f1f5f9'],
        'bronze' => ['icon' => '🥉', 'name' => 'برنزی', 'color' => '#92400e', 'bg' => '#fef3c7'],
    ];
    $r = $ranks[$rank] ?? $ranks['bronze'];
    return "<span class='rank-badge' style='background:{$r['bg']};color:{$r['color']};padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:4px'>
        {$r['icon']} {$r['name']} " . ($score ? "<small>({$score})</small>" : "") . "
    </span>";
}

// 🔔 ارسال نوتیفیکیشن تلگرام
function sendTelegram($message) {
    $bot_token = @file_get_contents(__DIR__ . '/telegram_token.txt');
    $chat_id = @file_get_contents(__DIR__ . '/telegram_chat_id.txt');
    if (empty($bot_token) || empty($chat_id)) return false;
    
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $message, 'parse_mode' => 'HTML'];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_TIMEOUT => 10
    ]);
    curl_exec($ch);
    curl_close($ch);
    return true;
}