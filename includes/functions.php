<?php
/**
 * توابع هسته و ابزارهای کمکی کافی‌نت گلستان
 * نسخه ۲.۰ - بهینه‌شده برای موبایل و امنیت بالا
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// =============================================
// امنیت خروجی
// =============================================
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// =============================================
// توکن امنیتی CSRF
// =============================================
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// =============================================
// هدایت (Redirect)
// =============================================
function redirect($url) {
    if (strpos($url, 'http') !== 0) {
        $url = SITE_URL . $url;
    }
    header("Location: " . $url);
    exit();
}

// =============================================
// احراز هویت (با پشتیبانی از کوکی برای موبایل)
// =============================================
function isLoggedIn() {
    // بررسی سشن فعلی
    if (!empty($_SESSION['user_id'])) return true;

    // بازیابی از کوکی (مناسب موبایل که سشن زودتر منقضی می‌شود)
    if (!empty($_COOKIE['golestan_user']) && !empty($_COOKIE['golestan_token'])) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            $stmt = $db->prepare("SELECT id, full_name, phone, credits, is_admin, theme FROM users WHERE id = ? AND is_active = 1");
            $stmt->execute([intval($_COOKIE['golestan_user'])]);
            $user = $stmt->fetch();

            if ($user && hash_equals(md5($user['id'] . 'golestan_salt_2024'), $_COOKIE['golestan_token'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['full_name']  = $user['full_name'];
                $_SESSION['phone']      = $user['phone'];
                $_SESSION['credits']    = $user['credits'];
                $_SESSION['is_admin']   = (bool)$user['is_admin'];
                $_SESSION['theme']      = $user['theme'] ?? 'light';
                return true;
            }
        } catch (Exception $e) {
            error_log("Cookie auth error: " . $e->getMessage());
        }
    }
    return false;
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function requireAuth() {
    if (!isLoggedIn()) {
        redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        redirect('/user/dashboard/v2/');
    }
}

// =============================================
// اطلاعات کاربر
// =============================================
function getUserData($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("SELECT id, phone, email, full_name, avatar, bio, theme, credits, is_admin, created_at, last_login FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function updateLastActivity($user_id) {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("UPDATE users SET last_login = NOW(), login_count = login_count + 1, ip_address = ? WHERE id = ?");
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $user_id]);
}

// =============================================
// ثبت رویدادها
// =============================================
function logActivity($user_id, $action, $description = '') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id, $action,
            mb_substr($description, 0, 255),
            $_SERVER['REMOTE_ADDR'] ?? '',
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ]);
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
    }
}

// =============================================
// تم و پیام‌های فلش
// =============================================
function getTheme() {
    return $_SESSION['theme'] ?? 'light';
}

function flashMessage($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function displayFlash() {
    if (isset($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $message) {
            echo "<div class='alert alert-{$type}' role='alert'>{$message}</div>";
        }
        unset($_SESSION['flash']);
    }
}

// =============================================
// گفتگوها (چت‌ها)
// =============================================
function getConversations($user_id, $limit = 10) {
    $database = new Database();
    $db = $database->getConnection();
    $stmt = $db->prepare("
        SELECT c.*, 
               (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time
        FROM conversations c 
        WHERE c.user_id = ? AND c.status = 'active' 
        ORDER BY c.updated_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

// =============================================
// وضعیت اعتبار
// =============================================
function getCreditStatus($credits) {
    if ($credits > 500) return ['class' => 'success', 'text' => 'عالی'];
    if ($credits > 100) return ['class' => 'warning', 'text' => 'متوسط'];
    return ['class' => 'danger', 'text' => 'پایین'];
}

// =============================================
// ابزارهای کمکی SEO و زمان
// =============================================
function getPageTitle($title = '') {
    return $title ? $title . ' | ' . SITE_NAME : SITE_NAME;
}

function getTruncatedText($text, $length = 160) {
    $text = strip_tags($text);
    return mb_strlen($text) > $length ? mb_substr($text, 0, $length) . '...' : $text;
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'لحظاتی پیش';
    if ($diff < 3600) return floor($diff / 60) . ' دقیقه قبل';
    if ($diff < 86400) return floor($diff / 3600) . ' ساعت قبل';
    if ($diff < 604800) return floor($diff / 86400) . ' روز قبل';
    return date('Y/m/d', $time);
}