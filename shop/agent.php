<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

if (!isLoggedIn()) {
    redirect('/login.php?redirect=/shop/agent.php');
}

$db = (new Database())->getConnection();

// دریافت همه محصولات برای context
$products = $db->query("SELECT * FROM products WHERE is_active = 1")->fetchAll();
$products_context = "محصولات و خدمات کافی‌نت گلستان:\n";
foreach ($products as $p) {
    $products_context .= "- {$p['name']} | قیمت: " . number_format($p['price']) . " تومان | نوع: " . ($p['type'] == 'service' ? 'خدمات' : 'کالا') . " | وضعیت: {$p['condition']} | موجودی: {$p['stock']}\n";
}

// پردازش پیام
$conversation = $_SESSION['agent_conversation'] ?? [];
$reply = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $user_message = trim($_POST['message']);
    $conversation[] = ['role' => 'user', 'content' => $user_message];
    
    // ارسال به AI
    require_once $_SERVER['DOCUMENT_ROOT'] . '/api/chat/DeepSeekAPI.php';
    try {
        $ai = new DeepSeekAPI();
        $system_prompt = "شما دستیار فروش کافی‌نت گلستان هستید. محصولات ما:\n{$products_context}\n\nلطفاً به مشتری کمک کنید بهترین انتخاب را داشته باشد. پاسخ‌ها کوتاه، مفید، گرم و دوستانه باشد. قیمت‌ها را به تومان بگویید.";
        
        $response = $ai->sendMessage($user_message, $conversation, null, ['think' => false, 'search' => false]);
        $reply = $response['content'];
        $conversation[] = ['role' => 'assistant', 'content' => $reply];
    } catch (Exception $e) {
        $reply = 'متأسفانه در حال حاضر نمیتونم پاسخ بدم. لطفاً دوباره تلاش کنید. 😔';
        $conversation[] = ['role' => 'assistant', 'content' => $reply];
    }
    
    $_SESSION['agent_conversation'] = $conversation;
}

$page_title = 'مشاور هوشمند فروش | ' . SITE_NAME;
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';
?>

<style>
.agent-page { max-width: 750px; margin: 100px auto 40px; }
.agent-header { text-align: center; margin-bottom: 24px; }
.agent-header h1 { font-size: 1.8rem; }
.agent-header p { color: var(--text-secondary); }
.chat-box { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 20px; min-height: 350px; max-height: 500px; overflow-y: auto; margin-bottom: 16px; }
.chat-msg { margin-bottom: 16px; display: flex; gap: 10px; }
.chat-msg.user { flex-direction: row-reverse; }
.chat-msg .avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.chat-msg.user .avatar { background: var(--primary); color: white; }
.chat-msg.assistant .avatar { background: var(--bg-tertiary); color: var(--primary); }
.chat-msg .bubble { padding: 10px 16px; border-radius: 16px; max-width: 80%; line-height: 1.7; font-size: 0.9rem; }
.chat-msg.user .bubble { background: var(--primary); color: white; border-bottom-right-radius: 4px; }
.chat-msg.assistant .bubble { background: var(--bg-secondary); border: 1px solid var(--border); border-bottom-left-radius: 4px; }
.chat-input-area { display: flex; gap: 10px; }
.chat-input-area input { flex: 1; padding: 14px; border: 1px solid var(--border); border-radius: 12px; font-family: var(--font); font-size: 0.95rem; background: var(--bg-card); color: var(--text-primary); }
.chat-input-area input:focus { outline: none; border-color: var(--primary); }
.suggestions { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.suggestion-chip { padding: 8px 16px; border-radius: 20px; border: 1px solid var(--border); background: var(--bg-card); cursor: pointer; font-size: 0.85rem; font-family: var(--font); transition: all 0.2s; color: var(--text-secondary); }
.suggestion-chip:hover { background: var(--primary); color: white; border-color: var(--primary); }
</style>

<div class="container agent-page">
    <div class="agent-header">
        <h1>🤖 مشاور فروش هوشمند</h1>
        <p>از من بپرسید تا بهترین محصول یا خدمت را به شما پیشنهاد دهم</p>
    </div>
    
    <div class="suggestions">
        <button class="suggestion-chip" onclick="askAgent('چه خدماتی دارید؟')">💼 خدمات</button>
        <button class="suggestion-chip" onclick="askAgent('قیمت طراحی سایت چنده؟')">🌐 قیمت طراحی سایت</button>
        <button class="suggestion-chip" onclick="askAgent('ارزان‌ترین خدمات رو بگو')">💰 ارزان‌ترین</button>
        <button class="suggestion-chip" onclick="askAgent('چه کالاهای نو دارید؟')">🆕 کالاهای نو</button>
        <button class="suggestion-chip" onclick="askAgent('پیشنهاد ویژه برای برنامه‌نویس‌ها')">💻 برای برنامه‌نویس‌ها</button>
    </div>
    
    <div class="chat-box" id="chatBox">
        <?php if (empty($conversation)): ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <i class="fas fa-robot" style="font-size: 3rem; margin-bottom: 12px; display: block;"></i>
                <p>سلام! 👋 من دستیار فروش کافی‌نت گلستان هستم.</p>
                <p>می‌تونید درباره خدمات، قیمت‌ها و محصولات از من بپرسید.</p>
            </div>
        <?php else: ?>
            <?php foreach ($conversation as $msg): ?>
            <div class="chat-msg <?php echo $msg['role']; ?>">
                <div class="avatar">
                    <i class="fas fa-<?php echo $msg['role'] == 'user' ? 'user' : 'robot'; ?>"></i>
                </div>
                <div class="bubble"><?php echo nl2br(sanitize($msg['content'])); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <form method="POST" class="chat-input-area">
        <input type="text" name="message" placeholder="سوال خود را بپرسید... (مثلاً: قیمت نصب ویندوز چنده؟)" required autofocus>
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
    </form>
    
    <div style="text-align: center; margin-top: 16px;">
        <a href="/shop/" class="btn btn-outline btn-sm">🛍️ رفتن به فروشگاه</a>
        <?php if (!empty($conversation)): ?>
            <a href="?clear=1" class="btn btn-outline btn-sm" style="color: #f44336;">🗑️ پاک کردن گفتگو</a>
        <?php endif; ?>
    </div>
</div>

<?php
// پاک کردن گفتگو
if (isset($_GET['clear'])) {
    unset($_SESSION['agent_conversation']);
    redirect('/shop/agent.php');
}
?>

<script>
function askAgent(question) {
    // پر کردن input و submit خودکار
    const input = document.querySelector('input[name="message"]');
    if (input) {
        input.value = question;
        input.form.submit();
    }
}

// اسکرول به پایین چت
const chatBox = document.getElementById('chatBox');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>