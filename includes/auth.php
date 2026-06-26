<?php
// includes/auth.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $db;
    private $user = null;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            $this->loadUser($_SESSION['user_id']);
        }
    }
    
    private function loadUser($user_id) {
        $stmt = $this->db->prepare("
            SELECT id, phone, email, full_name, avatar, bio, theme, 
                   credits, is_admin, is_active, last_login, created_at
            FROM users 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$user_id]);
        $this->user = $stmt->fetch();
        
        if ($this->user) {
            $_SESSION['user_id'] = $this->user['id'];
            $_SESSION['full_name'] = $this->user['full_name'];
            $_SESSION['phone'] = $this->user['phone'];
            $_SESSION['credits'] = $this->user['credits'];
            $_SESSION['is_admin'] = (bool)$this->user['is_admin'];
            $_SESSION['theme'] = $this->user['theme'] ?? 'gold';
        }
    }
    
    public function login($phone, $password, $remember = false) {
        // Check rate limiting
        if ($this->isRateLimited($phone)) {
            throw new Exception('حساب کاربری موقتاً قفل شده است. لطفاً ۱۵ دقیقه صبر کنید.');
        }
        
        $stmt = $this->db->prepare("
            SELECT id, phone, full_name, password_hash, credits, 
                   is_admin, is_active, theme 
            FROM users 
            WHERE phone = ?
        ");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->logFailedAttempt($phone);
            throw new Exception('شماره موبایل یا رمز عبور اشتباه است.');
        }
        
        if (!$user['is_active']) {
            throw new Exception('حساب کاربری شما غیرفعال شده است.');
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['phone'] = $user['phone'];
        $_SESSION['credits'] = $user['credits'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        $_SESSION['theme'] = $user['theme'] ?? 'gold';
        
        // Remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
            
            $stmt = $this->db->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $user['id']]);
        }
        
        // Update last login
        $stmt = $this->db->prepare("
            UPDATE users 
            SET last_login = NOW(), 
                login_count = login_count + 1,
                ip_address = ? 
            WHERE id = ?
        ");
        $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]);
        
        $this->user = $user;
        logActivity($user['id'], 'login', 'ورود موفق به سیستم');
        
        return true;
    }
    
    public function register($phone, $full_name, $password) {
        // Validate
        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            throw new Exception('شماره موبایل نامعتبر است.');
        }
        
        if (strlen($password) < 6) {
            throw new Exception('رمز عبور باید حداقل ۶ کاراکتر باشد.');
        }
        
        // Check existing
        $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        
        if ($stmt->fetch()) {
            throw new Exception('این شماره موبایل قبلاً ثبت شده است.');
        }
        
        // Create user
        $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (phone, full_name, password_hash, credits, ip_address) 
            VALUES (?, ?, ?, 1000, ?)
        ");
        $stmt->execute([$phone, $full_name, $password_hash, $_SERVER['REMOTE_ADDR'] ?? '']);
        
        $user_id = $this->db->lastInsertId();
        
        // Auto login
        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['phone'] = $phone;
        $_SESSION['credits'] = 1000;
        $_SESSION['is_admin'] = false;
        $_SESSION['theme'] = 'gold';
        
        logActivity($user_id, 'register', 'ثبت‌نام کاربر جدید');
        
        return $user_id;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'logout', 'خروج از سیستم');
        }
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
    
    public function getUser() {
        return $this->user;
    }
    
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            redirect('/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        }
    }
    
    public function requireAdmin() {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            redirect('/user/dashboard/v2/');
        }
    }
    
    private function isRateLimited($phone) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as attempts 
            FROM activity_logs 
            WHERE action = 'login_failed' 
            AND description LIKE CONCAT('%', ?, '%')
            AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$phone]);
        $result = $stmt->fetch();
        
        return $result['attempts'] >= MAX_LOGIN_ATTEMPTS;
    }
    
    private function logFailedAttempt($phone) {
        logActivity(null, 'login_failed', "ورود ناموفق برای شماره {$phone}");
    }
    
    public function updateCredits($user_id, $amount) {
        $stmt = $this->db->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
        $stmt->execute([$amount, $user_id]);
        
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['credits'] += $amount;
        }
    }
    
    public function changePassword($user_id, $old_password, $new_password) {
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!password_verify($old_password, $user['password_hash'])) {
            throw new Exception('رمز عبور فعلی اشتباه است.');
        }
        
        $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $user_id]);
        
        logActivity($user_id, 'password_change', 'تغییر رمز عبور');
        return true;
    }
}

// Global instance
$auth = new Auth();