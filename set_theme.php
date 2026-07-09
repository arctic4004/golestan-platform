<?php
// set_theme.php
session_start();
require_once 'config/database.php';

// فقط درخواست POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$color = $_POST['theme_color'] ?? null;
$mode  = $_POST['theme_mode'] ?? null;

$allowed_colors = ['sapphire', 'emerald', 'ruby', 'amber', 'amethyst', 'teal', 'rose', 'indigo', 'cyan'];
$allowed_modes  = ['light', 'dark'];

$updated = false;

// ذخیره در سشن
if ($color && in_array($color, $allowed_colors)) {
    $_SESSION['theme_color'] = $color;
    $updated = true;
}
if ($mode && in_array($mode, $allowed_modes)) {
    $_SESSION['theme_mode'] = $mode;
    $updated = true;
}

// ذخیره در دیتابیس (اگر کاربر لاگین کرده و ستون‌ها موجود باشند)
if (isset($_SESSION['user_id']) && $updated) {
    try {
        $db = (new Database())->getConnection();
        
        $setClauses = [];
        $params = [];
        if ($color && in_array($color, $allowed_colors)) {
            $setClauses[] = 'theme_color = ?';
            $params[] = $color;
        }
        if ($mode && in_array($mode, $allowed_modes)) {
            $setClauses[] = 'theme_mode = ?';
            $params[] = $mode;
        }
        $params[] = $_SESSION['user_id'];
        
        if (!empty($setClauses)) {
            $sql = "UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
        }
    } catch (Exception $e) {
        // اگر جدول یا ستون‌ها وجود نداشتند، فقط سشن کافی است
        error_log('Theme save error: ' . $e->getMessage());
    }
}

echo json_encode(['success' => $updated]);