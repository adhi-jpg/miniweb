<?php
session_start();

// Strong cache prevention
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if session doesn't exist or wrong role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

include "config.php";

$user_id = $_SESSION["user_id"];
$student = $conn->query("SELECT name, roll_number, department FROM student_profiles WHERE user_id = $user_id")->fetch_assoc();
?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard – MDC Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right, #f7f8fa, #dbe2f1);
        }

        header {
            background-color: #141e30;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
        }

        header h1 {
            font-size: 24px;
        }

        .logout a {
            background: #fdd835;
            color: #141e30;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
        }

        .container {
            padding: 40px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h2 {
            margin-top: 0;
            color: #141e30;
            font-size: 22px;
            border-left: 5px solid #fdd835;
            padding-left: 10px;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: #fff;
            border-left: 5px solid #141e30;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-4px);
        }

        .announcement {
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .announcement strong {
            display: block;
            color: #333;
        }

        .announcement p {
            margin: 5px 0 0;
            color: #444;
        }

        .feedback textarea {
            width: 100%;
            border-radius: 6px;
            padding: 12px;
            border: 1px solid #ccc;
            resize: vertical;
            font-size: 14px;
        }

        .feedback button {
            margin-top: 10px;
            padding: 10px 20px;
            background: #141e30;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .feedback button:hover {
            background: #0f1725;
        }

        .msg {
            color: green;
            font-weight: bold;
            margin-top: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        tr:nth-child(even) {
            background: #f8f8f8;
        }
    </style>
</head>
<script>
<script>
if (performance.navigation.type === 2) {
    // Force redirect if the user is navigating with the back button
    location.href = "logout.php";
}
</script>

</script>

<body>

<header>
    <h1>Welcome, <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['roll_number']) ?>)</h1>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="container">

    <!-- 📢 Announcements -->
    <div class="section">
        <h2>📢 Announcements</h2>
        <?php
        $announcements = $conn->query("SELECT * FROM announcements ORDER BY posted_on DESC LIMIT 5");
        if ($announcements->num_rows > 0) {
            while ($a = $announcements->fetch_assoc()) {
                echo "<div class='announcement'>
                        <strong>" . date("d M Y", strtotime($a["posted_on"])) . "</strong>
                        <p>" . htmlspecialchars($a["message"]) . "</p>
                      </div>";
            }
        } else {
            echo "<p>No announcements available.</p>";
        }
        ?>
    </div>

    <!-- 📦 Original Dashboard Cards -->
    <div class="section">
        <h2>🎯 Quick Access</h2>
        <div class="card-grid">
            <div class="card">
                <h3>🎉 My Events</h3>
                <p>View upcoming and registered events.</p>
            </div>

            <div class="card">
                <h3>✅ My Participation</h3>
                <p>Track your event history and approvals.</p>
            </div>

            <div class="card">
                <h3>📝 Submit Feedback</h3>
                <p>Share suggestions or report issues.</p>
            </div>

            <div class="card">
                <h3>📚 Resources</h3>
                <p>Access club materials and past content.</p>
            </div>
        </div>
    </div>

</div>

</body>
</html>
