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
        body {
            display: flex;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f4f4f4;
        }

        .sidebar {
            width: 240px;
            background-color: #141e30;
            color: white;
            height: 100vh;
            padding: 20px 0;
            position: fixed;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #fdd835;
        }

        .sidebar a {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: background 0.3s;
        }

        .sidebar a:hover {
            background: #1f2f49;
        }

        .main-content {
            margin-left: 240px;
            padding: 40px;
            width: 100%;
        }

        .main-content h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #141e30;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .card p {
            font-size: 14px;
            color: #555;
        }

        .logout {
            margin-top: 40px;
            text-align: center;
        }

        .logout a {
            color: #fdd835;
            text-decoration: none;
            font-weight: bold;
        }

        .logout a:hover {
            text-decoration: underline;
        }

        .add-admin-form {
            max-width: 500px;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .add-admin-form h2 {
            margin-bottom: 20px;
            color: #141e30;
        }

        .add-admin-form input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
        }

        .add-admin-form button {
            width: 100%;
            padding: 12px;
            background: #141e30;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
        }

        .add-admin-form button:hover {
            background: #0e1524;
        }

        .status-msg {
            margin-top: 15px;
            font-weight: bold;
            color: green;
        }

        .status-msg.warning {
            color: #c0392b;
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
