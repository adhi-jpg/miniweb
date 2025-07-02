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
    <title>Admin Dashboard – MDC Club</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            display: flex;
            font-family: 'Inter', 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #2c3e50;
        }

        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            height: 100vh;
            padding: 0;
            position: fixed;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar h2 {
            text-align: center;
            margin: 0;
            padding: 30px 20px;
            color: #f39c12;
            font-size: 24px;
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 18px 25px;
            color: #ecf0f1;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            position: relative;
        }

        .sidebar a::before {
            content: '';
            width: 8px;
            height: 8px;
            background: #f39c12;
            border-radius: 50%;
            margin-right: 12px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar a:hover {
            background: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            color: #ffffff;
            transform: translateX(5px);
        }

        .sidebar a:hover::before {
            opacity: 1;
        }

        .logout {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            padding: 0 25px;
        }

        .logout a {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
            padding: 15px 20px;
            border: 2px solid #e74c3c;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s ease;
            display: block;
        }

        .logout a:hover {
            background: #e74c3c;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .main-content {
            margin-left: 280px;
            padding: 40px 50px;
            width: calc(100% - 280px);
            min-height: 100vh;
        }

        .main-content h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #2c3e50;
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .main-content h1::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #f39c12, #e67e22);
            border-radius: 2px;
            margin-top: 15px;
            margin-bottom: 40px;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 60px;
        }

        .card {
            background: white;
            padding: 30px 25px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3498db, #2980b9);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .card:hover::before {
            transform: scaleX(1);
        }

        .card h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: 600;
        }

        .card p {
            font-size: 15px;
            color: #7f8c8d;
            line-height: 1.6;
            font-weight: 400;
        }

        .add-admin-form {
            max-width: 550px;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .add-admin-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #2980b9, #f39c12);
        }

        .add-admin-form h2 {
            margin-bottom: 30px;
            color: #2c3e50;
            font-size: 26px;
            font-weight: 700;
            position: relative;
            padding-bottom: 15px;
        }

        .add-admin-form h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #f39c12, #e67e22);
            border-radius: 2px;
        }

        .add-admin-form input {
            width: 100%;
            padding: 16px 20px;
            margin: 12px 0;
            border: 2px solid #ecf0f1;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: #fafbfc;
        }

        .add-admin-form input:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }

        .add-admin-form input::placeholder {
            color: #95a5a6;
            font-weight: 400;
        }

        .add-admin-form button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
        }

        .add-admin-form button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .add-admin-form button:hover {
            background: linear-gradient(135deg, #2980b9, #1f5f8b);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.3);
        }

        .add-admin-form button:hover::before {
            left: 100%;
        }

        .add-admin-form button:active {
            transform: translateY(0);
        }

        .status-msg {
            margin-top: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border-left: 5px solid #1e8449;
        }

        .status-msg.warning {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            border-left-color: #a93226;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 250px;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            
            .card-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .add-admin-form {
                padding: 30px 25px;
            }
        }

        /* Subtle animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }

        .add-admin-form {
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 0.5s;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="#">Dashboard</a>
    <a href="admin_post_announcement.php">Post Announcement</a>
    <a href="#">Create Event</a>
    <a href="#">View Registrations</a>
    <a href="#">Approve Participation</a>
    <a href="#">Messages</a>
    <a href="#">Merchandise</a>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main-content">
    <h1>Welcome, Admin</h1>

    <div class="card-grid">
        <div class="card">
            <h3>New Event</h3>
            <p>Create and schedule upcoming events.</p>
        </div>
        <div class="card">
            <h3>Registrations</h3>
            <p>View and manage student registrations.</p>
        </div>
        <a href="admin_post_announcement.php" style="text-decoration: none;color: inherit;" >
        <div class="card">
            <h3>Announcements</h3>
            <p>Post updates for students and faculty.</p>
        </div></a>
        <div class="card">
            <h3>Merchandise</h3>
            <p>List or update T-shirts, hoodies, etc.</p>
        </div>
    </div>

    <div class="add-admin-form">
        <h2>Add New Admin</h2>
        <form method="POST">
            <input type="email" name="new_admin_email" placeholder="Admin Email" required>
            <input type="password" name="new_admin_password" placeholder="Admin Password" required>
            <button type="submit" name="add_admin">Add Admin</button>
        </form>

        <?php if ($admin_msg): ?>
            <p class="status-msg <?= str_contains($admin_msg, '⚠️') || str_contains($admin_msg, '❌') ? 'warning' : '' ?>">
                <?= $admin_msg ?>
            </p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
