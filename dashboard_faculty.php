<?php
session_start();
include "config.php";

// ✅ Access control
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

// Optionally, fetch faculty details if needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard – MDC Club</title>
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
            align-items: center;
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

        .logout a:hover {
            background: #ffe94d;
        }

        .container {
            padding: 40px;
        }

        .section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease;
            text-align: center;
        }

        .card:hover {
            transform: translateY(-6px);
        }

        .card h3 {
            margin-top: 0;
            color: #141e30;
        }

        .card p {
            color: #555;
        }

        @media(max-width: 600px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>MDC Club – Faculty Dashboard</h1>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="container">
    <div class="section">
        <div class="card">
            <h3>✅ Approve Event Participation</h3>
            <p>View and approve student participation requests.</p>
        </div>

        <div class="card">
            <h3>📢 Post Announcements</h3>
            <p>Publish important updates for students.</p>
        </div>

        <div class="card">
            <h3>📋 View Feedback</h3>
            <p>Check feedback submitted by students for events.</p>
        </div>

        <div class="card">
            <h3>🎓 View Student Engagement</h3>
            <p>Track student involvement in events and clubs.</p>
        </div>
    </div>
</div>

</body>
</html>
