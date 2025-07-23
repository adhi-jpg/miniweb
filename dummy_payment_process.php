<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['item_id'], $_POST['quantity'], $_POST['total_price'], $_POST['payment_method'])) {
    die("Invalid payment attempt!");
}

$item_id = intval($_POST['item_id']);
$quantity = intval($_POST['quantity']);
$total_price = floatval($_POST['total_price']);
$user_id = $_SESSION['user_id'];
$payment_method = $_POST['payment_method'];

// Dummy validations
if ($payment_method == "card") {
    $card_number = trim($_POST['card_number']);
    // Accept only one dummy card number
    if ($card_number !== "4111111111111111") {
        header("Location: payment_failed.php?amount=$total_price");
        exit();
    }
}
elseif ($payment_method == "upi") {
    $upi_id = trim($_POST['upi_id']);
    // Accept any non-empty UPI ID for demo
    if (empty($upi_id)) {
        header("Location: payment_failed.php?amount=$total_price");
        exit();
    }
}

// Insert order (status: pending)
$stmt = $conn->prepare("INSERT INTO orders (user_id, item_id, quantity, total_price, status, payment_method) 
                        VALUES (?, ?, ?, ?, 'pending', ?)");
$stmt->bind_param("iiids", $user_id, $item_id, $quantity, $total_price, $payment_method);
$stmt->execute();
$stmt->close();

// Reduce stock
$conn->query("UPDATE merchandise SET stock = stock - $quantity WHERE item_id = $item_id");

header("Location: payment_success.php?amount=$total_price");
exit();
?>
