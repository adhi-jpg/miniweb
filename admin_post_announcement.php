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
    <title>Post Announcement – Admin</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f5f5f5;
        }

        header {
            background: #141e30;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
        }

        .logout a {
            color: #fdd835;
            text-decoration: none;
            font-weight: bold;
        }

        .container {
            padding: 40px;
        }

        h2 {
            color: #141e30;
        }

        textarea {
            width: 100%;
            height: 100px;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
            resize: vertical;
        }

        button {
            padding: 12px 25px;
            background: #fdd835;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
            cursor: pointer;
        }

        button:hover {
            background: #ffe94d;
        }

        .msg {
            margin-top: 15px;
            font-weight: bold;
            color: green;
        }

        .announcement {
            background: white;
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .announcement p {
            margin: 5px 0;
        }

        .announcement small {
            color: gray;
        }

        .delete-link {
            float: right;
            color: red;
            font-size: 14px;
            text-decoration: none;
        }

        .delete-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<header>
    <h1>Admin – Post Announcement</h1>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="container">
    <h2>📢 Create Announcement</h2>
    <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
    <form method="POST">
        <textarea name="message" placeholder="Enter your announcement..." required></textarea><br>
        <button type="submit">Post Announcement</button>
    </form>

    <h2 style="margin-top: 40px;">📄 Previous Announcements</h2>
    <?php
    $result = $conn->query("SELECT * FROM announcements ORDER BY posted_on DESC");
    while ($row = $result->fetch_assoc()):
    ?>
        <div class="announcement">
            <a href="?delete=<?= $row['id'] ?>" class="delete-link" onclick="return confirm('Delete this announcement?')">Delete</a>
            <p><?= htmlspecialchars($row["message"]) ?></p>
            <small>Posted on <?= date("d M Y – h:i A", strtotime($row["posted_on"])) ?></small>
        </div>
    <?php endwhile; ?>
</div>

</body>
</html>
