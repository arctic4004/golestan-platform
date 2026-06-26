<?php
// projects/view.php - نسخه کامل با قابلیت ویرایش کد توسط AI
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';
requireAuth();

$id = $_GET['id'] ?? 0;
$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT * FROM github_projects WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$project = $stmt->fetch();

if (!$project) {
    header("Location: /projects/");
    exit();
}

$analysis = '';
$edited_code = '';
$action_result = '';

// =============================================
// دریافت ساختار فایل‌های ریپازیتوری
// =============================================
function getRepoFiles($url, $token = null) {
    // تبدیل URL به API URL
    $parsed = parse_url($url);
    $path = trim($parsed['path'] ?? '', '/');
    $parts = explode('/', $path);
    
    if (count($parts) >= 2) {
        $owner = $parts[0];
        $repo = $parts[1];
        $api_url = "https://api.github.com/repos/{$owner}/{$repo}/contents";
        
        $ch = curl_init($api_url);
        $headers = ['User-Agent: GolestanAI'];
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
    }
    return [];
}

$repo_files = [];
$selected_file = $_POST['file'] ?? $_GET['file'] ?? '';
$file_content = '';

if ($selected_file) {
    $parsed = parse_url($project['repo_url']);
    $path = trim($parsed['path'] ?? '', '/');
    $parts = explode('/', $path);
    
    if (count($parts) >= 2) {
        $owner = $parts[0];
        $repo = $parts[1];
        $api_url = "https://api.github.com/repos/{$owner}/{$repo}/contents/{$selected_file}";
        
        $ch = curl_init($api_url);
        $headers = ['User-Agent: GolestanAI'];
        if ($project['access_token']) $headers[] = 'Authorization: Bearer ' . $project['access_token'];
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $file_data = json_decode($response, true);
            $file_content = base64_decode($file_data['content'] ?? '');
        }
    }
} else {
    $repo_files = getRepoFiles($project['repo_url'], $project['access_token']);
}

// =============================================
// تحلیل پروژه
// =============================================
if (isset($_POST['analyze'])) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/api/chat/DeepSeekAPI.php';
    $ai = new DeepSeekAPI();
    
    $prompt = "Analyze this GitHub repository: {$project['repo_name']}\nURL: {$project['repo_url']}\nDescription: {$project['description']}\n\nProvide a comprehensive analysis in Persian (فارسی) including:\n1) Project overview\n2) Technologies likely used\n3) Architecture suggestions\n4) Potential improvements\n5) Security recommendations";
    
    $response = $ai->sendMessage($prompt);
    $analysis = $response['content'];
}

// =============================================
// ویرایش کد با AI
// =============================================
if (isset($_POST['edit_code']) && !empty($file_content)) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/api/chat/DeepSeekAPI.php';
    $ai = new DeepSeekAPI();
    
    $instruction = $_POST['instruction'] ?? 'improve this code';
    
    $prompt = "You are an expert programmer. Here is a file from a GitHub repository.\n\nFile: {$selected_file}\nCurrent code:\n```\n{$file_content}\n```\n\nUser instruction: {$instruction}\n\nPlease provide ONLY the improved/edited code in a code block. Explain your changes briefly in Persian (فارسی).";
    
    $response = $ai->sendMessage($prompt);
    $action_result = $response['content'];
    
    // استخراج کد از پاسخ
    preg_match('/```(?:\w+)?\n?(.*?)```/s', $action_result, $matches);
    if (!empty($matches[1])) {
        $edited_code = trim($matches[1]);
    }
}

// =============================================
// توضیح کد
// =============================================
if (isset($_POST['explain_code']) && !empty($file_content)) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/api/chat/DeepSeekAPI.php';
    $ai = new DeepSeekAPI();
    
    $prompt = "Explain this code in Persian (فارسی). File: {$selected_file}\n\n```\n{$file_content}\n```\n\nExplain:\n1) What this code does\n2) Key functions and logic\n3) Potential issues or improvements";
    
    $response = $ai->sendMessage($prompt);
    $action_result = $response['content'];
}

// =============================================
// رفع باگ
// =============================================
if (isset($_POST['fix_bugs']) && !empty($file_content)) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/api/chat/DeepSeekAPI.php';
    $ai = new DeepSeekAPI();
    
    $prompt = "Find and fix all bugs in this code. File: {$selected_file}\n\n```\n{$file_content}\n```\n\nProvide:\n1) List of bugs found (in Persian)\n2) Fixed code in a code block";
    
    $response = $ai->sendMessage($prompt);
    $action_result = $response['content'];
    
    preg_match('/```(?:\w+)?\n?(.*?)```/s', $action_result, $matches);
    if (!empty($matches[1])) {
        $edited_code = trim($matches[1]);
    }
}

$page_title = $project['repo_name'] . ' | پروژه‌ها | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.view-page { max-width: 1100px; margin: 100px auto 40px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-bottom: 20px; }
.card h3 { font-size: 1.1rem; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.file-list { max-height: 400px; overflow-y: auto; }
.file-list a { display: block; padding: 6px 8px; border-radius: 6px; color: var(--text-secondary); font-size: 0.85rem; font-family: monospace; direction: ltr; text-align: left; }
.file-list a:hover { background: var(--bg-hover); color: var(--primary); }
.code-editor { background: #1e1e2e; color: #e0e0e0; border-radius: 12px; padding: 16px; font-family: 'Fira Code', monospace; font-size: 0.85rem; line-height: 1.6; overflow-x: auto; max-height: 500px; overflow-y: auto; direction: ltr; text-align: left; white-space: pre-wrap; }
.code-editor:focus { outline: 2px solid var(--primary); }
.result-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 24px; margin-top: 20px; line-height: 1.8; white-space: pre-wrap; }
.btn-group { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.action-btn { padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-primary); cursor: pointer; font-family: var(--font); font-size: 0.85rem; transition: all 0.2s; }
.action-btn:hover { background: var(--primary); color: white; border-color: var(--primary); }
.action-btn.active { background: var(--primary); color: white; }
.badge-lang { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; background: var(--primary-light); color: var(--primary); }
@media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }
</style>

<div class="container view-page">
    <a href="/projects/" style="color:var(--text-muted);">← بازگشت به پروژه‌ها</a>
    
    <!-- هدر پروژه -->
    <div class="card">
        <h1>📁 <?=sanitize($project['repo_name'])?></h1>
        <p style="color:var(--text-secondary);"><?=sanitize($project['description'] ?: 'بدون توضیح')?></p>
        <a href="<?=$project['repo_url']?>" target="_blank" class="btn btn-outline btn-sm">🔗 گیت‌هاب</a>
        
        <form method="POST" style="display:inline;margin-right:8px;">
            <button type="submit" name="analyze" class="btn btn-primary btn-sm">🤖 تحلیل کامل پروژه</button>
        </form>
    </div>

    <?php if ($analysis): ?>
    <div class="card">
        <h3>📊 تحلیل هوش مصنوعی</h3>
        <div style="white-space:pre-wrap;line-height:1.8;"><?=nl2br(sanitize($analysis))?></div>
    </div>
    <?php endif; ?>

    <!-- بخش فایل‌ها و ویرایش -->
    <div class="grid-2">
        <!-- لیست فایل‌ها -->
        <div class="card">
            <h3>📂 فایل‌های پروژه</h3>
            <div class="file-list">
                <?php if (empty($repo_files)): ?>
                    <p style="color:var(--text-muted);">فایلی یافت نشد. یک فایل را انتخاب کنید.</p>
                    <form method="POST">
                        <input type="text" name="file" placeholder="مسیر فایل: src/index.js" style="width:100%;padding:8px;border:1px solid var(--border);border-radius:6px;font-family:monospace;direction:ltr;">
                        <button type="submit" class="btn btn-primary btn-sm mt-2">📂 باز کردن</button>
                    </form>
                <?php else: ?>
                    <?php foreach ($repo_files as $file): ?>
                        <?php if ($file['type'] === 'file'): ?>
                        <a href="?id=<?=$id?>&file=<?=urlencode($file['path'])?>">
                            📄 <?=sanitize($file['name'])?>
                            <span class="badge-lang"><?=round($file['size']/1024,1)?>KB</span>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- نمایش و ویرایش کد -->
        <div class="card">
            <h3>💻 <?=sanitize($selected_file ?: 'انتخاب فایل')?></h3>
            
            <?php if ($selected_file && $file_content): ?>
                <div class="code-editor" id="codeEditor" contenteditable="false"><?=htmlspecialchars($file_content)?></div>
                
                <div class="btn-group">
                    <form method="POST" style="display:contents;">
                        <input type="hidden" name="file" value="<?=htmlspecialchars($selected_file)?>">
                        <button type="submit" name="explain_code" class="action-btn">📖 توضیح کد</button>
                        <button type="submit" name="fix_bugs" class="action-btn">🐛 رفع باگ</button>
                    </form>
                </div>
                
                <!-- ویرایش با AI -->
                <form method="POST" style="margin-top:12px;">
                    <input type="hidden" name="file" value="<?=htmlspecialchars($selected_file)?>">
                    <textarea name="instruction" rows="2" placeholder="دستور ویرایش: مثلاً add error handling, optimize loops, convert to async..." style="width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;font-family:var(--font);"></textarea>
                    <button type="submit" name="edit_code" class="btn btn-primary btn-sm mt-2">✏️ ویرایش با AI</button>
                </form>
                
                <?php if ($edited_code): ?>
                <h4 style="margin-top:16px;">✅ کد ویرایش شده:</h4>
                <div class="code-editor" style="border:2px solid #4caf50;"><?=htmlspecialchars($edited_code)?></div>
                <button onclick="copyToClipboard('<?=htmlspecialchars(addslashes($edited_code))?>')" class="btn btn-outline btn-sm mt-2">📋 کپی کد</button>
                <button onclick="document.getElementById('codeEditor').textContent = document.querySelector('.code-editor[style*=\"#4caf50\"]').textContent" class="btn btn-outline btn-sm mt-2">🔄 جایگزینی</button>
                <?php endif; ?>
                
            <?php elseif ($selected_file): ?>
                <p style="color:#f44336;">❌ فایل یافت نشد یا خطا در دریافت محتوا</p>
            <?php else: ?>
                <p style="color:var(--text-muted);text-align:center;padding:40px;">👈 یک فایل را از لیست انتخاب کنید</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($action_result && !$edited_code): ?>
    <div class="result-box">
        <h3>📝 نتیجه</h3>
        <div><?=nl2br(sanitize($action_result))?></div>
    </div>
    <?php endif; ?>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => alert('✅ کد کپی شد!'));
}
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>