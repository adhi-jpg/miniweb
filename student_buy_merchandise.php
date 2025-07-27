<?php
session_start();
include "config.php";

// Ensure student login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle product order
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['item_id'], $_POST['quantity'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);

    $item_query = $conn->query("SELECT * FROM merchandise WHERE item_id = $item_id");
    if ($item_query && $item_query->num_rows > 0) {
        $item = $item_query->fetch_assoc();
        if ($item['stock'] >= $quantity) {
            $total_price = $item['price'] * $quantity;

            $conn->query("INSERT INTO orders (user_id, item_id, quantity, total_price, status)
                          VALUES ($user_id, $item_id, $quantity, $total_price, 'pending')");
            $conn->query("UPDATE merchandise SET stock = stock - $quantity WHERE item_id = $item_id");
            $message = "✅ Order placed successfully!";
        } else {
            $message = "❌ Not enough stock.";
        }
    } else {
        $message = "❌ Item not found.";
    }
}

// Fetch available items
$items = $conn->query("SELECT * FROM merchandise WHERE stock > 0");

// Fetch purchase history
$orders = $conn->query("
    SELECT o.*, m.name AS item_name 
    FROM orders o
    JOIN merchandise m ON o.item_id = m.item_id
    WHERE o.user_id = $user_id
    ORDER BY o.ordered_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
  <title>MDC Merchandise Shop</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f0f0f0; padding: 30px; }
    h1,h2 { text-align: center; color: #333; }
    .message { text-align: center; margin-bottom: 20px; font-weight: bold; color: green; }
    table { width: 100%; border-collapse: collapse; background: #fff; margin: 30px auto; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
    th, td { padding: 12px; border: 1px solid #ddd; text-align: center; }
    th { background-color: #4a00e0; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    input[type="number"] { width: 60px; padding: 5px; }
    button { padding: 8px 14px; background-color: #4a00e0; color: white; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background-color: #3700b3; }
    .status { text-transform: capitalize; font-weight: bold; padding: 4px 8px; border-radius: 5px; }
    .status.pending { background: #fff3cd; color: #856404; }
    .status.approved { background: #d4edda; color: #155724; }
    .status.rejected { background: #f8d7da; color: #721c24; }
    .receipt-btn { background: #007bff; }
    .receipt-btn:hover { background: #0056b3; }
  </style>
</head>
<body>

<h1>MDC Club Merchandise Shop</h1>
<?php if ($message): ?><p class="message"><?= $message ?></p><?php endif; ?>

<h2>Available Merchandise</h2>
<?php if ($items && $items->num_rows > 0): ?>
<table>
  <tr>
    <th>Item</th><th>Price (₹)</th><th>Stock</th><th>Quantity</th><th>Order</th>
  </tr>
  <?php while ($row = $items->fetch_assoc()): ?>
    <tr>
      <form method="POST" action="dummy_payment.php">
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= number_format($row['price'], 2) ?></td>
    <td><?= $row['stock'] ?></td>
    <td>
        <input type="hidden" name="item_id" value="<?= $row['item_id'] ?>">
        <input type="number" name="quantity" min="1" max="<?= $row['stock'] ?>" required>
    </td>
    <td>
        <button type="submit">Buy</button>
    </td>
</form>

    </tr>
  <?php endwhile; ?>
</table>
<?php else: ?>
  <p style="text-align:center;">No merchandise available.</p>
<?php endif; ?>

<h2>Purchase History</h2>
<?php if ($orders && $orders->num_rows > 0): ?>
<table>
  <tr>
    <th>Order ID</th><th>Item</th><th>Quantity</th><th>Total Price (₹)</th><th>Status</th><th>Date</th><th>Receipt</th>
  </tr>
  <?php while ($order = $orders->fetch_assoc()): ?>
    <tr>
      <td><?= $order['order_id'] ?></td>
      <td><?= htmlspecialchars($order['item_name']) ?></td>
      <td><?= $order['quantity'] ?></td>
      <td><?= number_format($order['total_price'], 2) ?></td>
      <td><span class="status <?= $order['status'] ?>"><?= $order['status'] ?></span></td>
      <td><?= $order['ordered_at'] ?></td>
      <td><a class="receipt-btn" href="receipt.php?order_id=<?= $order['order_id'] ?>" target="_blank">Download</a></td>
    </tr>
  <?php endwhile; ?>
</table>
<?php else: ?>
  <p style="text-align:center;">No past purchases.</p>
<?php endif; ?>
</body>
</html>
