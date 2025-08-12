<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Chat – MDC Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 50%, #0f0f0f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 90vh;
            width: 100%;
            max-width: 800px;
            background: linear-gradient(145deg, #1c1c1c 0%, #242424 100%);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(144,238,144,0.1);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .chat-header {
            background: linear-gradient(135deg, #90ee90 0%, #7dd87d 100%);
            color: #1a1a1a;
            padding: 20px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .faculty-avatar {
            width: 45px;
            height: 45px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: 2px solid rgba(0, 0, 0, 0.1);
        }
        
        .header-info h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .status {
            font-size: 12px;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-dot {
            width: 6px;
            height: 6px;
            background: #1a1a1a;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .header-btn {
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.15);
            border: none;
            border-radius: 50%;
            color: #1a1a1a;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .header-btn:hover {
            background: rgba(0, 0, 0, 0.25);
            transform: scale(1.1);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: linear-gradient(145deg, #2a2a2a 0%, #1f1f1f 100%);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            padding: 10px 0;
            min-width: 180px;
            z-index: 100;
            display: none;
            border: 1px solid rgba(144,238,144,0.2);
        }
        
        .dropdown-item {
            padding: 10px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e0e0e0;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .dropdown-item:hover { background: rgba(144,238,144,0.1); }
        
        #chat-box {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            background: linear-gradient(to bottom, #1a1a1a, #0f0f0f);
        }
        
        #chat-box::-webkit-scrollbar { width: 6px; }
        #chat-box::-webkit-scrollbar-track { background: #333; border-radius: 10px; }
        #chat-box::-webkit-scrollbar-thumb { background: #90ee90; border-radius: 10px; }
        
        .message {
            max-width: 65%;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 20px;
            font-size: 15px;
            line-height: 1.5;
            position: relative;
            word-wrap: break-word;
            animation: messageSlide 0.3s ease-out;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }
        
        @keyframes messageSlide {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .sent {
            background: linear-gradient(135deg, #90ee90, #7dd87d);
            color: #1a1a1a;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .received {
            background: linear-gradient(145deg, #2a2a2a 0%, #1f1f1f 100%);
            color: #e0e0e0;
            margin-right: auto;
            border: 1px solid rgba(144,238,144,0.2);
            border-bottom-left-radius: 5px;
        }
        
        .message small {
            display: block;
            font-size: 11px;
            margin-top: 8px;
            opacity: 0.7;
        }
        
        .chat-footer {
            display: flex;
            padding: 20px 25px;
            background: linear-gradient(145deg, #242424 0%, #1c1c1c 100%);
            border-top: 1px solid rgba(144,238,144,0.2);
            gap: 15px;
            align-items: center;
        }
        
        .input-container { flex: 1; position: relative; }
        
        #message {
            width: 100%;
            padding: 15px 20px;
            border-radius: 25px;
            border: 2px solid #444;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            background: #2a2a2a;
            color: #e0e0e0;
            resize: none;
            min-height: 50px;
            max-height: 100px;
        }
        
        #message:focus {
            border-color: #90ee90;
            background: #333;
            box-shadow: 0 0 0 3px rgba(144, 238, 144, 0.15);
        }
        
        #message::placeholder { color: #888; }
        
        #sendBtn {
            background: linear-gradient(135deg, #90ee90, #7dd87d);
            border: none;
            color: #1a1a1a;
            width: 55px;
            height: 55px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #sendBtn:hover {
            transform: scale(1.1);
            box-shadow: 0 5px 20px rgba(144, 238, 144, 0.4);
        }
        
        .emoji-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            color: #90ee90;
        }
        
        .emoji-btn:hover { background: rgba(144, 238, 144, 0.15); }
        
        .emoji-picker {
            position: absolute;
            bottom: 70px;
            right: 80px;
            background: linear-gradient(145deg, #2a2a2a 0%, #1f1f1f 100%);
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            z-index: 100;
            width: 280px;
            display: none;
            border: 1px solid rgba(144,238,144,0.2);
        }
        
        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 5px;
        }
        
        .emoji-item {
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 5px;
            text-align: center;
            transition: background 0.2s;
        }
        
        .emoji-item:hover { background: rgba(144,238,144,0.2); }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }
        
        .modal-content {
            background: linear-gradient(145deg, #2a2a2a 0%, #1f1f1f 100%);
            margin: 10% auto;
            padding: 20px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(144,238,144,0.2);
        }
        
        .modal h3 {
            margin-bottom: 20px;
            color: #90ee90;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #888;
            margin-top: -10px;
        }
        
        .close:hover { color: #90ee90; }
        
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #444;
            color: #e0e0e0;
        }
        
        .toggle {
            width: 40px;
            height: 20px;
            background: #555;
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .toggle.active { background: #90ee90; }
        
        .toggle-slider {
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 2px;
            left: 2px;
            transition: transform 0.3s;
        }
        
        .toggle.active .toggle-slider { transform: translateX(20px); }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            z-index: 10000;
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .notification.success { background: #90ee90; color: #1a1a1a; }
        .notification.error { background: #ff6b6b; }
        .notification.info { background: #64b5f6; }
    </style>
</head>
<body>
<div class="chat-container">
    <div class="chat-header">
        <div class="header-left">
            <div class="faculty-avatar">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="header-info">
                <h3>Faculty Panel</h3>
                <div class="status">
                    <span class="status-dot"></span>
                    Admin Communication
                </div>
            </div>
        </div>
        <div class="header-actions">
            <button class="header-btn" id="settingsBtn" title="Settings">
                <i class="fas fa-cog"></i>
            </button>
            <div style="position: relative;">
                <button class="header-btn" id="moreBtn" title="More options">
                    <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <a href="#" class="dropdown-item" id="clearChat">
                        <i class="fas fa-trash"></i> Clear Chat
                    </a>
                    <a href="#" class="dropdown-item" id="exportChat">
                        <i class="fas fa-download"></i> Export Chat
                    </a>
                    <a href="#" class="dropdown-item" id="refresh">
                        <i class="fas fa-sync"></i> Refresh
                    </a>
                    <a href="login.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div id="chat-box"></div>
    
    <div class="chat-footer">
        <div class="input-container">
            <textarea name="message" id="message" placeholder="Type your message here..." rows="1"></textarea>
        </div>
        <button class="emoji-btn" id="emojiBtn" title="Add emoji">😊</button>
        <button id="sendBtn" title="Send message">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Settings Modal -->
<div id="settingsModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeSettings">&times;</span>
        <h3><i class="fas fa-cog"></i> Settings</h3>
        <div class="setting-row">
            <span>Sound Notifications</span>
            <div class="toggle active" id="soundToggle">
                <div class="toggle-slider"></div>
            </div>
        </div>
        <div class="setting-row">
            <span>Auto Scroll</span>
            <div class="toggle active" id="autoScrollToggle">
                <div class="toggle-slider"></div>
            </div>
        </div>
    </div>
</div>

<!-- Emoji Picker -->
<div class="emoji-picker" id="emojiPicker">
    <div class="emoji-grid">
        <span class="emoji-item">😀</span><span class="emoji-item">😂</span><span class="emoji-item">😊</span><span class="emoji-item">😎</span>
        <span class="emoji-item">🤔</span><span class="emoji-item">👍</span><span class="emoji-item">❤️</span><span class="emoji-item">🎉</span>
        <span class="emoji-item">🔥</span><span class="emoji-item">💯</span><span class="emoji-item">⚡</span><span class="emoji-item">✨</span>
        <span class="emoji-item">🚀</span><span class="emoji-item">💻</span><span class="emoji-item">📚</span><span class="emoji-item">🎯</span>
    </div>
</div>

<script>
let soundEnabled = true;
let autoScrollEnabled = true;

function fetchMessages() {
    fetch("fetch_messages.php?role=admin")
        .then(response => response.text())
        .then(data => {
            document.getElementById("chat-box").innerHTML = data;
            if(autoScrollEnabled) {
                document.getElementById("chat-box").scrollTop = document.getElementById("chat-box").scrollHeight;
            }
        });
}

function sendMessage() {
    let msg = document.getElementById("message").value.trim();
    if (msg !== "") {
        document.getElementById("sendBtn").querySelector('i').className = 'fas fa-spinner fa-spin';
        
        let formData = new FormData();
        formData.append("receiver_role", "admin");
        formData.append("message", msg);

        fetch("send_message.php", {method: "POST", body: formData})
            .then(res => res.text())
            .then(res => {
                document.getElementById("sendBtn").querySelector('i').className = 'fas fa-paper-plane';
                if (res === "success") {
                    document.getElementById("message").value = "";
                    document.getElementById("message").style.height = 'auto';
                    fetchMessages();
                    showNotification('Message sent', 'success');
                } else {
                    showNotification('Failed to send', 'error');
                }
            });
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 2000);
}

// Auto-resize textarea
document.getElementById("message").addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

// Event listeners
document.getElementById("sendBtn").addEventListener("click", sendMessage);
document.getElementById("message").addEventListener("keypress", function(e) {
    if(e.which === 13 && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Settings modal
document.getElementById("settingsBtn").addEventListener("click", () => {
    document.getElementById("settingsModal").style.display = 'block';
});

document.getElementById("closeSettings").addEventListener("click", () => {
    document.getElementById("settingsModal").style.display = 'none';
});

window.addEventListener("click", function(e) {
    if (e.target === document.getElementById("settingsModal")) {
        document.getElementById("settingsModal").style.display = 'none';
    }
});

// Toggle switches
document.querySelectorAll('.toggle').forEach(toggle => {
    toggle.addEventListener('click', function() {
        this.classList.toggle('active');
        if(this.id === 'soundToggle') soundEnabled = this.classList.contains('active');
        if(this.id === 'autoScrollToggle') autoScrollEnabled = this.classList.contains('active');
    });
});

// Dropdown menu
document.getElementById("moreBtn").addEventListener("click", (e) => {
    e.stopPropagation();
    const dropdown = document.getElementById("dropdownMenu");
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener("click", () => {
    document.getElementById("dropdownMenu").style.display = 'none';
    document.getElementById("emojiPicker").style.display = 'none';
});

// Dropdown actions
document.getElementById("clearChat").addEventListener("click", (e) => {
    e.preventDefault();
    if(confirm('Clear chat history?')) {
        fetch('clear_chat.php', { method: 'POST' })
            .then(() => {
                document.getElementById("chat-box").innerHTML = '';
                showNotification('Chat cleared', 'success');
            });
    }
});

document.getElementById("exportChat").addEventListener("click", (e) => {
    e.preventDefault();
    const content = document.getElementById("chat-box").textContent;
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'faculty_chat_export.txt';
    a.click();
    URL.revokeObjectURL(url);
    showNotification('Chat exported', 'success');
});

document.getElementById("refresh").addEventListener("click", (e) => {
    e.preventDefault();
    fetchMessages();
    showNotification('Refreshed', 'info');
});

// Emoji picker
document.getElementById("emojiBtn").addEventListener("click", (e) => {
    e.stopPropagation();
    const picker = document.getElementById("emojiPicker");
    picker.style.display = picker.style.display === 'block' ? 'none' : 'block';
});

document.querySelectorAll('.emoji-item').forEach(emoji => {
    emoji.addEventListener('click', function() {
        const emojiText = this.textContent;
        const textarea = document.getElementById("message");
        const pos = textarea.selectionStart;
        const text = textarea.value;
        textarea.value = text.slice(0, pos) + emojiText + text.slice(pos);
        textarea.focus();
        textarea.setSelectionRange(pos + emojiText.length, pos + emojiText.length);
        document.getElementById("emojiPicker").style.display = 'none';
    });
});

// Initialize
setInterval(fetchMessages, 2000);
fetchMessages();
document.getElementById("message").focus();
</script>
</body>
</html>