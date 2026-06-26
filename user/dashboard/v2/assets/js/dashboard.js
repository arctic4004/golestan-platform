/**
 * =============================================
 * DASHBOARD JS - نسخه ۲.۰
 * مدیریت مستقل منوی داشبورد، چت، و ابزارها
 * =============================================
 */

(function() {
    'use strict';
    
    // ==================== منوی داشبورد (موبایل) ====================
    // این منو کاملاً مستقل از navbar است
    var sidebar = document.querySelector('.dashboard-sidebar');
    var toggleBtn = document.querySelector('.sidebar-toggle');
    
    // تابع باز/بسته کردن منوی داشبورد
    window.toggleDashboardSidebar = function() {
        if (!sidebar) return;
        var isOpen = sidebar.classList.contains('open');
        
        if (isOpen) {
            sidebar.classList.remove('open');
            sidebar.style.cssText = '';
        } else {
            sidebar.classList.add('open');
            sidebar.style.cssText = 'position:fixed!important;top:60px!important;right:0!important;width:280px!important;height:'+(window.innerHeight-60)+'px!important;z-index:1001!important;background:var(--bg-card,#fff)!important;display:flex!important;flex-direction:column!important;padding:16px!important;overflow-y:auto!important;box-shadow:0 20px 60px rgba(0,0,0,0.3)!important;';
        }
    };
    
    // بستن منوی داشبورد
    window.closeDashboardSidebar = function() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        sidebar.style.cssText = '';
    };
    
    // دکمه toggle در چت
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDashboardSidebar();
        });
    }
    
    // بستن منو با کلیک روی محتوای اصلی
    document.addEventListener('click', function(e) {
        if (!sidebar || !sidebar.classList.contains('open')) return;
        if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
            closeDashboardSidebar();
        }
    });
    
    // بستن منو با کلیک روی لینک‌های منو
    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    setTimeout(closeDashboardSidebar, 200);
                }
            });
        });
    }
    
    // ==================== چت ====================
    var chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
        scrollToBottom(false);
    }
    
    // دکمه‌های Think و Search
    var thinkBtn = document.getElementById('thinkBtn');
    var searchBtn = document.getElementById('searchBtn');
    if (thinkBtn) thinkBtn.addEventListener('click', function() { this.classList.toggle('active'); });
    if (searchBtn) searchBtn.addEventListener('click', function() { this.classList.toggle('active'); });
    
})();

// ==================== توابع چت (گلوبال) ====================
async function sendMessage() {
    var input = document.getElementById('messageInput');
    var sendBtn = document.getElementById('sendBtn');
    var chatMessages = document.getElementById('chatMessages');
    
    if (!input || !chatMessages) return;
    
    var message = input.value.trim();
    if (!message || (sendBtn && sendBtn.disabled)) return;
    
    addMessageToChat(message, 'user');
    input.value = '';
    autoResize(input);
    scrollToBottom(true);
    
    var typingId = showTypingIndicator();
    scrollToBottom(true);
    
    if (sendBtn) sendBtn.disabled = true;
    
    try {
        var urlParams = new URLSearchParams(window.location.search);
        var conversationId = urlParams.get('conversation');
        
        var thinkActive = document.getElementById('thinkBtn')?.classList.contains('active') || false;
        var searchActive = document.getElementById('searchBtn')?.classList.contains('active') || false;
        
        var response = await fetch('/api/chat/send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': window.CSRF_TOKEN || ''
            },
            body: JSON.stringify({
                message: message,
                conversation_id: conversationId,
                model: 'llama-4',
                think: thinkActive,
                search: searchActive
            })
        });
        
        var data = await response.json();
        removeTypingIndicator(typingId);
        
        if (data.error) {
            addMessageToChat('❌ ' + data.error, 'system');
        } else {
            addMessageToChat(data.message, 'assistant');
            if (!conversationId && data.conversation_id) {
                window.history.pushState({}, '', '?conversation=' + data.conversation_id);
            }
        }
        scrollToBottom(true);
    } catch (error) {
        removeTypingIndicator(typingId);
        addMessageToChat('❌ خطا در ارتباط با سرور', 'system');
        scrollToBottom(true);
    } finally {
        if (sendBtn) sendBtn.disabled = false;
        input.focus();
    }
}

function addMessageToChat(content, role) {
    var container = document.getElementById('chatMessages');
    if (!container) return;
    
    var div = document.createElement('div');
    div.className = 'message ' + role;
    
    var avatarIcon = role === 'user' ? 'user' : 'robot';
    
    var formatted = escapeHtml(content);
    formatted = formatCodeBlocks(formatted);
    formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    formatted = formatted.replace(/\n/g, '<br>');
    
    var time = new Date().toLocaleTimeString('fa-IR', {hour:'2-digit', minute:'2-digit'});
    
    div.innerHTML = '<div class="message-avatar"><i class="fas fa-' + avatarIcon + '"></i></div>' +
        '<div class="message-body"><div class="message-bubble"><div class="message-content">' + formatted + '</div>' +
        (role === 'user' ? '<div class="message-actions"><button onclick="copyMessageContent(this)" class="msg-action-btn"><i class="fas fa-copy"></i></button></div>' : '') +
        '</div><div class="message-time">' + time + '</div></div>';
    
    container.appendChild(div);
    
    div.querySelectorAll('.copy-btn').forEach(function(btn) {
        btn.addEventListener('click', function() { copyCode(btn); });
    });
}

function escapeHtml(text) {
    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function formatCodeBlocks(text) {
    return text.replace(/```(\w*)\n?([\s\S]*?)```/g, function(match, lang, code) {
        return '<div class="code-block"><div class="code-header"><span>' + (lang || 'code') + '</span><button class="copy-btn"><i class="fas fa-copy"></i> کپی</button></div><pre><code>' + code.trim() + '</code></pre></div>';
    }).replace(/`([^`]+)`/g, '<code class="inline-code">$1</code>');
}

function showTypingIndicator() {
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

function removeTypingIndicator(id) {
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

function copyCode(btn) {
    var codeBlock = btn.closest('.code-block');
    if (!codeBlock) return;
    var code = codeBlock.querySelector('code').textContent;
    navigator.clipboard.writeText(code).then(function() {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> کپی شد';
        btn.style.color = '#4caf50';
        setTimeout(function() { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
    });
}

function copyMessageContent(btn) {
    var bubble = btn.closest('.message-bubble');
    if (!bubble) return;
    var content = bubble.querySelector('.message-content').textContent;
    navigator.clipboard.writeText(content).then(function() {
        var icon = btn.querySelector('i');
        if (icon) { icon.className = 'fas fa-check'; setTimeout(function() { icon.className = 'fas fa-copy'; }, 2000); }
    });
}

function autoResize(textarea) {
    textarea.style.height = 'auto';
    textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey && document.activeElement?.id === 'messageInput') {
        e.preventDefault();
        sendMessage();
    }
});