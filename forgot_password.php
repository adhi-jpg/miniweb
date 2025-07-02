<?php
session_start();
include "config.php";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = mysqli_real_escape_string($conn, $_POST["identifier"]);

    $check = $conn->query("SELECT * FROM users WHERE email='$identifier' OR user_id='$identifier' LIMIT 1");
    if ($check->num_rows > 0) {
        // Optional: Log the request or notify admin
        $msg = "✅ Password reset request submitted. Please contact the admin.";
    } else {
        $msg = "❌ User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password – MDC Club</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #243b55, #141e30);
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: rgba(255, 255, 255, 0.08);
            padding: 40px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            width: 90%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #fdd835;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0 20px;
            border: none;
            border-radius: 8px;
            background: #ffffffdd;
            font-size: 16px;
            color: #141e30;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #fdd835;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            color: #141e30;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #ffe94d;
        }

        .msg {
            text-align: center;
            font-weight: bold;
            color: lightgreen;
        }

        .msg.error {
            color: #ff6b6b;
        }

        a {
            color: #fdd835;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Forgot Password?</h2>
    <?php if (!empty($msg)): ?>
        <div class="msg <?= str_contains($msg, '❌') ? 'error' : '' ?>"><?= $msg ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="identifier" placeholder="Enter Email or Roll Number" required>
        <button type="submit">Request Reset</button>
    </form>
    <a href="login.php">🔙 Back to Login</a>
</div>

</body>
</html>
