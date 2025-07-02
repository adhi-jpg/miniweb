<?php
session_start();
include "config.php";

// Access control
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

$msg = "";

// Handle new announcement
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"])) {
    $message = mysqli_real_escape_string($conn, $_POST["message"]);
    if (!empty($message)) {
        $sql = "INSERT INTO announcements (message) VALUES ('$message')";
        if ($conn->query($sql)) {
            $msg = "✅ Announcement posted!";
        } else {
            $msg = "❌ Failed to post announcement: " . $conn->error;
        }
    }
}

// Handle delete (optional)
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    $conn->query("DELETE FROM announcements WHERE id = $id");
    header("Location: admin_post_announcement.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Announcement – Admin</title>
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
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
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
            top: 15%;
            right: 20%;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 70%;
            left: 10%;
            animation-delay: -7s;
        }

        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            bottom: 30%;
            right: 30%;
            animation-delay: -14s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(15px) rotate(240deg); }
        }

        .header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 25px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 10;
            box-shadow: var(--shadow-light);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            font-size: 32px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
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

        .container {
            padding: 50px;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }

        .section {
            margin-bottom: 50px;
        }

        .section-title {
            color: white;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 30px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-title i {
            font-size: 28px;
            color: #f39c12;
        }

        .announcement-form {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 40px;
            border-radius: 25px;
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .announcement-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--success-gradient);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: white;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .announcement-textarea {
            width: 100%;
            height: 150px;
            padding: 20px 25px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            color: white;
            font-size: 16px;
            font-weight: 500;
            font-family: inherit;
            resize: vertical;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .announcement-textarea::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        .announcement-textarea:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .submit-btn {
            padding: 18px 35px;
            background: var(--warning-gradient);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: var(--shadow-light);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-heavy);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .status-message {
            margin-top: 20px;
            padding: 18px 25px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border-color: rgba(76, 175, 80, 0.3);
        }

        .status-message i {
            font-size: 18px;
        }

        .announcements-grid {
            display: grid;
            gap: 25px;
        }

        .announcement-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-light);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .announcement-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .announcement-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-heavy);
        }

        .announcement-card:hover::before {
            transform: scaleX(1);
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .announcement-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 500;
        }

        .announcement-meta i {
            color: #f39c12;
        }

        .delete-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: var(--danger-gradient);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-light);
        }

        .delete-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .announcement-content {
            color: white;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .announcement-date {
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .announcement-date i {
            color: #f39c12;
        }

        .empty-state {
            text-align: center;
            padding: 60px 30px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            color: rgba(255, 255, 255, 0.8);
        }

        .empty-state i {
            font-size: 48px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: white;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .empty-state p {
            font-size: 16px;
            line-height: 1.6;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .header {
                padding: 20px 25px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .header h1 {
                font-size: 24px;
            }

            .header-actions {
                flex-direction: column;
                gap: 10px;
            }

            .container {
                padding: 30px 20px;
            }

            .section-title {
                font-size: 28px;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }

            .announcement-form {
                padding: 30px 25px;
            }

            .announcement-textarea {
                height: 120px;
            }

            .announcement-header {
                flex-direction: column;
                gap: 15px;
            }

            .announcement-meta {
                justify-content: center;
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

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .announcement-form {
            animation: slideInUp 0.6s ease forwards;
        }

        .announcement-card {
            animation: slideInUp 0.6s ease forwards;
        }

        .announcement-card:nth-child(1) { animation-delay: 0.1s; }
        .announcement-card:nth-child(2) { animation-delay: 0.2s; }
        .announcement-card:nth-child(3) { animation-delay: 0.3s; }
        .announcement-card:nth-child(4) { animation-delay: 0.4s; }
        .announcement-card:nth-child(5) { animation-delay: 0.5s; }

        .header {
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

    <header class="header">
        <h1>
            <i class="fas fa-bullhorn"></i>
            Announcement Management
        </h1>
        <div class="header-actions">
            <a href="dashboard_admin.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </header>

    <div class="container">
        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-plus-circle"></i>
                Create New Announcement
            </h2>
            
            <div class="announcement-form">
                <?php if ($msg): ?>
                    <div class="status-message">
                        <i class="fas fa-check-circle"></i>
                        <?= $msg ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="announcement-message">Announcement Message</label>
                        <textarea 
                            id="announcement-message" 
                            name="message" 
                            class="announcement-textarea" 
                            placeholder="Enter your announcement message here... Share important updates, events, or news with your club members."
                            required
                        ></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Post Announcement
                    </button>
                </form>
            </div>
        </div>

        <div class="section">
            <h2 class="section-title">
                <i class="fas fa-history"></i>
                Previous Announcements
            </h2>
            
            <div class="announcements-grid">
                <?php
                $result = $conn->query("SELECT * FROM announcements ORDER BY posted_on DESC");
                $hasAnnouncements = false;
                while ($row = $result->fetch_assoc()):
                    $hasAnnouncements = true;
                ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <div class="announcement-meta">
                                <i class="fas fa-bullhorn"></i>
                                Announcement
                            </div>
                            <a href="?delete=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this announcement? This action cannot be undone.')">
                                <i class="fas fa-trash-alt"></i>
                                Delete
                            </a>
                        </div>
                        
                        <div class="announcement-content">
                            <?= nl2br(htmlspecialchars($row["message"])) ?>
                        </div>
                        
                        <div class="announcement-date">
                            <i class="fas fa-calendar-alt"></i>
                            Posted on <?= date("d M Y – h:i A", strtotime($row["posted_on"])) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <?php if (!$hasAnnouncements): ?>
                    <div class="empty-state">
                        <i class="fas fa-bullhorn"></i>
                        <h3>No Announcements Yet</h3>
                        <p>Create your first announcement to share important updates with your club members.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
