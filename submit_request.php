<?php
// submit_request.php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    flashMessage('error', 'خطای امنیتی. لطفاً دوباره تلاش کنید.');
    redirect('/#contact');
}

$full_name = sanitize($_POST['full_name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$service_type = sanitize($_POST['service_type'] ?? '');
$description = sanitize($_POST['description'] ?? '');

// Validation
if (empty($full_name) || empty($phone) || empty($service_type)) {
    flashMessage('error', 'لطفاً تمام فیلدهای الزامی را پر کنید.');
    redirect('/#contact');
}

if (!preg_match('/^09[0-9]{9}$/', $phone)) {
    flashMessage('error', 'شماره موبایل نامعتبر است.');
    redirect('/#contact');
}

$database = new Database();
$db = $database->getConnection();

try {
    // Check if user exists
    $user_id = null;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if ($user) {
            $user_id = $user['id'];
        } else {
            // Auto register
            $password_hash = password_hash($phone, PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (phone, full_name, password_hash, credits) VALUES (?, ?, ?, 1000)");
            $stmt->execute([$phone, $full_name, $password_hash]);
            $user_id = $db->lastInsertId();
            
            // Auto login
            $_SESSION['user_id'] = $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['phone'] = $phone;
            $_SESSION['credits'] = 1000;
            $_SESSION['is_admin'] = false;
            $_SESSION['theme'] = 'gold';
            
            logActivity($user_id, 'auto_register', 'ثبت‌نام خودکار از طریق فرم درخواست');
        }
    }
    
    // Insert service request
    $stmt = $db->prepare("
        INSERT INTO service_requests (user_id, full_name, phone, service_type, description, priority) 
        VALUES (?, ?, ?, ?, ?, 'medium')
    ");
    $stmt->execute([$user_id, $full_name, $phone, $service_type, $description]);
    $request_id = $db->lastInsertId();
    
    // Send email notification
    $to = ADMIN_EMAIL;
    $subject = "درخواست جدید خدمات - {$service_type}";
    $message = "
        <html>
        <head><title>درخواست جدید</title></head>
        <body>
            <h2>درخواست خدمات جدید</h2>
            <p><strong>نام:</strong> {$full_name}</p>
            <p><strong>شماره تماس:</strong> {$phone}</p>
            <p><strong>نوع خدمات:</strong> {$service_type}</p>
            <p><strong>توضیحات:</strong> {$description}</p>
            <p><strong>تاریخ:</strong> " . date('Y/m/d H:i') . "</p>
            <hr>
            <p>برای مدیریت درخواست‌ها به پنل مدیریت مراجعه کنید.</p>
            <a href='" . SITE_URL . "/admin/'>پنل مدیریت</a>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SITE_NAME . " <noreply@golestanyasuj.ir>\r\n";
    
    mail($to, $subject, $message, $headers);
    
    logActivity($user_id, 'service_request', "ثبت درخواست خدمات: {$service_type}");
    flashMessage('success', 'درخواست شما با موفقیت ثبت شد. به زودی با شما تماس می‌گیریم.');
    
} catch (Exception $e) {
    error_log("Service request error: " . $e->getMessage());
    flashMessage('error', 'خطا در ثبت درخواست. لطفاً دوباره تلاش کنید.');
}

redirect('/#contact');