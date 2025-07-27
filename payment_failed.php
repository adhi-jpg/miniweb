<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$amount = isset($_GET['amount']) ? number_format($_GET['amount'], 2) : "0.00";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Failed</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .fail-box { background: #fff; padding: 30px; border-radius: 10px; width: 450px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .fail-box h2 { color: #dc3545; margin-bottom: 20px; }
        .fail-box p { font-size: 16px; margin: 8px 0; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #4a00e0; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #3700b3; }
    </style>
</head>
<body>
    <div class="fail-box">
        <h2>❌ Payment Failed</h2>
        <p><b>Amount:</b> ₹<?= $amount ?></p>
        <p>Your payment could not be processed.</p>
        <a href="student_buy_merchandise.php" class="btn">Try Again</a>
    </div>
</body>
</html>
