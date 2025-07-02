<?php
session_start();
include "config.php";

// 🛡️ Redirect non-admin users
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$admin_msg = "";

// ✅ Handle new admin form
if (isset($_POST["add_admin"])) {
    $new_email = mysqli_real_escape_string($conn, $_POST["new_admin_email"]);
    $new_password = password_hash($_POST["new_admin_password"], PASSWORD_DEFAULT);

    // Check if email exists
    $check = $conn->query("SELECT * FROM users WHERE email = '$new_email'");
    if ($check->num_rows > 0) {
        $admin_msg = "⚠️ This email is already registered.";
    } else {
        $insert = "INSERT INTO users (email, password, role) VALUES ('$new_email', '$new_password', 'admin')";
        if ($conn->query($insert)) {
            $admin_msg = "✅ New admin added successfully!";
        } else {
            $admin_msg = "❌ Failed to add admin: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – MDC Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #4a6741 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --text-light: #95a5a6;
            --shadow-light: 0 8px 32px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 12px 40px rgba(0, 0, 0, 0.15);
            --shadow-heavy: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            min-height: 100vh;
            color: var(--text-primary);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="0.5" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
            z-index: 1;
        }

        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 2;
        }

        .shape {
            position: absolute;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 15%;
            animation-delay: -5s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 20%;
            left: 20%;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }

        .sidebar {
            width: 300px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1000;
            padding: 0;
            overflow: hidden;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            z-index: -1;
        }

        .sidebar-header {
            padding: 40px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
        }

        .sidebar-header h2 {
            color: white;
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            font-weight: 500;
        }

        .nav-menu {
            padding: 20px 0;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 18px 30px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
            margin: 2px 15px;
            border-radius: 12px;
        }

        .nav-item i {
            width: 20px;
            margin-right: 15px;
            font-size: 18px;
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--success-gradient);
            border-radius: 0 4px 4px 0;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-item:hover::before {
            transform: scaleY(1);
        }

        .nav-item.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-item.active::before {
            transform: scaleY(1);
        }

        .logout-section {
            position: absolute;
            bottom: 30px;
            left: 15px;
            right: 15px;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px 20px;
            background: var(--danger-gradient);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .logout-btn i {
            margin-right: 10px;
        }

        .main-content {
            margin-left: 300px;
            padding: 40px 50px;
            min-height: 100vh;
            position: relative;
            z-index: 10;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 25px 35px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            box-shadow: var(--shadow-light);
        }

        .welcome-section h1 {
            font-size: 36px;
            font-weight: 800;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .welcome-section p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
            font-weight: 500;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: var(--success-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            font-weight: bold;
            box-shadow: var(--shadow-light);
        }

        .user-details h3 {
            color: white;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-details p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 50px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--success-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-heavy);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:nth-child(2)::before {
            background: var(--warning-gradient);
        }

        .stat-card:nth-child(3)::before {
            background: var(--secondary-gradient);
        }

        .stat-card:nth-child(4)::before {
            background: var(--danger-gradient);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: white;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.2);
        }

        .stat-card h3 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .stat-card p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 15px;
            line-height: 1.6;
        }

        .admin-form-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 50px;
            border-radius: 25px;
            box-shadow: var(--shadow-medium);
            max-width: 600px;
            position: relative;
            overflow: hidden;
        }

        .admin-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--primary-gradient);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .form-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            color: white;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 18px 25px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            color: white;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .submit-btn {
            width: 100%;
            padding: 20px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: var(--shadow-light);
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-heavy);
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .status-message {
            margin-top: 25px;
            padding: 20px 25px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .status-message.success {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border-color: rgba(76, 175, 80, 0.3);
        }

        .status-message.warning {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border-color: rgba(244, 67, 54, 0.3);
        }

        .status-message i {
            font-size: 18px;
        }

        /* Mobile Responsiveness */
        @media (max-width: 1024px) {
            .sidebar {
                width: 260px;
            }
            
            .main-content {
                margin-left: 260px;
                padding: 30px 35px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            }
            
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .top-bar {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-form-container {
                padding: 30px 25px;
            }
        }

        /* Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .stat-card {
            animation: slideInUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        .admin-form-container {
            animation: slideInUp 0.8s ease forwards;
            animation-delay: 0.5s;
        }

        .sidebar {
            animation: slideInLeft 0.6s ease forwards;
        }

        .top-bar {
            animation: fadeIn 0.8s ease forwards;
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>MDC Admin</h2>
            <p>Management Dashboard</p>
        </div>
        
        <nav class="nav-menu">
            <a href="#" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="admin_post_announcement.php" class="nav-item">
                <i class="fas fa-bullhorn"></i>
                Post Announcement
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-calendar-plus"></i>
                Create Event
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-users"></i>
                View Registrations
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-check-circle"></i>
                Approve Participation
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-envelope"></i>
                Messages
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-tshirt"></i>
                Merchandise
            </a>
        </nav>
        
        <div class="logout-section">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="welcome-section">
                <h1>Welcome Back, Admin</h1>
                <p>Manage your club with ease and efficiency</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="user-details">
                    <h3>Administrator</h3>
                    <p>Super Admin</p>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h3>New Event</h3>
                <p>Create and schedule upcoming events for your club members</p>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Registrations</h3>
                <p>View and manage student registrations and participation</p>
            </div>
            
            <a href="admin_post_announcement.php" style="text-decoration: none; color: inherit;">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3>Announcements</h3>
                    <p>Post important updates for students and faculty</p>
                </div>
            </a>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tshirt"></i>
                </div>
                <h3>Merchandise</h3>
                <p>Manage and update T-shirts, hoodies, and other items</p>
            </div>
        </div>

        <div class="admin-form-container">
            <div class="form-header">
                <h2>Add New Admin</h2>
                <p>Grant administrative privileges to new members</p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="admin-email">Admin Email</label>
                    <input type="email" id="admin-email" name="new_admin_email" placeholder="Enter admin email address" required>
                </div>
                
                <div class="form-group">
                    <label for="admin-password">Admin Password</label>
                    <input type="password" id="admin-password" name="new_admin_password" placeholder="Create a secure password" required>
                </div>
                
                <button type="submit" name="add_admin" class="submit-btn">
                    <i class="fas fa-user-plus"></i>
                    Add Admin
                </button>
            </form>

            <?php if ($admin_msg): ?>
                <div class="status-message <?= str_contains($admin_msg, '⚠️') || str_contains($admin_msg, '❌') ? 'warning' : 'success' ?>">
                    <i class="fas <?= str_contains($admin_msg, '⚠️') || str_contains($admin_msg, '❌') ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                    <?= $admin_msg ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>