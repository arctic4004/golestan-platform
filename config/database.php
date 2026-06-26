<?php
// config/database.php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset;
    public $conn;

    public function __construct() {
        // 🔴 این ۴ خط رو با اطلاعات واقعی cPanel خودت آپدیت کن
        $this->host = 'localhost';
        $this->db_name = 'golestanyasujir_golestanyasujir_chat';        // ← اسم کامل دیتابیس از cPanel
        $this->username = 'golestanyasujir_golestanyasujir_golestan';   // ← اسم کامل کاربر از cPanel  
        $this->password = 'Aa2120917@';             // ← پسورد جدید که ساختی
        $this->charset = 'utf8mb4';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            // نمایش خطا برای دیباگ
            die("❌ Database Connection Error: " . $e->getMessage() . 
                "<br><br>📝 Debug Info:" .
                "<br>Host: {$this->host}" .
                "<br>Database: {$this->db_name}" .
                "<br>Username: {$this->username}" .
                "<br><br>💡 مطمئن شو:" .
                "<br>۱. دیتابیس و کاربر در cPanel ساخته شده باشن" .
                "<br>۲. کاربر به دیتابیس دسترسی داشته باشه (ALL PRIVILEGES)" .
                "<br>۳. پسورد درست باشه");
        }
        return $this->conn;
    }
}