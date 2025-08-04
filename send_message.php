<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || !isset($_POST['message'])) exit;

$user_id = $_SESSION['user_id'];
$message = mysqli_real_escape_string($conn, $_POST['message']);
$receiver_role = $_POST['receiver_role'];

$sql = "INSERT INTO messages (sender_id, receiver_role, message, sent_on) 
        VALUES ('$user_id', '$receiver_role', '$message', NOW())";
$conn->query($sql);
?>
