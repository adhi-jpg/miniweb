<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];
$receiver_role = $_GET['role'];

$query = "SELECT * FROM messages WHERE 
         (sender_id = '$user_id' AND receiver_role = '$receiver_role') 
         OR (receiver_role = '{$_SESSION['role']}' AND sender_id != '$user_id')
         ORDER BY sent_on ASC";
$result = $conn->query($query);

while($row = $result->fetch_assoc()){
    $class = ($row['sender_id'] == $user_id) ? 'sent' : 'received';
    echo "<div class='message $class'>".htmlspecialchars($row['message'])."<br><small>".$row['sent_on']."</small></div>";
}
?>
