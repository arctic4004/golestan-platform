<?php
// projects/chat.php - چت با کد پروژه
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

// دریافت ساختار فایل‌ها برای context
function getAllFiles($url, $token = null, $path = '') {
    $parsed = parse_url($url);
    $repo_path = trim($parsed['path'] ?? '', '/');
    $parts = explode('/', $repo_path);
    
    if (count($parts) < 2) return [];
    
    $owner = $parts[0];
    $repo = $parts[1];
    $api_url = "https://api.github.com/repos/{$owner}/{$repo}/contents/" . $path;
    
    $ch = curl_init($api_url);
    $headers = ['User-Agent: GolestanAI'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 15]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $files = json_decode($response, true);
    if (!is_array($files)) return [];
    
    $result = [];
    foreach ($files as $file) {
        if ($file['type'] === 'file' && $file['size'] < 50000) {
            $content = base64_decode($file['content'] ?? '');
            $result[] = "File: {$file['path']}\n```\n" . substr($content, 0, 2000) . "\n```\n";
        }
    }
    return $result;
}

$repo_context = getAllFiles($project['repo_url'], $project['access_token']);
$context_text = implode("\n", array_slice($repo_context, 0, 10));

// پردازش پیام
$conversation = $_SESSION['project_chat_' . $id] ?? [];
$reply = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $user_msg = trim($_POST['message']);
    $conversation[] = ['role' => 'user', 'content' => $user_msg];
    
    require_once $_SERVER['DOCUMENT_ROOT'] . '/api/chat/DeepSeekAPI.php';
    try {
        $ai = new DeepSeekAPI();
        $system = "You are an AI code assistant. Project: {$project['repo_name']}\nDescription: {$project['description']}\n\nRepository files:\n{$context_text}\n\nAnswer in Persian (فارسی). Help with code analysis, debugging, optimization, and development.";
        
        $full_conversation = array_merge(
            [['role' => 'system', 'content' => $system]],
            $conversation
        );
        
        $response = $ai->sendMessage($user_msg, $full_conversation);
        $reply = $response['content'];
        $conversation[] = ['role' => 'assistant', 'content' => $reply];
    } catch (Exception $e) {
        $reply = 'متأسفانه خطایی رخ داد. 😔';
    }
    
    $_SESSION['project_chat_' . $id] = $conversation;
}

$page_title = 'چت با کد | ' . $project['repo_name'] . ' | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.chat-page { max-width: 800px; margin: 100px auto 40px; }
.chat-header-bar { background: linear-gradient(135deg, #0d1117, #161b22); color: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; }
.chat-header-bar h1 { font-size: 1.3rem; }
.chat-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; min-height: 400px; max-height: 500px; overflow-y: auto; margin-bottom: 16px; }
.chat-msg { margin-bottom: 16px; display: flex; gap: 10px; }
.chat-msg.user { flex-direction: row-reverse; }
.chat-msg .avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; flex-shrink: 0; }
.chat-msg.user .avatar { background: var(--primary); color: white; }
.chat-msg.assistant .avatar { background: var(--bg-tertiary); color: var(--primary); }
.chat-msg .bubble { padding: 10px 16px; border-radius: 16px; max-width: 80%; line-height: 1.7; font-size: 0.9rem; white-space: pre-wrap; }
.chat-msg.user .bubble { background: var(--primary); color: white; border-bottom-right-radius: 4px; }
.chat-msg.assistant .bubble { background: var(--bg-secondary); border: 1px solid var(--border); border-bottom-left-radius: 4px; }
.chat-input-area { display: flex; gap: 10px; }
.chat-input-area input { flex: 1; padding: 14px; border: 1px solid var(--border); border-radius: 12px; font-family: var(--font); }
</style>

<div class="container chat-page">
    <div class="chat-header-bar">
        <a href="/projects/" style="color:rgba(255,255,255,0.7);font-size:0.85rem;">← بازگشت</a>
        <h1>💬 چت با کد: <?=sanitize($project['repo_name'])?></h1>
        <p style="opacity:0.8;font-size:0.85rem;">از AI درباره کد پروژه بپرسید - تحلیل، دیباگ، بهینه‌سازی</p>
    </div>
    
    <div class="chat-box" id="chatBox">
        <?php if (empty($conversation)): ?>
        <div style="text-align:center;padding:60px;color:var(--text-muted);">
            <i class="fas fa-robot" style="font-size:3rem;margin-bottom:12px;display:block;"></i>
            <p>از من درباره کد پروژه بپرسید!</p>
            <p style="font-size:0.85rem;">مثلاً: "این پروژه چطور کار میکنه؟" یا "کد بخش لاگین رو بهینه کن"</p>
        </div>
        <?php else: ?>
        <?php foreach ($conversation as $msg): ?>
        <?php if ($msg['role'] !== 'system'): ?>
        <div class="chat-msg <?=$msg['role']?>">
            <div class="avatar"><i class="fas fa-<?=$msg['role']=='user'?'user':'robot'?>"></i></div>
            <div class="bubble"><?=nl2br(sanitize($msg['content']))?></div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <form method="POST" class="chat-input-area">
        <input type="text" name="message" placeholder="درباره کد پروژه بپرسید..." required autofocus>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
    </form>
    <a href="?id=<?=$id?>&clear=1" style="color:#f44336;font-size:0.85rem;margin-top:8px;display:inline-block;">🗑️ پاک کردن گفتگو</a>
</div>

<?php
if (isset($_GET['clear'])) {
    unset($_SESSION['project_chat_' . $id]);
    header("Location: /projects/chat.php?id=" . $id);
    exit();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>