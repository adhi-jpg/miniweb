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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDC Merchandise Shop</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #8B0000 0%, #DC143C 25%, #B8860B 75%, #FFD700 100%); min-height: 100vh; color: #333; }
        body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 20% 50%, rgba(255, 215, 0, 0.1) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(139, 0, 0, 0.1) 0%, transparent 50%); pointer-events: none; z-index: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; position: relative; z-index: 1; }
        .header { text-align: center; margin-bottom: 40px; background: linear-gradient(135deg, rgba(139, 0, 0, 0.95) 0%, rgba(165, 42, 42, 0.95) 100%); backdrop-filter: blur(15px); border-radius: 20px; padding: 40px 30px; box-shadow: 0 8px 32px rgba(184, 134, 11, 0.3), 0 0 0 1px rgba(255, 215, 0, 0.2); border: 2px solid rgba(255, 215, 0, 0.3); overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255, 215, 0, 0.05), transparent); transform: rotate(45deg); animation: shimmer 3s ease-in-out infinite; }
        @keyframes shimmer { 0%, 100% { transform: rotate(45deg) translateX(-100%); } 50% { transform: rotate(45deg) translateX(100%); } }
        .header h1 { font-size: 3rem; background: linear-gradient(45deg, #FFD700 0%, #FFA500 25%, #FFD700 50%, #FFFF00 75%, #FFD700 100%); background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 15px; animation: goldShine 2s ease-in-out infinite alternate; position: relative; z-index: 2; font-weight: 800; }
        @keyframes goldShine { 0% { background-position: 0% 50%; } 100% { background-position: 100% 50%; } }
        .header p { color: #FFD700; font-size: 1.2rem; font-weight: 500; position: relative; z-index: 2; }
        .message { text-align: center; margin-bottom: 30px; padding: 18px 35px; border-radius: 50px; font-weight: 700; font-size: 1.1rem; background: linear-gradient(45deg, #B8860B, #FFD700, #DAA520); color: #8B0000; box-shadow: 0 4px 20px rgba(184, 134, 11, 0.4); border: 2px solid rgba(255, 215, 0, 0.5); animation: slideInDown 0.6s ease-out; }
        @keyframes slideInDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .section { background: linear-gradient(135deg, rgba(139, 0, 0, 0.95) 0%, rgba(165, 42, 42, 0.92) 30%, rgba(139, 0, 0, 0.95) 100%); backdrop-filter: blur(15px); border-radius: 20px; padding: 35px; margin-bottom: 40px; box-shadow: 0 10px 40px rgba(184, 134, 11, 0.2); border: 2px solid rgba(255, 215, 0, 0.3); transition: all 0.4s ease; overflow: hidden; }
        .section:hover { transform: translateY(-8px); box-shadow: 0 20px 50px rgba(255, 215, 0, 0.3); border-color: rgba(255, 215, 0, 0.5); }
        .section h2 { background: linear-gradient(45deg, #FFD700 0%, #FFA500 25%, #FFD700 50%, #FFFF00 75%, #FFD700 100%); background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 30px; font-size: 2rem; display: flex; align-items: center; gap: 12px; font-weight: 700; animation: goldShine 2s ease-in-out infinite alternate; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .product-card { background: linear-gradient(135deg, rgba(139, 0, 0, 0.9) 0%, rgba(160, 82, 45, 0.8) 50%, rgba(139, 0, 0, 0.9) 100%); border-radius: 18px; padding: 30px; box-shadow: 0 8px 25px rgba(184, 134, 11, 0.2); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 2px solid rgba(255, 215, 0, 0.4); position: relative; overflow: hidden; }
        .product-card::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255, 215, 0, 0.15), transparent); transform: rotate(45deg); transition: transform 0.8s ease; opacity: 0; }
        .product-card:hover::before { transform: rotate(45deg) translate(50%, 50%); opacity: 1; }
        .product-card:hover { transform: translateY(-12px) scale(1.03); box-shadow: 0 25px 50px rgba(255, 215, 0, 0.4); border-color: rgba(255, 215, 0, 0.7); }
        .product-info { text-align: center; margin-bottom: 25px; position: relative; z-index: 2; }
        .product-name { font-size: 1.4rem; font-weight: 700; color: #FFD700; margin-bottom: 12px; }
        .product-price { font-size: 1.8rem; font-weight: 800; background: linear-gradient(45deg, #FFD700 0%, #FFA500 25%, #FFD700 50%, #FFFF00 75%, #FFD700 100%); background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 8px; animation: goldShine 2s ease-in-out infinite alternate; }
        .product-stock { color: #F5DEB3; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 600; }
        .order-form { display: flex; gap: 12px; align-items: center; justify-content: center; margin-top: 20px; position: relative; z-index: 2; }
        .quantity-input { width: 70px; padding: 12px; border: 2px solid rgba(255, 215, 0, 0.6); background: linear-gradient(135deg, rgba(139, 0, 0, 0.4), rgba(160, 82, 45, 0.3)); color: #FFD700; border-radius: 10px; text-align: center; font-weight: 700; font-size: 1rem; transition: all 0.3s ease; }
        .quantity-input:focus { outline: none; border-color: #FFD700; background: linear-gradient(135deg, rgba(139, 0, 0, 0.6), rgba(160, 82, 45, 0.5)); box-shadow: 0 0 15px rgba(255, 215, 0, 0.4); }
        .buy-btn { padding: 12px 24px; background: linear-gradient(45deg, #B8860B 0%, #FFD700 25%, #DAA520 50%, #FFD700 75%, #B8860B 100%); background-size: 200% auto; color: #8B0000; border: 2px solid rgba(255, 215, 0, 0.6); border-radius: 10px; cursor: pointer; font-weight: 800; font-size: 0.95rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 6px; }
        .buy-btn:hover { background: linear-gradient(45deg, #FFD700 0%, #FFA500 25%, #FFD700 50%, #FFFF00 75%, #FFD700 100%); border-color: #FFD700; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(255, 215, 0, 0.5); animation: goldShine 0.8s ease-in-out infinite alternate; }
        .orders-table { width: 100%; border-collapse: collapse; background: linear-gradient(135deg, rgba(139, 0, 0, 0.95) 0%, rgba(165, 42, 42, 0.92) 50%, rgba(139, 0, 0, 0.95) 100%); border-radius: 18px; overflow: hidden; box-shadow: 0 8px 30px rgba(184, 134, 11, 0.2); border: 2px solid rgba(255, 215, 0, 0.4); }
        .orders-table th { background: linear-gradient(45deg, #B8860B 0%, #DAA520 25%, #FFD700 50%, #DAA520 75%, #B8860B 100%); background-size: 200% auto; color: #8B0000; padding: 18px 15px; font-weight: 800; text-align: left; animation: goldShine 3s ease-in-out infinite alternate; }
        .orders-table td { padding: 16px 15px; border-bottom: 1px solid rgba(255, 215, 0, 0.3); color: #FFD700; font-weight: 600; }
        .orders-table tr:hover { background: linear-gradient(90deg, rgba(255, 215, 0, 0.1) 0%, rgba(255, 215, 0, 0.15) 50%, rgba(255, 215, 0, 0.1) 100%); }
        .status { padding: 8px 16px; border-radius: 25px; font-size: 0.85rem; font-weight: 700; text-transform: capitalize; display: inline-flex; align-items: center; gap: 6px; }
        .status.pending { background: linear-gradient(45deg, #DAA520, #FFD700, #FFFF00); color: #8B0000; border: 1px solid rgba(255, 215, 0, 0.6); }
        .status.approved { background: linear-gradient(45deg, #228B22, #32CD32, #90EE90); color: white; border: 1px solid rgba(50, 205, 50, 0.6); }
        .status.rejected { background: linear-gradient(45deg, #DC143C, #FF6347, #FFA07A); color: white; border: 1px solid rgba(220, 20, 60, 0.6); }
        .receipt-btn { background: linear-gradient(45deg, #B8860B 0%, #FFD700 25%, #DAA520 50%, #FFD700 75%, #B8860B 100%); background-size: 200% auto; color: #8B0000; padding: 10px 18px; text-decoration: none; border-radius: 10px; font-size: 0.9rem; font-weight: 800; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(255, 215, 0, 0.6); }
        .receipt-btn:hover { background: linear-gradient(45deg, #FFD700 0%, #FFA500 25%, #FFD700 50%, #FFFF00 75%, #FFD700 100%); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(255, 215, 0, 0.5); animation: goldShine 0.8s ease-in-out infinite alternate; }
        .no-items { text-align: center; color: #FFD700; font-size: 1.2rem; font-weight: 600; padding: 50px 40px; background: linear-gradient(135deg, rgba(139, 0, 0, 0.8) 0%, rgba(184, 134, 11, 0.4) 50%, rgba(139, 0, 0, 0.8) 100%); border-radius: 18px; margin: 25px 0; border: 2px solid rgba(255, 215, 0, 0.4); }
        .no-items i { font-size: 3.5rem; margin-bottom: 20px; opacity: 0.8; color: #FFD700; }
        .no-items small { color: #F5DEB3; font-size: 0.9rem; font-weight: 500; }
        @media (max-width: 768px) { .container { padding: 15px; } .header h1 { font-size: 2.2rem; } .header { padding: 30px 20px; } .section { padding: 25px; } .products-grid { grid-template-columns: 1fr; gap: 20px; } .orders-table { font-size: 0.85rem; } .orders-table th, .orders-table td { padding: 12px 8px; } .product-card { padding: 25px 20px; } }
        .spinner { border: 3px solid rgba(255, 215, 0, 0.3); border-top: 3px solid #FFD700; border-radius: 50%; width: 35px; height: 35px; animation: spin 1s linear infinite; margin: 0 auto 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-crown"></i> MDC Club Merchandise Shop</h1>
            <p>Discover and purchase exclusive premium club merchandise</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <div class="section">
            <h2><i class="fas fa-gem"></i> Premium Merchandise Collection</h2>
            <?php if ($items && $items->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $items->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($row['name']) ?></div>
                                <div class="product-price">₹<?= number_format($row['price'], 2) ?></div>
                                <div class="product-stock">
                                    <i class="fas fa-box"></i> <?= $row['stock'] ?> items in stock
                                </div>
                            </div>
                            <form method="POST" action="dummy_payment.php" class="order-form">
                                <input type="hidden" name="item_id" value="<?= $row['item_id'] ?>">
                                <input type="number" name="quantity" min="1" max="<?= $row['stock'] ?>" value="1" class="quantity-input" required>
                                <button type="submit" class="buy-btn">
                                    <i class="fas fa-shopping-cart"></i> Buy Now
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-shopping-bag"></i>
                    <div>No merchandise available at the moment</div>
                    <small>Check back soon for exclusive new arrivals!</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2><i class="fas fa-scroll"></i> Purchase History</h2>
            <?php if ($orders && $orders->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Order ID</th>
                                <th><i class="fas fa-tag"></i> Item</th>
                                <th><i class="fas fa-sort-numeric-up"></i> Quantity</th>
                                <th><i class="fas fa-coins"></i> Total Price</th>
                                <th><i class="fas fa-info-circle"></i> Status</th>
                                <th><i class="fas fa-calendar-alt"></i> Date</th>
                                <th><i class="fas fa-receipt"></i> Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= $order['order_id'] ?></td>
                                    <td><?= htmlspecialchars($order['item_name']) ?></td>
                                    <td><?= $order['quantity'] ?></td>
                                    <td>₹<?= number_format($order['total_price'], 2) ?></td>
                                    <td>
                                        <span class="status <?= $order['status'] ?>">
                                            <?php if ($order['status'] == 'pending'): ?>
                                                <i class="fas fa-hourglass-half"></i>
                                            <?php elseif ($order['status'] == 'approved'): ?>
                                                <i class="fas fa-check-circle"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle"></i>
                                            <?php endif; ?>
                                            <?= $order['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($order['ordered_at'])) ?></td>
                                    <td>
                                        <a class="receipt-btn" href="receipt.php?order_id=<?= $order['order_id'] ?>" target="_blank">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-receipt"></i>
                    <div>No purchase history found</div>
                    <small>Your premium orders will appear here after purchase</small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const button = this.querySelector('button[type="submit"]');
                if (button) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    button.disabled = true;
                }
            });
        });

        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('input', function() {
                const max = parseInt(this.getAttribute('max'));
                const value = parseInt(this.value);
                if (value > max) {
                    this.value = max;
                    this.style.borderColor = '#DC143C';
                    setTimeout(() => {
                        this.style.borderColor = 'rgba(255, 215, 0, 0.6)';
                    }, 2000);
                }
            });
        });

        setTimeout(() => {
            const message = document.querySelector('.message');
            if (message) {
                message.style.transition = 'all 0.8s ease-out';
                message.style.opacity = '0';
                message.style.transform = 'translateY(-30px)';
                setTimeout(() => message.style.display = 'none', 800);
            }
        }, 6000);

        document.querySelectorAll('.buy-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                for (let i = 0; i < 3; i++) {
                    setTimeout(() => {
                        const sparkle = document.createElement('div');
                        sparkle.innerHTML = '✨';
                        sparkle.style.cssText = `position: absolute; font-size: 12px; color: #FFD700; pointer-events: none; left: ${e.pageX + (Math.random() - 0.5) * 30}px; top: ${e.pageY + (Math.random() - 0.5) * 30}px; z-index: 9999; animation: sparkle 1.5s ease-out forwards;`;
                        document.body.appendChild(sparkle);
                        setTimeout(() => sparkle.remove(), 1500);
                    }, i * 100);
                }
            });
        });

        const style = document.createElement('style');
        style.textContent = '@keyframes sparkle { 0% { opacity: 1; transform: translateY(0) scale(0); } 50% { opacity: 1; transform: translateY(-20px) scale(1); } 100% { opacity: 0; transform: translateY(-40px) scale(0); } }';
        document.head.appendChild(style);
    </script>
</body>
</html>