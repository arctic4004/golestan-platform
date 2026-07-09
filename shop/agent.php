<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/constants.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

$page_title = 'مشاور هوشمند فروش | ' . SITE_NAME;
$extra_css = ['user/dashboard/v2/assets/css/dashboard.css'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php';

$user = $_SESSION['user_id'] ?? null ? getUserData($_SESSION['user_id']) : null;

$kb_file = $_SERVER['DOCUMENT_ROOT'] . '/knowledge/cafenet_knowledge.json';
$kb_services = [];
if (file_exists($kb_file)) {
    $kb = json_decode(file_get_contents($kb_file), true);
    $kb_services = $kb['cafenet_knowledge']['services'] ?? [];
}
$categories = array_unique(array_column($kb_services, 'category'));
?>

<style>
.agent-page { max-width: 800px; margin: 80px auto 40px; padding: 0 20px; }
.agent-card { background: var(--bg-card); border-radius: 20px; box-shadow: var(--shadow-lg); overflow: hidden; border: 1px solid var(--border); }
.agent-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; }
.agent-header .icon { font-size: 2rem; color: var(--primary); }
.agent-header h2 { font-size: 1.3rem; font-weight: 700; color: var(--text-primary); }
.agent-header p { color: var(--text-secondary); font-size: 0.85rem; margin-top: 2px; }
.agent-body { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
.agent-messages { max-height: 450px; overflow-y: auto; display: flex; flex-direction: column; gap: 16px; padding-right: 8px; }
.msg { display: flex; gap: 10px; animation: msgIn 0.3s ease; }
.msg.user { flex-direction: row-reverse; }
.msg-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.msg.user .msg-avatar { background: var(--primary); color: #fff; }
.msg.assistant .msg-avatar { background: var(--accent); color: #fff; }
.msg-bubble { padding: 12px 16px; border-radius: 16px; line-height: 1.7; font-size: 0.9rem; max-width: 80%; word-wrap: break-word; }
.msg.user .msg-bubble { background: var(--primary); color: #fff; border-bottom-right-radius: 4px; }
.msg.assistant .msg-bubble { background: var(--bg-secondary); border: 1px solid var(--border); border-bottom-left-radius: 4px; color: var(--text-primary); }
@keyframes msgIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.agent-footer { padding: 16px 24px; border-top: 1px solid var(--border); background: var(--bg-secondary); }
.suggestions { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.suggestion { padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-secondary); cursor: pointer; font-size: 0.8rem; font-family: inherit; transition: 0.2s; }
.suggestion:hover { border-color: var(--primary); color: var(--primary); background: var(--primary-light); }
.input-row { display: flex; gap: 8px; align-items: flex-end; }
.input-row textarea { flex: 1; padding: 12px; border: 2px solid var(--border); border-radius: 14px; font-family: inherit; font-size: 0.9rem; resize: none; min-height: 48px; max-height: 120px; background: var(--bg-input); color: var(--text-primary); transition: 0.2s; }
.input-row textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-light); outline: none; }
.mic-btn, .send-btn { width: 44px; height: 44px; border-radius: 50%; border: none; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center; transition: 0.2s; flex-shrink: 0; }
.mic-btn { background: var(--accent); color: #fff; }
.mic-btn:hover { background: #d97706; transform: scale(1.05); }
.mic-btn.recording { background: #ef4444; animation: micPulse 1.5s infinite; }
@keyframes micPulse { 0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,0.4); } 50% { box-shadow: 0 0 0 12px rgba(239,68,68,0); } }
.send-btn { background: var(--primary); color: #fff; }
.send-btn:hover { background: var(--primary-hover); transform: scale(1.05); }
.typing-dots { display: flex; gap: 4px; padding: 8px 0; }
.typing-dots span { width: 6px; height: 6px; border-radius: 50%; background: var(--text-muted); animation: bounce 1.4s infinite; }
.typing-dots span:nth-child(2) { animation-delay: 0.2s; } .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
@keyframes bounce { 0%,60%,100% { opacity: 0.3; transform: translateY(0); } 30% { opacity: 1; transform: translateY(-6px); } }
.mic-error { color: var(--danger); font-size: 0.75rem; margin-top: 4px; display: none; }
</style>

<div class="agent-page">
    <div class="agent-card">
        <div class="agent-header">
            <span class="icon"><i class="ph ph-robot"></i></span>
            <div>
                <h2>مشاور هوشمند کافی‌نت</h2>
                <p>قیمت، مدارک و زمان خدمات را بپرسید | <i class="ph ph-microphone"></i> ورودی صوتی</p>
            </div>
        </div>
        <div class="agent-body">
            <div class="agent-messages" id="agentMessages">
                <div class="msg assistant">
                    <div class="msg-avatar"><i class="ph ph-robot"></i></div>
                    <div class="msg-bubble">
                        👋 سلام! من می‌تونم درباره <strong>قیمت، مدارک و زمان</strong> هر خدمتی راهنماییت کنم.<br>
                        <i class="ph ph-microphone"></i> با صدا هم سوال بپرس!<br>
                        <small style="opacity:0.7">مثال: "وام ازدواج چقدر طول میکشه؟"</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="agent-footer">
            <div class="suggestions">
                <?php foreach (array_slice($categories, 0, 5) as $cat): ?>
                <button class="suggestion" onclick="ask('خدمات <?= $cat ?> چه چیزایی دارید؟')"><i class="ph ph-folder"></i> <?= $cat ?></button>
                <?php endforeach; ?>
                <button class="suggestion" onclick="ask('لیست کامل قیمت‌ها رو بده')"><i class="ph ph-list"></i> همه قیمت‌ها</button>
            </div>
            <div class="input-row">
                <button class="mic-btn" id="micBtn" onclick="toggleMic()" title="ورودی صوتی">
                    <i class="ph ph-microphone"></i>
                </button>
                <textarea id="agentInput" placeholder="سوال خود را بنویسید..." rows="1" oninput="autoResize(this)"></textarea>
                <button class="send-btn" onclick="sendMsg()" title="ارسال">
                    <i class="ph ph-paper-plane-right"></i>
                </button>
            </div>
            <div class="mic-error" id="micError">⚠️ لطفاً دسترسی میکروفون را در تنظیمات مرورگر فعال کنید.</div>
        </div>
    </div>
</div>

<script>
let recognition = null;
let isRecording = false;

async function toggleMic() {
    const micBtn = document.getElementById('micBtn');
    const errorDiv = document.getElementById('micError');
    errorDiv.style.display = 'none';

    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
        alert('مرورگر شما از ورودی صوتی پشتیبانی نمی‌کند. لطفاً از Chrome یا Edge استفاده کنید.');
        return;
    }

    try {
        const permissionStatus = await navigator.permissions.query({ name: 'microphone' });
        if (permissionStatus.state === 'denied') {
            errorDiv.textContent = '⛔ دسترسی میکروفون مسدود شده است. لطفاً از تنظیمات مرورگر آن را فعال کنید.';
            errorDiv.style.display = 'block';
            return;
        }
    } catch (e) {}

    if (!recognition) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SpeechRecognition();
        recognition.lang = 'fa-IR';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        recognition.continuous = false;

        recognition.onresult = function(event) {
            const text = event.results[0][0].transcript;
            document.getElementById('agentInput').value = text;
            stopRecording();
            sendMsg();
        };

        recognition.onerror = function(event) {
            stopRecording();
            if (event.error === 'not-allowed') {
                errorDiv.textContent = '⛔ دسترسی میکروفون رد شد. لطفاً از تنظیمات مرورگر مجوز را صادر کنید.';
                errorDiv.style.display = 'block';
            } else if (event.error === 'no-speech') {
                errorDiv.textContent = '🎤 صدایی تشخیص داده نشد، دوباره تلاش کنید.';
                errorDiv.style.display = 'block';
                setTimeout(() => errorDiv.style.display = 'none', 3000);
            } else {
                console.log('Speech error:', event.error);
            }
        };

        recognition.onend = function() { stopRecording(); };
    }

    if (isRecording) { stopRecording(); } else { startRecording(); }
}

function startRecording() {
    try {
        recognition.start();
        isRecording = true;
        const btn = document.getElementById('micBtn');
        btn.classList.add('recording');
        btn.innerHTML = '<i class="ph ph-stop-circle"></i>';
        btn.title = 'توقف ضبط';
        document.getElementById('agentInput').placeholder = '🎤 در حال گوش دادن...';
        document.getElementById('micError').style.display = 'none';
    } catch (e) { console.log('Recognition start error:', e); }
}

function stopRecording() {
    if (recognition) { try { recognition.stop(); } catch (e) {} }
    isRecording = false;
    const btn = document.getElementById('micBtn');
    btn.classList.remove('recording');
    btn.innerHTML = '<i class="ph ph-microphone"></i>';
    btn.title = 'ورودی صوتی';
    document.getElementById('agentInput').placeholder = 'سوال خود را بنویسید...';
}

async function sendMsg() {
    const input = document.getElementById('agentInput');
    const message = input.value.trim();
    if (!message) return;

    addMessage(message, 'user');
    input.value = '';
    autoResize(input);
    input.focus();

    const typingId = showTyping();

    try {
        const res = await fetch('/api/chat/send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message, model: 'llama-4', think: false, search: true })
        });
        const data = await res.json();
        removeTyping(typingId);
        addMessage(data.error ? '❌ ' + data.error : data.message, 'assistant');
    } catch (e) {
        removeTyping(typingId);
        addMessage('❌ خطا در ارتباط با سرور', 'assistant');
    }
}

function ask(text) {
    document.getElementById('agentInput').value = text;
    sendMsg();
}

function addMessage(text, role) {
    const container = document.getElementById('agentMessages');
    const div = document.createElement('div');
    div.className = 'msg ' + role;
    div.innerHTML = `
        <div class="msg-avatar"><i class="ph ph-${role === 'user' ? 'user' : 'robot'}"></i></div>
        <div class="msg-bubble">${text.replace(/\n/g, '<br>').replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')}</div>
    `;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
}

function showTyping() {
    const container = document.getElementById('agentMessages');
    const id = 'typing-' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'msg assistant';
    div.innerHTML = '<div class="msg-avatar"><i class="ph ph-robot"></i></div><div class="msg-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div>';
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return id;
}

function removeTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
}

document.getElementById('agentInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMsg();
    }
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'; ?>