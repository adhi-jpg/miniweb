<?php
session_start();
include "config.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Chat with Faculty</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .chat-header {
            background: linear-gradient(135deg, #4a00e0 0%, #6200ea 100%);
            color: white;
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
        
        .admin-avatar {
            width: 45px;
            height: 45px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .header-info h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .status {
            font-size: 12px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-dot {
            width: 6px;
            height: 6px;
            background: #4caf50;
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
            background: rgba(255, 255, 255, 0.2);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .header-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            padding: 10px 0;
            min-width: 180px;
            z-index: 100;
            display: none;
        }
        
        .dropdown-item {
            padding: 10px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .dropdown-item:hover { background-color: #f5f5f5; }
        
        #chat-box {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
        }
        
        #chat-box::-webkit-scrollbar { width: 6px; }
        #chat-box::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        #chat-box::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
        
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        @keyframes messageSlide {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .sent {
            background: linear-gradient(135deg, #4a00e0, #6200ea);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        
        .received {
            background: white;
            color: #333;
            margin-right: auto;
            border: 1px solid #e0e0e0;
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
            background: #ffffff;
            border-top: 1px solid #e0e0e0;
            gap: 15px;
            align-items: center;
        }
        
        .input-container { flex: 1; position: relative; }
        
        #message {
            width: 100%;
            padding: 15px 20px;
            border-radius: 25px;
            border: 2px solid #e0e0e0;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            background: #f8f9fa;
            resize: none;
            min-height: 50px;
            max-height: 100px;
        }
        
        #message:focus {
            border-color: #4a00e0;
            background: white;
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
        }
        
        #sendBtn {
            background: linear-gradient(135deg, #4a00e0, #6200ea);
            border: none;
            color: white;
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
            box-shadow: 0 5px 20px rgba(74, 0, 224, 0.4);
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
        }
        
        .emoji-btn:hover { background: rgba(74, 0, 224, 0.1); }
        
        .emoji-picker {
            position: absolute;
            bottom: 70px;
            right: 80px;
            background: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            z-index: 100;
            width: 280px;
            display: none;
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
        
        .emoji-item:hover { background: #f0f0f0; }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal h3 {
            margin-bottom: 20px;
            color: #4a00e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .close {
            float: right;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
            margin-top: -10px;
        }
        
        .close:hover { color: #333; }
        
        .setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .toggle {
            width: 40px;
            height: 20px;
            background: #ccc;
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .toggle.active { background: #4a00e0; }
        
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
        
        .notification.success { background: #4caf50; }
        .notification.error { background: #f44336; }
        .notification.info { background: #2196f3; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="chat-container">
    <div class="chat-header">
        <div class="header-left">
            <div class="admin-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="header-info">
                <h3>Admin Panel</h3>
                <div class="status">
                    <span class="status-dot"></span>
                    Faculty Communication
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

function loadMessages(){
    $.get("fetch_messages.php?role=faculty", function(data){
        $("#chat-box").html(data);
        if(autoScrollEnabled) {
            $("#chat-box").scrollTop($("#chat-box")[0].scrollHeight);
        }
    });
}

function sendMessage() {
    var msg = $("#message").val().trim();
    if(msg !== ""){
        $("#sendBtn").find('i').removeClass('fa-paper-plane').addClass('fa-spinner fa-spin');
        $.post("send_message.php", { message: msg, receiver_role: "faculty" }, function(){
            $("#message").val("");
            $("#message").css('height', 'auto');
            loadMessages();
            $("#sendBtn").find('i').removeClass('fa-spinner fa-spin').addClass('fa-paper-plane');
            showNotification('Message sent', 'success');
        }).fail(function() {
            $("#sendBtn").find('i').removeClass('fa-spinner fa-spin').addClass('fa-paper-plane');
            showNotification('Failed to send', 'error');
        });
    }
}

function showNotification(message, type) {
    const notification = $(`<div class="notification ${type}">${message}</div>`);
    $('body').append(notification);
    setTimeout(() => notification.fadeOut(300, () => notification.remove()), 2000);
}

// Auto-resize textarea
$("#message").on('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

// Event listeners
$("#sendBtn").click(sendMessage);
$("#message").keypress(function(e){
    if(e.which == 13 && !e.shiftKey){ 
        e.preventDefault();
        sendMessage();
    }
});

// Settings modal
$("#settingsBtn").click(() => $("#settingsModal").fadeIn(200));
$("#closeSettings, .modal").click(function(e) {
    if (e.target === this) $("#settingsModal").fadeOut(200);
});

// Toggle switches
$(".toggle").click(function() {
    $(this).toggleClass("active");
    if($(this).attr('id') === 'soundToggle') soundEnabled = $(this).hasClass('active');
    if($(this).attr('id') === 'autoScrollToggle') autoScrollEnabled = $(this).hasClass('active');
});

// Dropdown menu
$("#moreBtn").click(e => { e.stopPropagation(); $("#dropdownMenu").toggle(); });
$(document).click(() => { $("#dropdownMenu, #emojiPicker").hide(); });

// Dropdown actions
$("#clearChat").click(e => {
    e.preventDefault();
    if(confirm('Clear chat history?')) {
        $.post('clear_chat.php', () => {
            $("#chat-box").empty();
            showNotification('Chat cleared', 'success');
        });
    }
});

$("#exportChat").click(e => {
    e.preventDefault();
    const content = $("#chat-box").text();
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'chat_export.txt';
    a.click();
    URL.revokeObjectURL(url);
    showNotification('Chat exported', 'success');
});

$("#refresh").click(e => {
    e.preventDefault();
    loadMessages();
    showNotification('Refreshed', 'info');
});

// Emoji picker
$("#emojiBtn").click(e => {
    e.stopPropagation();
    $("#emojiPicker").toggle();
});

$(".emoji-item").click(function() {
    const emoji = $(this).text();
    const textarea = $("#message")[0];
    const pos = textarea.selectionStart;
    const text = textarea.value;
    textarea.value = text.slice(0, pos) + emoji + text.slice(pos);
    textarea.focus();
    textarea.setSelectionRange(pos + emoji.length, pos + emoji.length);
    $("#emojiPicker").hide();
});

// Initialize
loadMessages();
setInterval(loadMessages, 2000);
$("#message").focus();
</script>
</body>
</html>