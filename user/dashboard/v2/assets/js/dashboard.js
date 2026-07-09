/**
 * =============================================
 * DASHBOARD JS - نسخه ۳.۱
 * مدیریت سایدبار، چت (Think/Search فعال)، و ابزارها
 * =============================================
 */
(function() {
    'use strict';
    
    // ========== سایدبار ==========
    function initSidebar() {
        var sidebar = document.querySelector('.dashboard-sidebar');
        if (!sidebar) return;
        
        var overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        overlay.onclick = closeSidebar;
        document.body.appendChild(overlay);
        
        window.toggleDashboardSidebar = function() {
            if (!sidebar) return;
            var isOpen = sidebar.classList.contains('active');
            isOpen ? closeSidebar() : openSidebar();
        };
        
        function openSidebar() {
            sidebar.classList.add('active');
            document.querySelector('.sidebar-overlay')?.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeSidebar() {
            sidebar.classList.remove('active');
            document.querySelector('.sidebar-overlay')?.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        sidebar.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) setTimeout(closeSidebar, 200);
            });
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) closeSidebar();
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) closeSidebar();
        });
    }
    
    // ========== چت ==========
    function initChat() {
        var chatMessages = document.getElementById('chatMessages');
        if (chatMessages) scrollToBottom(false);
        
        // Think & Search buttons
        var thinkBtn = document.getElementById('thinkBtn');
        var searchBtn = document.getElementById('searchBtn');
        
        if (thinkBtn) {
            thinkBtn.addEventListener('click', function() {
                this.classList.toggle('active');
                // تغییر رنگ و آیکون
                if (this.classList.contains('active')) {
                    this.style.background = 'var(--primary-light, #e0f2fe)';
                    this.style.borderColor = 'var(--primary, #0ea5e9)';
                    this.style.color = 'var(--primary, #0ea5e9)';
                    this.style.opacity = '1';
                    this.style.fontWeight = '600';
                } else {
                    this.style.background = 'transparent';
                    this.style.borderColor = 'var(--border, #e2e8f0)';
                    this.style.color = 'var(--text-secondary, #475569)';
                    this.style.opacity = '0.7';
                    this.style.fontWeight = '400';
                }
            });
        }
        
        if (searchBtn) {
            searchBtn.addEventListener('click', function() {
                this.classList.toggle('active');
                if (this.classList.contains('active')) {
                    this.style.background = 'var(--primary-light, #e0f2fe)';
                    this.style.borderColor = 'var(--primary, #0ea5e9)';
                    this.style.color = 'var(--primary, #0ea5e9)';
                    this.style.opacity = '1';
                    this.style.fontWeight = '600';
                } else {
                    this.style.background = 'transparent';
                    this.style.borderColor = 'var(--border, #e2e8f0)';
                    this.style.color = 'var(--text-secondary, #475569)';
                    this.style.opacity = '0.7';
                    this.style.fontWeight = '400';
                }
            });
        }
        
        // ارسال با Enter
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey && document.activeElement?.id === 'messageInput') {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    initSidebar();
    initChat();
    
})();

// ========== توابع چت (global) ==========
async function sendMessage() {
    var input = document.getElementById('messageInput');
    var chatMessages = document.getElementById('chatMessages');
    if (!input || !chatMessages) return;
    
    var message = input.value.trim();
    if (!message) return;
    
    // غیرفعال کردن دکمه ارسال
    var sendBtn = document.getElementById('sendBtn');
    if (sendBtn) sendBtn.disabled = true;
    
    addMessage(message, 'user');
    input.value = '';
    autoResize(input);
    scrollToBottom(true);
    
    var typingId = showTyping();
    scrollToBottom(true);
    
    try {
        var urlParams = new URLSearchParams(window.location.search);
        var conversationId = urlParams.get('conversation');
        var think = document.getElementById('thinkBtn')?.classList.contains('active') || false;
        var search = document.getElementById('searchBtn')?.classList.contains('active') || false;
        
        var response = await fetch('/api/chat/send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: message,
                conversation_id: conversationId,
                model: 'llama-4',
                think: think,
                search: search
            })
        });
        
        var data = await response.json();
        removeTyping(typingId);
        
        if (data.error) {
            addMessage('❌ ' + data.error, 'system');
        } else {
            var responseText = data.message;
            // اگه Think فعاله، پیشوند اضافه کن
            if (think && data.kb_matches === 0) {
                responseText = '🧠 **حالت تفکر عمیق**\n\n' + responseText;
            }
            // اگه Search فعاله
            if (search && data.kb_matches === 0) {
                responseText = '🌐 **حالت جستجو**\n\n' + responseText;
            }
            addMessage(responseText, 'assistant');
            if (!conversationId && data.conversation_id) {
                window.history.pushState({}, '', '?conversation=' + data.conversation_id);
            }
        }
        scrollToBottom(true);
    } catch (error) {
        removeTyping(typingId);
        addMessage('❌ خطا در ارتباط با سرور', 'system');
        scrollToBottom(true);
    } finally {
        if (sendBtn) sendBtn.disabled = false;
        input.focus();
    }
}

function addMessage(content, role) {
    var container = document.getElementById('chatMessages');
    if (!container) return;
    
    var div = document.createElement('div');
    div.className = 'message ' + role;
    div.innerHTML = 
        '<div class="message-avatar"><i class="fas fa-' + (role === 'user' ? 'user' : 'robot') + '"></i></div>' +
        '<div class="message-body">' +
            '<div class="message-bubble"><div class="message-content">' + formatContent(content) + '</div></div>' +
            '<div class="message-time">' + new Date().toLocaleTimeString('fa-IR', {hour:'2-digit', minute:'2-digit'}) + '</div>' +
        '</div>';
    
    container.appendChild(div);
    initCodeBlocks(div);
}

function formatContent(text) {
    text = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, 
        '<div class="code-block"><div class="code-header"><span>$1</span><button class="copy-btn"><i class="fas fa-copy"></i> کپی</button></div><pre><code>$2</code></pre></div>');
    text = text.replace(/`([^`]+)`/g, '<code class="inline-code">$1</code>');
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\n/g, '<br>');
    return text;
}

function initCodeBlocks(container) {
    container.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var code = this.closest('.code-block')?.querySelector('code')?.textContent || '';
            navigator.clipboard.writeText(code).then(function() {
                btn.innerHTML = '<i class="fas fa-check"></i> کپی شد';
                setTimeout(function() { btn.innerHTML = '<i class="fas fa-copy"></i> کپی'; }, 2000);
            });
        });
    });
}

function showTyping() {
    var container = document.getElementById('chatMessages');
    if (!container) return null;
    var id = 'typing-' + Date.now();
    var div = document.createElement('div');
    div.id = id;
    div.className = 'message assistant';
    div.innerHTML = '<div class="message-avatar"><i class="fas fa-robot"></i></div><div class="message-body"><div class="message-bubble"><div class="typing-dots"><span></span><span></span><span></span></div></div></div>';
    container.appendChild(div);
    return id;
}

function removeTyping(id) {
    var el = document.getElementById(id);
    if (el) el.remove();
}

function scrollToBottom(smooth) {
    var container = document.getElementById('chatMessages');
    if (!container) return;
    requestAnimationFrame(function() {
        container.scrollTo({ top: container.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
    });
}

function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
}