<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "/login.php?redirect=/user/dashboard/v2/chat.php");
    exit;
}

$db = (new Database())->getConnection();
$active_id = $_GET['conversation'] ?? null;
$initial_msg = $_GET['msg'] ?? null;

if ($initial_msg && !$active_id) {
    $title = mb_substr($initial_msg, 0, 50);
    $db->prepare("INSERT INTO conversations (user_id, title) VALUES (?, ?)")->execute([$_SESSION['user_id'], $title]);
    $active_id = $db->lastInsertId();
    $db->prepare("INSERT INTO messages (conversation_id, user_id, role, content) VALUES (?, ?, 'user', ?)")->execute([$active_id, $_SESSION['user_id'], $initial_msg]);
    
    try {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/api/chat/DeepSeekAPI.php';
        $ai = new DeepSeekAPI();
        $response = $ai->sendMessage($initial_msg);
        $db->prepare("INSERT INTO messages (conversation_id, user_id, role, content, tokens_used) VALUES (?, ?, 'assistant', ?, ?)")->execute([$active_id, $_SESSION['user_id'], $response['content'], $response['tokens_used'] ?? 0]);
        $credits = max(1, ceil(($response['tokens_used'] ?? 100) / 100));
        $db->prepare("UPDATE users SET credits = credits - ? WHERE id = ?")->execute([$credits, $_SESSION['user_id']]);
        $_SESSION['credits'] = ($_SESSION['credits'] ?? 1000) - $credits;
    } catch (Exception $e) {
        $db->prepare("INSERT INTO messages (conversation_id, user_id, role, content) VALUES (?, ?, 'assistant', ?)")->execute([$active_id, $_SESSION['user_id'], '❌ خطایی رخ داد. دوباره تلاش کنید.']);
    }
    $db->prepare("UPDATE conversations SET message_count = message_count + 2, updated_at = NOW() WHERE id = ?")->execute([$active_id]);
}

$hide_footer = true;
$page_title = 'چت هوشمند | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
$extra_js = ['user/dashboard/v2/assets/js/dashboard.js'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$user = getUserData($_SESSION['user_id']);
$conversations = getConversations($_SESSION['user_id'], 20);

$messages = [];
if ($active_id) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC");
    $stmt->execute([$active_id]);
    $messages = $stmt->fetchAll();
}

function formatChatMessage($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/```(\w+)?\n?(.*?)```/s', '<div class="code-block"><div class="code-header"><span>$1</span><button class="copy-btn" onclick="copyCode(this)"><i class="ph ph-copy"></i> کپی</button></div><pre><code>$2</code></pre></div>', $text);
    $text = preg_replace('/`([^`]+)`/', '<code class="inline-code">$1</code>', $text);
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = nl2br($text);
    return $text;
}
?>

<div class="dashboard-container chat-layout">
    <aside class="dashboard-sidebar">
        <div class="user-profile-summary">
            <div class="avatar"><?= mb_substr($user['full_name'] ?? 'U', 0, 1) ?></div>
            <h3><?= sanitize($user['full_name'] ?? 'کاربر') ?></h3>
            <span class="user-phone"><?= $user['phone'] ?? '' ?></span>
            <div style="margin-top:6px;font-size:0.85rem"><i class="ph ph-coin"></i> <?= number_format($_SESSION['credits'] ?? 0) ?> اعتبار</div>
        </div>
        
        <a href="?new=1" class="btn btn-primary btn-block" style="margin-bottom:12px"><i class="ph ph-plus"></i> چت جدید</a>
        
        <nav class="dashboard-nav" style="flex:1;overflow-y:auto">
            <a href="/user/dashboard/v2/" class="nav-item"><i class="ph ph-house"></i> داشبورد</a>
            <a href="/user/dashboard/v2/chat.php" class="nav-item active"><i class="ph ph-chats-circle"></i> چت AI</a>
            <a href="/user/dashboard/v2/image.php" class="nav-item"><i class="ph ph-image"></i> ساخت عکس</a>
            <a href="/projects/" class="nav-item"><i class="ph ph-github-logo"></i> پروژه‌ها</a>
            <a href="/user/dashboard/v2/tasks.php" class="nav-item"><i class="ph ph-kanban"></i> تسک‌ها</a>
            <a href="/shop/" class="nav-item"><i class="ph ph-storefront"></i> فروشگاه</a>
            <a href="/shop/agent.php" class="nav-item"><i class="ph ph-robot"></i> مشاور AI</a>
            <a href="/user/dashboard/v2/profile.php" class="nav-item"><i class="ph ph-user"></i> پروفایل</a>
            <a href="/logout.php" class="nav-item nav-item-danger"><i class="ph ph-sign-out"></i> خروج</a>
        </nav>
        
        <?php if (!empty($conversations)): ?>
        <div style="margin-top:12px;border-top:1px solid var(--border);padding-top:12px">
            <p style="font-size:0.7rem;color:var(--text-muted);margin-bottom:8px"><i class="ph ph-chats-circle"></i> چت‌های اخیر</p>
            <?php foreach (array_slice($conversations, 0, 10) as $c): ?>
            <a href="?conversation=<?= $c['id'] ?>" class="recent-chat-link <?= $active_id == $c['id'] ? 'active' : '' ?>">
                <?= mb_substr(sanitize($c['title']), 0, 25) ?>
                <small style="display:block;color:var(--text-muted);font-size:0.65rem"><?= $c['message_count'] ?> پیام</small>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </aside>
    
    <main class="chat-main">
        <?php if ($active_id): ?>
            <div class="chat-header">
                <button class="sidebar-toggle" onclick="toggleDashboardSidebar()"><i class="ph ph-list"></i></button>
                <?php 
                $active_conv = null;
                foreach ($conversations as $c) { if ($c['id'] == $active_id) { $active_conv = $c; break; } }
                ?>
                <h2><?= sanitize($active_conv['title'] ?? 'چت') ?></h2>
                <span style="font-size:0.75rem;color:var(--text-muted)"><?= $active_conv['message_count'] ?? 0 ?> پیام</span>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messages)): ?>
                <div class="message system"><div class="message-bubble"><i class="ph ph-hand-waving"></i> گفتگو شروع شد. اولین پیام خود را بنویسید.</div></div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                    <div class="message <?= $msg['role'] ?>">
                        <div class="message-avatar"><i class="ph ph-<?= $msg['role'] === 'user' ? 'user' : 'robot' ?>"></i></div>
                        <div class="message-body">
                            <div class="message-bubble">
                                <div class="message-content"><?= formatChatMessage($msg['content']) ?></div>
                                <?php if ($msg['role'] === 'assistant'): ?>
                                <button class="copy-btn" onclick="copyMessage(this)" style="margin-top:6px;font-size:0.7rem;background:transparent;border:1px solid var(--border);padding:3px 10px;border-radius:6px;cursor:pointer;color:var(--text-muted)">
                                    <i class="ph ph-copy"></i> کپی
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="message-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="chat-input-container">
                <div class="chat-input-wrapper">
                    <div class="input-options">
                        <button class="input-option-btn" id="thinkBtn" title="تفکر عمیق"><i class="ph ph-brain"></i> Think</button>
                        <button class="input-option-btn" id="searchBtn" title="جستجوی اینترنتی"><i class="ph ph-magnifying-glass"></i> Search</button>
                    </div>
                    <div class="input-row">
                        <textarea id="messageInput" placeholder="پیام خود را بنویسید..." rows="1" oninput="autoResize(this)"></textarea>
                        <button class="send-btn" id="sendBtn" onclick="sendMessage()"><i class="ph ph-arrow-up"></i></button>
                    </div>
                </div>
                <div class="input-footer">
                    <span><i class="ph ph-brain"></i> Llama 4</span>
                    <span><i class="ph ph-coin"></i> <?= number_format($_SESSION['credits'] ?? 0) ?> اعتبار</span>
                </div>
            </div>
        <?php else: ?>
            <div class="chat-welcome">
                <div class="welcome-icon"><i class="ph ph-robot"></i></div>
                <h1>به چت هوشمند خوش آمدید</h1>
                <p>از من بپرسید — برنامه‌نویسی، ترجمه، یادگیری و خیلی بیشتر</p>
                
                <div class="suggestion-grid-chat">
                    <button onclick="location.href='?msg=یه تابع PHP برای اعتبارسنجی کد ملی بنویس'" class="suggestion-card-chat">
                        <i class="ph ph-code"></i> کد PHP بنویس
                    </button>
                    <button onclick="location.href='?msg=تفاوت SSD و HDD چیه؟'" class="suggestion-card-chat">
                        <i class="ph ph-question"></i> سوال فنی
                    </button>
                    <button onclick="location.href='?msg=یه ایمیل رسمی به انگلیسی بنویس'" class="suggestion-card-chat">
                        <i class="ph ph-envelope"></i> ایمیل انگلیسی
                    </button>
                    <button onclick="location.href='?msg=۵ تا ایده برای کسب و کار اینترنتی بده'" class="suggestion-card-chat">
                        <i class="ph ph-rocket"></i> ایده کسب‌وکار
                    </button>
                </div>
                
                <form method="GET" action="/user/dashboard/v2/chat.php" class="welcome-form">
                    <input type="text" name="msg" placeholder="پیام خود را بنویسید..." required>
                    <button type="submit" class="btn btn-primary"><i class="ph ph-paper-plane-right"></i> ارسال</button>
                </form>
            </div>
        <?php endif; ?>
    </main>
</div>

<script src="/user/dashboard/v2/assets/js/dashboard.js"></script>
<script>
function copyMessage(btn) {
    var bubble = btn.closest('.message-bubble');
    var content = bubble.querySelector('.message-content')?.textContent || '';
    navigator.clipboard.writeText(content).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="ph ph-check"></i> کپی شد';
        btn.style.color = '#10b981';
        setTimeout(function() { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
    });
}

function copyCode(btn) {
    var code = btn.closest('.code-block')?.querySelector('code')?.textContent || '';
    navigator.clipboard.writeText(code).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="ph ph-check"></i> کپی شد';
        setTimeout(function() { btn.innerHTML = orig; }, 2000);
    });
}

document.getElementById('thinkBtn')?.addEventListener('click', function() { this.classList.toggle('active'); });
document.getElementById('searchBtn')?.addEventListener('click', function() { this.classList.toggle('active'); });

document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('messageInput');
    if (input) setTimeout(function() { input.focus(); }, 500);
});

document.getElementById('messageInput')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});
</script>

<style>
.suggestion-grid-chat { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; max-width: 500px; margin: 20px auto; }
.suggestion-card-chat { padding: 16px; border: 1px solid var(--border); border-radius: 14px; background: var(--bg-card); cursor: pointer; font-family: inherit; font-size: 0.85rem; color: var(--text-primary); transition: all 0.2s; display: flex; align-items: center; gap: 10px; }
.suggestion-card-chat:hover { border-color: var(--primary); background: var(--primary-light); transform: translateY(-2px); }
.suggestion-card-chat i { font-size: 1.4rem; color: var(--primary); }
.welcome-form { margin-top: 20px; width: 100%; max-width: 500px; display: flex; gap: 8px; }
.welcome-form input { flex: 1; padding: 14px 18px; border: 2px solid var(--border); border-radius: 14px; font-family: inherit; font-size: 0.95rem; background: var(--bg-input); color: var(--text-primary); }
.welcome-form input:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px var(--primary-light); }
.welcome-icon { font-size: 4rem; color: var(--primary); margin-bottom: 16px; }
</style>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>