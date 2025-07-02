<?php
session_start();
include "config.php";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = mysqli_real_escape_string($conn, $_POST["identifier"]);
    $password = trim($_POST["password"]);

    // Check for student login via roll number
    $sqlStudent = "SELECT u.user_id, u.password, u.role FROM users u
                   JOIN student_profiles s ON u.user_id = s.user_id
                   WHERE s.roll_number = '$id'";
    $resStudent = $conn->query($sqlStudent);

    if ($resStudent->num_rows === 1) {
        $row = $resStudent->fetch_assoc();
        if (password_verify($password, $row["password"])) {
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["role"] = $row["role"];
            header("Location: dashboard_student.php");
            exit();
        } else {
            $msg = "❌ Incorrect password.";
        }
    } else {
        // Check for admin or faculty login via email
        $sqlUser = "SELECT * FROM users WHERE email = '$id'";
        $resUser = $conn->query($sqlUser);

        if ($resUser->num_rows === 1) {
            $row = $resUser->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["role"] = $row["role"];
                if ($row["role"] === "admin") {
                    header("Location: dashboard_admin.php");
                } elseif ($row["role"] === "faculty") {
                    header("Location: dashboard_faculty.php");
                }
                exit();
            } else {
                $msg = "❌ Incorrect password.";
            }
        } else {
            $msg = "⚠️ User not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDC Club – Login</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --glass-bg: rgba(255, 255, 255, 0.12);
            --glass-border: rgba(255, 255, 255, 0.18);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.8);
            --accent-color: #ffd700;
            --error-color: #ff6b6b;
            --success-color: #51cf66;
        }

        body, html {
            height: 100%;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            overflow: hidden;
            background: var(--primary-gradient);
        }

        /* 🎥 Video Background */
        .bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            z-index: -3;
            opacity: 0.6;
        }

        /* 🌟 Animated Background Overlay */
        .bg-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, 
                rgba(102, 126, 234, 0.7) 0%, 
                rgba(118, 75, 162, 0.7) 100%);
            backdrop-filter: blur(1px);
        }

        .bg-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(10px, 10px); }
        }

        /* 🌈 Floating Orbs */
        .floating-orbs {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            backdrop-filter: blur(10px);
            animation: float 6s ease-in-out infinite;
        }

        .orb:nth-child(1) {
            width: 200px;
            height: 200px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .orb:nth-child(2) {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }

        .orb:nth-child(3) {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }

        /* 💎 Main Login Container */
        .login-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            padding: 48px 40px;
            border-radius: 24px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.05) inset;
            width: 90%;
            max-width: 420px;
            color: var(--text-primary);
            opacity: 0;
            transform: scale(0.9) translate(-50%, -50%);
            animation: slideInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: scale(1) translate(-50%, -50%);
            }
        }

        /* 🏆 Header Section */
        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-header h2 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--accent-color), #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 400;
        }

        /* 📝 Form Styling */
        .form-group {
            position: relative;
            margin-bottom: 24px;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            font-size: 16px;
            color: var(--text-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }

        .form-input::placeholder {
            color: var(--text-secondary);
            font-weight: 400;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.15);
            background: rgba(255, 255, 255, 0.12);
        }

        /* 🎯 Submit Button */
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--accent-color), #ffed4e);
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            color: #1a1a1a;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(255, 215, 0, 0.3);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* 🔗 Links */
        .form-links {
            text-align: center;
            margin-top: 24px;
        }

        .forgot-password {
            display: inline-block;
            color: var(--accent-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: #fff;
            text-shadow: 0 0 8px var(--accent-color);
        }

        .register-link {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 16px;
        }

        .register-link a {
            color: #4fc3f7;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .register-link a:hover {
            color: #81d4fa;
            text-shadow: 0 0 8px rgba(79, 195, 247, 0.5);
        }

        /* 🚨 Status Messages */
        .status {
            text-align: center;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            font-size: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid transparent;
            opacity: 0;
            transform: translateY(-10px);
            animation: statusSlideIn 0.5s ease forwards;
        }

        @keyframes statusSlideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status.error {
            background: rgba(255, 107, 107, 0.15);
            border-color: rgba(255, 107, 107, 0.3);
            color: #ff6b6b;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.2);
        }

        .status.success {
            background: rgba(81, 207, 102, 0.15);
            border-color: rgba(81, 207, 102, 0.3);
            color: #51cf66;
            box-shadow: 0 4px 12px rgba(81, 207, 102, 0.2);
        }

        .status.warning {
            background: rgba(255, 193, 7, 0.15);
            border-color: rgba(255, 193, 7, 0.3);
            color: #ffc107;
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
        }

        .status.info {
            background: rgba(33, 150, 243, 0.15);
            border-color: rgba(33, 150, 243, 0.3);
            color: #2196f3;
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
        }

        /* Status message icons */
        .status::before {
            content: '';
            display: inline-block;
            width: 18px;
            height: 18px;
            margin-right: 8px;
            vertical-align: middle;
        }

        .status.error::before {
            content: '❌';
        }

        .status.success::before {
            content: '✅';
        }

        .status.warning::before {
            content: '⚠️';
        }

        .status.info::before {
            content: 'ℹ️';
        }

        /* 📱 Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                padding: 32px 24px;
                margin: 20px;
                width: calc(100% - 40px);
            }

            .login-header h2 {
                font-size: 24px;
            }

            .form-input, .submit-btn {
                padding: 14px 16px;
                font-size: 15px;
            }
        }

        /* ✨ Loading Animation */
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .loading .submit-btn {
            background: linear-gradient(135deg, #ccc, #999);
            cursor: not-allowed;
        }

        .loading .submit-btn::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid #1a1a1a;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 🎨 Hover Effects for Container */
        .login-container:hover {
            box-shadow: 
                0 35px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        }
    </style>
</head>
<body>

<!-- 🎥 Background Video -->
<video class="bg-video" autoplay muted loop playsinline>
    <source src="video.mp4" type="video/mp4">
    <source src="video.webm" type="video/webm">
    <source src="video.ogv" type="video/ogg">
    Your browser does not support the video tag.
</video>

<!-- 🌌 Background Elements -->
<div class="bg-container"></div>
<div class="floating-orbs">
    <div class="orb"></div>
    <div class="orb"></div>
    <div class="orb"></div>
</div>

<!-- 🏛️ Main Login Container -->
<div class="login-container">
    <div class="login-header">
        <h2>MDC Club</h2>
        <p>Welcome back! Please sign in to your account</p>
    </div>

    <!-- Status Messages (PHP Integration) -->
    <?php if (!empty($msg)): ?>
        <?php 
        // Determine message type based on content
        $isSuccess = (
            stripos($msg, 'success') !== false || 
            stripos($msg, 'welcome') !== false || 
            stripos($msg, 'login successful') !== false ||
            stripos($msg, 'logged in') !== false
        );
        $messageClass = $isSuccess ? 'success' : 'error';
        ?>
        <div class="status <?= $messageClass ?>" id="statusMessage">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="loginForm">
        <div class="form-group">
            <input type="text" name="identifier" class="form-input" placeholder="Email or Roll Number" required>
        </div>
        
        <div class="form-group">
            <input type="password" name="password" class="form-input" placeholder="Password" required>
        </div>
        
        <button type="submit" class="submit-btn">Sign In</button>
        
        <div class="form-links">
            <a href="forgot_password.php" class="forgot-password">Forgot your password?</a>
        </div>
    </form>
    
    <div class="register-link">
        Don't have an account? <a href="register.php">Create one here</a>
    </div>
</div>

<script>
    // 🎯 Form Enhancement
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const container = document.querySelector('.login-container');
        container.classList.add('loading');
        
        // Remove loading state after 3 seconds (adjust based on your backend response time)
        setTimeout(() => {
            container.classList.remove('loading');
        }, 3000);
    });

    // 📢 Status Message Auto-hide
    const statusMessage = document.getElementById('statusMessage');
    if (statusMessage) {
        // Auto-hide success messages after 5 seconds
        if (statusMessage.classList.contains('success')) {
            setTimeout(() => {
                statusMessage.style.opacity = '0';
                statusMessage.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 300);
            }, 5000);
        }
        
        // Auto-hide error messages after 8 seconds
        if (statusMessage.classList.contains('error')) {
            setTimeout(() => {
                statusMessage.style.opacity = '0';
                statusMessage.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    statusMessage.style.display = 'none';
                }, 300);
            }, 8000);
        }
    }

    // 🔍 Client-side Form Validation Enhancement
    const form = document.getElementById('loginForm');
    const identifierInput = form.querySelector('input[name="identifier"]');
    const passwordInput = form.querySelector('input[name="password"]');

    function showClientMessage(message, type = 'error') {
        // Remove existing client messages
        const existingMessage = document.querySelector('.client-status');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = `status ${type} client-status`;
        messageDiv.innerHTML = message;
        
        // Insert before form
        form.parentNode.insertBefore(messageDiv, form);
        
        // Auto-hide after 4 seconds
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                messageDiv.remove();
            }, 300);
        }, 4000);
    }

    // Enhanced validation
    form.addEventListener('submit', function(e) {
        const identifier = identifierInput.value.trim();
        const password = passwordInput.value.trim();

        // Basic validation
        if (!identifier) {
            e.preventDefault();
            showClientMessage('Please enter your email or roll number');
            identifierInput.focus();
            return;
        }

        if (!password) {
            e.preventDefault();
            showClientMessage('Please enter your password');
            passwordInput.focus();
            return;
        }

        if (password.length < 6) {
            e.preventDefault();
            showClientMessage('Password must be at least 6 characters long');
            passwordInput.focus();
            return;
        }

        // Email validation if identifier contains @
        if (identifier.includes('@')) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(identifier)) {
                e.preventDefault();
                showClientMessage('Please enter a valid email address');
                identifierInput.focus();
                return;
            }
        }

        // Show loading state
        const container = document.querySelector('.login-container');
        container.classList.add('loading');
        
        // Show processing message
        showClientMessage('Authenticating...', 'info');
    });
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const container = document.querySelector('.login-container');
        container.classList.add('loading');
        
        // Remove loading state after 3 seconds (adjust based on your backend response time)
        setTimeout(() => {
            container.classList.remove('loading');
        }, 3000);
    });

    // ✨ Input Focus Effects
    document.querySelectorAll('.form-input').forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.3s ease';
        });

        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // 🎪 Dynamic Background Orbs
    function createFloatingOrb() {
        const orb = document.createElement('div');
        orb.className = 'orb';
        orb.style.width = Math.random() * 100 + 50 + 'px';
        orb.style.height = orb.style.width;
        orb.style.left = Math.random() * 100 + '%';
        orb.style.top = Math.random() * 100 + '%';
        orb.style.animationDelay = Math.random() * 6 + 's';
        
        document.querySelector('.floating-orbs').appendChild(orb);
        
        setTimeout(() => {
            orb.remove();
        }, 12000);
    }

    // Create new orbs periodically
    setInterval(createFloatingOrb, 4000);

    // 🌟 Parallax Mouse Effect
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;
        
        document.querySelectorAll('.orb').forEach((orb, index) => {
            const speed = (index + 1) * 0.5;
            const x = (mouseX - 0.5) * speed * 20;
            const y = (mouseY - 0.5) * speed * 20;
            
            orb.style.transform = `translate(${x}px, ${y}px) translateY(${Math.sin(Date.now() * 0.001 + index) * 10}px)`;
        });
    });
</script>

</body>
</html>