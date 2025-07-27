<?php
include "config.php";
session_start();

if (!isset($_GET['order_id'])) {
    die("Order ID missing");
}
$order_id = intval($_GET['order_id']);

$query = $conn->query("
    SELECT o.*, m.name AS item_name, sp.name AS student_name, sp.roll_number
    FROM orders o
    JOIN merchandise m ON o.item_id = m.item_id
    JOIN student_profiles sp ON o.user_id = sp.user_id
    WHERE o.order_id = $order_id
");
if (!$query || $query->num_rows == 0) {
    die("Order not found");
}
$order = $query->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?= $order['order_id'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f4f4f4; }
        .receipt { max-width: 600px; margin: auto; background: #fff; padding: 30px; border-radius: 10px; }
        h2 { text-align: center; color: #333; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        td, th { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #4a00e0; color: white; }
        .footer { text-align: center; margin-top: 30px; font-size: 14px; color: #555; }
        .btn { display: inline-block; margin: 20px auto; padding: 10px 20px; background: #4a00e0; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #3700b3; }
    </style>
</head>
<body>
    <div class="receipt">
        <h2>MDC Club Purchase Receipt</h2>
        <table>
            <tr><th>Order ID</th><td><?= $order['order_id'] ?></td></tr>
            <tr><th>Student</th><td><?= $order['student_name'] ?> (<?= $order['roll_number'] ?>)</td></tr>
            <tr><th>Item</th><td><?= $order['item_name'] ?></td></tr>
            <tr><th>Quantity</th><td><?= $order['quantity'] ?></td></tr>
            <tr><th>Total Price</th><td>₹<?= number_format($order['total_price'], 2) ?></td></tr>
            <tr><th>Status</th><td><?= ucfirst($order['status']) ?></td></tr>
            <tr><th>Date</th><td><?= $order['ordered_at'] ?></td></tr>
        </table>
        <div class="footer">
            <p>Thank you for your purchase!</p>
            <a href="#" class="btn" onclick="window.print()">Download as PDF</a>
        </div>
    </div>
</body>
</html>
