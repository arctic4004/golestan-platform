<?php
// projects/connect.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
requireAuth();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $repo_url = $_POST['repo_url'] ?? '';
    $repo_name = $_POST['repo_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $access_token = $_POST['access_token'] ?? '';
    
    if (empty($repo_url) || empty($repo_name)) {
        $error = 'لطفاً نام و آدرس ریپازیتوری را وارد کنید.';
    } else {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("INSERT INTO github_projects (user_id, repo_name, repo_url, description, access_token) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $repo_name, $repo_url, $description, $access_token ?: null]);
        
        $success = '✅ پروژه با موفقیت متصل شد!';
    }
}

$page_title = 'اتصال پروژه | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.connect-page { max-width: 600px; margin: 100px auto 40px; }
</style>

<div class="container connect-page">
    <h1>➕ اتصال ریپازیتوری گیت‌هاب</h1>
    <p style="color:var(--text-secondary);margin-bottom:20px;">آدرس ریپازیتوری خود را وارد کنید تا برای تحلیل و توسعه با هوش مصنوعی متصل شود.</p>
    
    <?php if ($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><?=$error?></div><?php endif; ?>
    
    <form method="POST" style="background:var(--bg-card);border:1px solid var(--border);border-radius:16px;padding:24px;">
        <div class="form-group">
            <label>📁 نام ریپازیتوری</label>
            <input type="text" name="repo_name" placeholder="مثال: my-awesome-project" required>
        </div>
        <div class="form-group">
            <label>🔗 آدرس ریپازیتوری</label>
            <input type="url" name="repo_url" placeholder="https://github.com/username/repo" required>
        </div>
        <div class="form-group">
            <label>📝 توضیحات (اختیاری)</label>
            <textarea name="description" rows="2" placeholder="توضیح کوتاه درباره پروژه..." style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;"></textarea>
        </div>
        <div class="form-group">
            <label>🔑 Token دسترسی (اختیاری - برای ریپوهای خصوصی)</label>
            <input type="text" name="access_token" placeholder="github_pat_...">
            <small>از <a href="https://github.com/settings/tokens" target="_blank">اینجا</a> دریافت کنید</small>
        </div>
        <button type="submit" class="btn btn-primary">🔗 اتصال پروژه</button>
    </form>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>