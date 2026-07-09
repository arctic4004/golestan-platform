<?php
echo "<!DOCTYPE html><html lang='fa' dir='rtl'><head><meta charset='UTF-8'><title>تست میکروفون</title>";
echo "<style>body{font-family:Vazirmatn,sans-serif;max-width:600px;margin:50px auto;padding:20px;background:#f8fafc;direction:rtl} .card{background:#fff;padding:20px;border-radius:16px;margin-bottom:16px;box-shadow:0 2px 10px rgba(0,0,0,0.05)} .ok{color:#10b981} .fail{color:#ef4444} button{padding:12px 24px;border-radius:12px;border:none;font-size:1rem;cursor:pointer;font-family:inherit;margin:5px} .mic-btn{background:#f59e0b;color:#fff} .mic-btn.recording{background:#ef4444} #log{background:#1e293b;color:#e2e8f0;padding:16px;border-radius:12px;font-size:0.85rem;line-height:1.8;max-height:300px;overflow:auto}</style>";
echo "</head><body>";
echo "<h2>🎤 تست میکروفون</h2>";

echo "<div class='card'><h3>۱. بررسی HTTPS</h3>";
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    echo "<p class='ok'>✅ سایت HTTPS است — میکروفون کار می‌کند</p>";
} else {
    echo "<p class='fail'>❌ سایت HTTP است — میکروفون فقط روی HTTPS یا localhost کار می‌کند!</p>";
}
echo "</div>";

echo "<div class='card'><h3>۲. بررسی Web Speech API</h3>";
echo "<p id='apiCheck'>در حال بررسی...</p>";
echo "</div>";

echo "<div class='card'><h3>۳. بررسی مجوز میکروفون</h3>";
echo "<p id='permCheck'>در حال بررسی...</p>";
echo "</div>";

echo "<div class='card'><h3>۴. تست ضبط صدا</h3>";
echo "<button class='mic-btn' id='micBtn' onclick='toggleMic()'>🎤 شروع ضبط</button>";
echo "<p id='result' style='margin-top:10px;font-size:1.1rem'></p>";
echo "</div>";

echo "<div class='card'><h3>📋 لاگ</h3>";
echo "<div id='log'></div>";
echo "</div>";

echo "<script>
const log = document.getElementById('log');
function addLog(msg) { log.innerHTML += msg + '<br>'; log.scrollTop = log.scrollHeight; }

// بررسی API
const hasSpeech = 'webkitSpeechRecognition' in window || 'SpeechRecognition' in window;
document.getElementById('apiCheck').innerHTML = hasSpeech 
    ? \"<span class='ok'>✅ Speech Recognition API پشتیبانی می‌شود</span>\" 
    : \"<span class='fail'>❌ مرورگر شما این API را پشتیبانی نمی‌کند. لطفاً Chrome یا Edge استفاده کنید.</span>\";

// بررسی مجوز
if (navigator.permissions) {
    navigator.permissions.query({name:'microphone'}).then(function(result) {
        document.getElementById('permCheck').innerHTML = 
            result.state === 'granted' ? \"<span class='ok'>✅ دسترسی میکروفون قبلاً داده شده</span>\" :
            result.state === 'denied' ? \"<span class='fail'>❌ دسترسی میکروفون مسدود شده! Settings → Privacy → Microphone → Allow</span>\" :
            \"<span style='color:#f59e0b'>⚠️ وضعیت نامشخص — هنگام ضبط پرسیده می‌شود</span>\";
    }).catch(function() {
        document.getElementById('permCheck').innerHTML = \"<span style='color:#f59e0b'>⚠️ permissions API پشتیبانی نمی‌شود</span>\";
    });
} else {
    document.getElementById('permCheck').innerHTML = \"<span style='color:#f59e0b'>⚠️ permissions API در این مرورگر نیست</span>\";
}

let recognition = null;
let isRecording = false;

function toggleMic() {
    if (!hasSpeech) { alert('مرورگر شما پشتیبانی نمی‌کند'); return; }
    
    if (!recognition) {
        const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
        recognition = new SR();
        recognition.lang = 'fa-IR';
        recognition.interimResults = true;
        recognition.continuous = false;
        
        recognition.onstart = function() { addLog('✅ ضبط شروع شد'); };
        recognition.onresult = function(e) {
            const text = e.results[0][0].transcript;
            document.getElementById('result').textContent = '📝 تشخیص: ' + text;
            addLog('📝 تشخیص داده شد: ' + text);
        };
        recognition.onerror = function(e) {
            addLog('❌ خطا: ' + e.error);
            if (e.error === 'not-allowed') {
                document.getElementById('result').innerHTML = '<span class=\"fail\">❌ دسترسی میکروفون رد شد!<br>Chrome: Settings → Privacy → Site Settings → Microphone → Allow<br>یا روی آیکون قفل کنار آدرس کلیک کنید</span>';
            }
            stopMic();
        };
        recognition.onend = function() { addLog('⏹ ضبط متوقف شد'); stopMic(); };
    }
    
    if (isRecording) { stopMic(); } else { startMic(); }
}

function startMic() {
    try {
        recognition.start();
        isRecording = true;
        document.getElementById('micBtn').textContent = '⏹ توقف';
        document.getElementById('micBtn').classList.add('recording');
        document.getElementById('result').textContent = '🎤 در حال گوش دادن...';
        addLog('🎤 start() صدا شد');
    } catch(e) {
        addLog('❌ Exception: ' + e.message);
    }
}

function stopMic() {
    try { recognition.stop(); } catch(e) {}
    isRecording = false;
    document.getElementById('micBtn').textContent = '🎤 شروع ضبط';
    document.getElementById('micBtn').classList.remove('recording');
}
</script>";
echo "</body></html>";
?>