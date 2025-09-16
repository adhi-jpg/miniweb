<?php
session_start();
include "config.php";

// 🛡️ Enhanced session validation and cache control
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin" || empty($_SESSION["user_id"])) {
    // Destroy any existing session data
    session_unset();
    session_destroy();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    
    // Redirect to login
    header("Location: login.php");
    exit();
}

// 🔒 Prevent page caching to avoid back button issues
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #6366f1;
            --secondary: #ec4899;
            --success: #10b981;
            --danger: #ef4444;
            --glass: rgba(255, 255, 255, 0.1);
            --border: rgba(255, 255, 255, 0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
            color: white;
        }

        @keyframes gradient {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* Floating Elements */
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .shape {
            position: absolute;
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            border-radius: 50%;
            animation: float 20s infinite ease-in-out;
        }

        .shape:nth-child(1) { width: 100px; height: 100px; top: 10%; left: 10%; }
        .shape:nth-child(2) { width: 150px; height: 150px; top: 60%; right: 15%; animation-delay: -5s; }
        .shape:nth-child(3) { width: 80px; height: 80px; bottom: 20%; left: 20%; animation-delay: -10s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 30px;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-header h2 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .sidebar-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .nav-menu { padding: 20px 0; }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 2px 15px;
            border-radius: 12px;
            position: relative;
        }

        .nav-item i { width: 20px; margin-right: 15px; }

        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(5px);
        }

        .nav-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-item:hover::before, .nav-item.active::before {
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
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .logout-btn i { margin-right: 10px; }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 40px;
            min-height: 100vh;
            position: relative;
            z-index: 10;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 30px;
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }

        .welcome-section h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .welcome-section p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--success), var(--primary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .user-details h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-details p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: inherit;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 0.2);
        }

        .stat-card h3 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .stat-card p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            line-height: 1.5;
        }

        /* Form Container */
        .admin-form-container {
            background: var(--glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            max-width: 500px;
            margin: 0 auto;
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .form-header p {
            color: rgba(255, 255, 255, 0.8);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: white;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid var(--border);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .status-message {
            margin-top: 20px;
            padding: 15px 20px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
        }

        .status-message.success {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-message.warning {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
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
                padding: 30px 20px;
            }
        }

        /* Animations */
        .stat-card {
            animation: slideUp 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            <a href="create_event.php" class="nav-item">
                <i class="fas fa-calendar-plus"></i>
                Create Event
            </a>
            <a href="view_event_registrations.php" class="nav-item">
                <i class="fas fa-users"></i>
                View Registrations
            </a>
            <a href="chat_admin.php" class="nav-item">
                <i class="fas fa-envelope"></i>
                Messages
            </a>
             <a href="admin_attendance.php" class="nav-item">
                <i class="fas fa-user-check"></i>
                Attendance
            </a>
               <a href="admin_view_attendance.php" class="nav-item">
                <i class="fas fa-chart-line"></i>
                View Attendance
            </a>
            <a href="admin_manage_merchandise.php" class="nav-item">
                <i class="fas fa-tshirt"></i>
                Merchandise
            </a>
             <a href="report.php" class="nav-item">
                <i class="fas fa-file-alt"></i>
                Reports
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
    <!-- 1. New Event -->
    <a href="create_event.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-calendar-plus"></i>
        </div>
        <h3>New Event</h3>
        <p>Create and schedule upcoming events for your club members</p>
    </a>

    <!-- 2. Manage Events -->
    <a href="manage_events.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-calendar-minus"></i>
        </div>
        <h3>Manage events</h3>
        <p>edit,delete and update events</p>
    </a>

    <!-- 3. Registrations -->
    <a href="view_event_registrations.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <h3>Registrations</h3>
        <p>View and manage student registrations and participation</p>
    </a>

    <!-- 4. Announcements -->
    <a href="admin_post_announcement.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-bullhorn"></i>
        </div>
        <h3>Announcements</h3>
        <p>Post important updates for students and faculty</p><br>
    </a>

    <!-- 5. Media -->
    <a href="admin_media_upload.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-image"></i>
        </div>
        <h3>Media</h3>
        <p>Upload media of program history</p><br>
    </a>

    <!-- 6. Merchandise -->
    <a href="admin_manage_merchandise.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-tshirt"></i>
        </div>
        <h3>Merchandise</h3>
        <p>Manage and update T-shirts, hoodies, and other items</p>
    </a>

    <!-- 7. Orders -->
    <a href="admin_view_orders.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-eye"></i>
        </div>
        <h3>Orders</h3>
        <p>to view the orders by the students for mdc products</p><br>
    </a>

    <!-- 8. Reports -->
    <a href="report.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-file-alt"></i>
        </div>
        <h3>Reports</h3>
        <p>Submit reports on club activities</p>
    </a>

    <a href="admin_event_proposal.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-file-signature"></i>
        </div>
        <h3>event proposal</h3>
        <p>proposal for creating event to faculty</p>
    </a>

    
    <a href="admin_delivery.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-truck-fast"></i>
        </div>
        <h3>Delivery status</h3>
        <p>Delivery status of ordersof mdc products</p>
    </a>

    <!-- 9. View Status of Reports -->
    <a href="admin_view_report_status.php" class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-eye"></i>
        </div>
        <h3>View status of reports</h3>
        <p>to view the reports are approved or rejected by the faculty</p><br>
    </a>
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