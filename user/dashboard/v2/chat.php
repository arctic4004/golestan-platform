<?php
// user/dashboard/v2/chat.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "/login.php?redirect=/user/dashboard/v2/chat.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$active_conversation_id = $_GET['conversation'] ?? null;
$initial_message = $_GET['msg'] ?? null;

// چت جدید (کلیک روی دکمه "چت جدید")
if (isset($_GET['new']) && !$active_conversation_id) {
    $active_conversation_id = null; // صفحه خوش‌آمد بدون متن پیشنهادی
}

if ($initial_message && !$active_conversation_id) {
    $title = mb_substr($initial_message, 0, 50);
    $stmt = $db->prepare("INSERT INTO conversations (user_id, title) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $title]);
    $active_conversation_id = $db->lastInsertId();
    
    $stmt = $db->prepare("INSERT INTO messages (conversation_id, user_id, role, content) VALUES (?, ?, 'user', ?)");
    $stmt->execute([$active_conversation_id, $_SESSION['user_id'], $initial_message]);
    
    require_once $_SERVER['DOCUMENT_ROOT'] . '/api/chat/DeepSeekAPI.php';
    try {
        $ai = new DeepSeekAPI();
        $response = $ai->sendMessage($initial_message);
        $stmt = $db->prepare("INSERT INTO messages (conversation_id, user_id, role, content, tokens_used) VALUES (?, ?, 'assistant', ?, ?)");
        $stmt->execute([$active_conversation_id, $_SESSION['user_id'], $response['content'], $response['tokens_used'] ?? 0]);
        
        $credits = max(1, ceil(($response['tokens_used'] ?? 100) / 100));
        $stmt = $db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?");
        $stmt->execute([$credits, $_SESSION['user_id']]);
        $_SESSION['credits'] = ($_SESSION['credits'] ?? 1000) - $credits;
    } catch (Exception $e) {
        $stmt = $db->prepare("INSERT INTO messages (conversation_id, user_id, role, content) VALUES (?, ?, 'assistant', ?)");
        $stmt->execute([$active_conversation_id, $_SESSION['user_id'], 'متأسفانه خطایی رخ داد.']);
    }
    
    $stmt = $db->prepare("UPDATE conversations SET message_count = message_count + 2, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$active_conversation_id]);
}

$hide_footer = true;
$page_title = 'چت هوشمند | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$user = getUserData($_SESSION['user_id']);
$conversations = getConversations($_SESSION['user_id'], 20);

$messages = [];
if ($active_conversation_id) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC");
    $stmt->execute([$active_conversation_id]);
    $messages = $stmt->fetchAll();
}

function formatMessage($text) {
    $text = htmlspecialchars($text);
    $text = preg_replace('/```(\w*)\n?(.*?)```/s', '<div class="code-block"><div class="code-header"><span>$1</span><button onclick="copyCode(this)" class="copy-btn"><i class="fas fa-copy"></i> کپی</button></div><pre><code>$2</code></pre></div>', $text);
    $text = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = nl2br($text);
    return $text;
}
?>

<div class="dashboard-container chat-layout">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?php echo mb_substr($user['full_name'] ?? 'U', 0, 1); ?></div>
            <h3><?php echo sanitize($user['full_name'] ?? 'کاربر'); ?></h3>
            <span class="user-phone"><?php echo $user['phone'] ?? ''; ?></span>
        </div>
        
        <a href="?new=1" class="btn btn-primary btn-block" style="margin-bottom:12px;">
            <i class="fas fa-plus"></i> چت جدید
        </a>
        
        <nav class="dashboard-nav" style="flex:1;overflow-y:auto;">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="fas fa-home"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item active"><i class="fas fa-comments"></i> چت AI</a>
            <a href="/projects/" class="nav-item"><i class="fab fa-github"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="fas fa-image"></i> ساخت عکس</a>
            <a href="/user/dashboard/v2/tools.php" class="nav-item"><i class="fas fa-tools"></i> ابزارها</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="fas fa-tasks"></i> تسک‌ها</a>
            <a href="/shop/" class="nav-item"><i class="fas fa-store"></i> فروشگاه</a>
            <a href="/user/dashboard/v2/history.php" class="nav-item"><i class="fas fa-history"></i> تاریخچه</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="fas fa-user"></i> پروفایل</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="fas fa-sign-out-alt"></i> خروج</a>
        </nav>
        
        <div style="margin-top:12px;border-top:1px solid var(--border);padding-top:12px;">
            <p style="font-size:0.7rem;color:var(--text-muted);margin-bottom:8px;">چت‌های اخیر</p>
            <?php foreach (array_slice($conversations, 0, 10) as $c): ?>
                <a href="?conversation=<?php echo $c['id']; ?>" class="recent-chat-link <?php echo $active_conversation_id == $c['id'] ? 'active' : ''; ?>">
                    <?php echo mb_substr(sanitize($c['title']), 0, 25); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </aside>
    
    <main class="chat-main">
        <?php if ($active_conversation_id): ?>
            <div class="chat-header">
                <button class="btn btn-ghost btn-icon sidebar-toggle" onclick="toggleDashboardSidebar()" title="منو">
                    <i class="fas fa-bars"></i>
                </button>
                <h2><?php 
                    $active_conv = array_filter($conversations, fn($c) => $c['id'] == $active_conversation_id);
                    echo sanitize(current($active_conv)['title'] ?? 'چت');
                ?></h2>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['role']; ?>">
                        <div class="message-avatar"><i class="fas fa-<?php echo $msg['role'] === 'user' ? 'user' : 'robot'; ?>"></i></div>
                        <div class="message-body">
                            <div class="message-bubble">
                                <div class="message-content"><?php echo formatMessage($msg['content']); ?></div>
                            </div>
                            <div class="message-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <div class="input-options">
                        <button class="input-option-btn" id="thinkBtn"><i class="fas fa-brain"></i> Think</button>
                        <button class="input-option-btn" id="searchBtn"><i class="fas fa-search"></i> Search</button>
                    </div>
                    <div class="input-row">
                        <textarea id="messageInput" placeholder="پیام خود را بنویسید..." rows="1" oninput="autoResize(this)"></textarea>
                        <button type="button" class="send-btn" onclick="sendMessage()"><i class="fas fa-arrow-up"></i></button>
                    </div>
                </div>
                <div class="input-footer">
                    <span>Llama AI</span>
                    <span>اعتبار: <?php echo number_format($_SESSION['credits'] ?? 0); ?></span>
                </div>
            </div>
        <?php else: ?>
            <!-- صفحه خوش‌آمد ساده - بدون متن‌های پیشنهادی -->
            <div class="chat-welcome">
                <div class="welcome-icon"><i class="fas fa-robot"></i></div>
                <h1>به چت هوشمند خوش آمدید</h1>
                <p>اولین پیام خود را بنویسید تا گفتگو شروع شود</p>
                
                <!-- فقط یک فیلد متنی ساده برای شروع -->
                <form method="GET" action="/user/dashboard/v2/chat.php" style="margin-top:20px;width:100%;max-width:500px;display:flex;gap:8px;">
                    <input type="text" name="msg" placeholder="پیام خود را بنویسید..." 
                           style="flex:1;padding:14px;border:1px solid var(--border);border-radius:12px;font-family:var(--font);font-size:1rem;background:var(--bg-input);color:var(--text-primary);">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>

<script src="/user/dashboard/v2/assets/js/dashboard.js"></script>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>