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

// Fetch available items with image_path
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
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f8f5f4 0%, #f1e6e4 25%, #eaddd9 75%, #e3d4d0 100%); min-height: 100vh; color: #3a1f1b; }
        body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 20% 50%, rgba(198, 65, 65, 0.08) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(181, 45, 45, 0.06) 0%, transparent 50%); pointer-events: none; z-index: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; position: relative; z-index: 1; }
        .header { text-align: center; margin-bottom: 40px; background: linear-gradient(135deg, #c64141 0%, #b52d2d 50%, #a42828 100%); border-radius: 20px; padding: 40px 30px; box-shadow: 0 8px 32px rgba(198, 65, 65, 0.25), inset 0 1px 0 rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15); overflow: hidden; position: relative; }
        .header::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.08), transparent); transform: rotate(45deg); animation: shimmer 4s ease-in-out infinite; }
        @keyframes shimmer { 0%, 100% { transform: rotate(45deg) translateX(-100%); } 50% { transform: rotate(45deg) translateX(100%); } }
        .header h1 { font-size: 3rem; color: #ffffff; margin-bottom: 15px; position: relative; z-index: 2; font-weight: 700; text-shadow: 0 2px 8px rgba(0,0,0,0.3); }
        .header p { color: #ffeaea; font-size: 1.2rem; font-weight: 400; position: relative; z-index: 2; opacity: 0.95; }
        .message { text-align: center; margin-bottom: 30px; padding: 18px 35px; border-radius: 12px; font-weight: 600; font-size: 1.1rem; background: linear-gradient(135deg, #c64141, #d65555); color: #ffffff; box-shadow: 0 4px 20px rgba(198, 65, 65, 0.3); border: 1px solid rgba(255,255,255,0.2); animation: slideInDown 0.6s ease-out; }
        @keyframes slideInDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .section { background: linear-gradient(135deg, #ffffff 0%, #fefcfc 50%, #fdfaf9 100%); border-radius: 16px; padding: 35px; margin-bottom: 40px; box-shadow: 0 8px 25px rgba(198, 65, 65, 0.12); border: 1px solid rgba(198, 65, 65, 0.15); transition: all 0.3s ease; }
        .section:hover { transform: translateY(-4px); box-shadow: 0 12px 35px rgba(198, 65, 65, 0.18); border-color: rgba(198, 65, 65, 0.25); }
        .section h2 { color: #c64141; margin-bottom: 30px; font-size: 2rem; display: flex; align-items: center; gap: 12px; font-weight: 600; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .product-card { background: linear-gradient(135deg, #ffffff 0%, #fefcfc 100%); border-radius: 16px; padding: 0; box-shadow: 0 6px 20px rgba(198, 65, 65, 0.12); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 1px solid rgba(198, 65, 65, 0.15); position: relative; overflow: hidden; }
        .product-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, #c64141, #d65555, #b52d2d); transform: scaleX(0); transform-origin: left; transition: transform 0.3s ease; }
        .product-card:hover::before { transform: scaleX(1); }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 16px 40px rgba(198, 65, 65, 0.2); border-color: rgba(198, 65, 65, 0.3); }
        
        .product-image-container { width: 100%; height: 250px; overflow: hidden; border-radius: 16px 16px 0 0; position: relative; background: linear-gradient(135deg, #f8f5f4, #eaddd9); }
        .product-image { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }
        .product-card:hover .product-image { transform: scale(1.05); }
        .image-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: rgba(198, 65, 65, 0.4); font-size: 3rem; background: linear-gradient(135deg, #fefcfc 0%, #f8f5f4 100%); }
        .image-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(198, 65, 65, 0.1) 0%, rgba(198, 65, 65, 0.05) 100%); opacity: 0; transition: opacity 0.3s ease; }
        .product-card:hover .image-overlay { opacity: 1; }
        
        .product-content { padding: 30px; }
        .product-info { text-align: center; margin-bottom: 25px; position: relative; z-index: 2; }
        .product-name { font-size: 1.4rem; font-weight: 600; color: #c64141; margin-bottom: 12px; }
        .product-price { font-size: 1.8rem; font-weight: 700; color: #a42828; margin-bottom: 8px; }
        .product-stock { color: #6b3834; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 6px; font-weight: 500; }
        .order-form { display: flex; gap: 12px; align-items: center; justify-content: center; margin-top: 20px; position: relative; z-index: 2; }
        .quantity-input { width: 70px; padding: 12px; border: 2px solid rgba(198, 65, 65, 0.3); background: #fefcfc; color: #c64141; border-radius: 8px; text-align: center; font-weight: 600; font-size: 1rem; transition: all 0.3s ease; }
        .quantity-input:focus { outline: none; border-color: #c64141; background: #ffffff; box-shadow: 0 0 0 3px rgba(198, 65, 65, 0.1); }
        .buy-btn { padding: 12px 24px; background: linear-gradient(135deg, #c64141 0%, #d65555 100%); color: #ffffff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.3s ease; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(198, 65, 65, 0.25); }
        .buy-btn:hover { background: linear-gradient(135deg, #b52d2d 0%, #c64141 100%); transform: translateY(-2px); box-shadow: 0 6px 18px rgba(198, 65, 65, 0.35); }
        .orders-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 6px 20px rgba(198, 65, 65, 0.12); border: 1px solid rgba(198, 65, 65, 0.15); }
        .orders-table th { background: linear-gradient(135deg, #c64141 0%, #b52d2d 100%); color: #ffffff; padding: 18px 15px; font-weight: 600; text-align: left; }
        .orders-table td { padding: 16px 15px; border-bottom: 1px solid rgba(198, 65, 65, 0.1); color: #3a1f1b; font-weight: 500; }
        .orders-table tr:hover { background: linear-gradient(90deg, rgba(198, 65, 65, 0.05) 0%, rgba(198, 65, 65, 0.08) 50%, rgba(198, 65, 65, 0.05) 100%); }
        .status { padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: capitalize; display: inline-flex; align-items: center; gap: 6px; }
        .status.pending { background: linear-gradient(135deg, #ffd89b, #ffb347); color: #8b4513; border: 1px solid #daa520; }
        .status.approved { background: linear-gradient(135deg, #90ee90, #32cd32); color: #006400; border: 1px solid #228b22; }
        .status.rejected { background: linear-gradient(135deg, #ffb3b3, #ff8080); color: #8b0000; border: 1px solid #dc143c; }
        .receipt-btn { background: linear-gradient(135deg, #c64141 0%, #d65555 100%); color: #ffffff; padding: 10px 18px; text-decoration: none; border-radius: 8px; font-size: 0.9rem; font-weight: 600; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 6px; border: none; }
        .receipt-btn:hover { background: linear-gradient(135deg, #b52d2d 0%, #c64141 100%); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(198, 65, 65, 0.3); }
        .no-items { text-align: center; color: #c64141; font-size: 1.2rem; font-weight: 500; padding: 50px 40px; background: linear-gradient(135deg, #fefcfc 0%, #fdf9f8 100%); border-radius: 16px; margin: 25px 0; border: 2px dashed rgba(198, 65, 65, 0.3); }
        .no-items i { font-size: 3.5rem; margin-bottom: 20px; opacity: 0.6; color: #c64141; }
        .no-items small { color: #6b3834; font-size: 0.9rem; font-weight: 400; }
        
        .image-zoom { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.9); display: none; z-index: 1000; align-items: center; justify-content: center; cursor: zoom-out; }
        .image-zoom img { max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 10px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5); }
        .image-zoom.active { display: flex; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        @media (max-width: 768px) { 
            .container { padding: 15px; } 
            .header h1 { font-size: 2.2rem; } 
            .header { padding: 30px 20px; } 
            .section { padding: 25px; } 
            .products-grid { grid-template-columns: 1fr; gap: 20px; } 
            .orders-table { font-size: 0.85rem; } 
            .orders-table th, .orders-table td { padding: 12px 8px; } 
            .product-card .product-content { padding: 25px 20px; }
            .product-image-container { height: 200px; }
        }
        .spinner { border: 3px solid rgba(198, 65, 65, 0.3); border-top: 3px solid #c64141; border-radius: 50%; width: 35px; height: 35px; animation: spin 1s linear infinite; margin: 0 auto 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> MDC Club Merchandise Shop</h1>
            <p>Discover and purchase exclusive club merchandise</p>
        </div>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <div class="section">
            <h2><i class="fas fa-shopping-bag"></i> Merchandise Collection</h2>
            <?php if ($items && $items->num_rows > 0): ?>
                <div class="products-grid">
                    <?php while ($row = $items->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-image-container">
                                <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($row['image_path']) ?>" 
                                         alt="<?= htmlspecialchars($row['name']) ?>" 
                                         class="product-image" 
                                         onclick="zoomImage('<?= htmlspecialchars($row['image_path']) ?>', '<?= htmlspecialchars($row['name']) ?>')">
                                    <div class="image-overlay"></div>
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-content">
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
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-shopping-bag"></i>
                    <div>No merchandise available at the moment</div>
                    <small>Check back soon for new arrivals!</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2><i class="fas fa-history"></i> Purchase History</h2>
            <?php if ($orders && $orders->num_rows > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> Order ID</th>
                                <th><i class="fas fa-tag"></i> Item</th>
                                <th><i class="fas fa-sort-numeric-up"></i> Quantity</th>
                                <th><i class="fas fa-rupee-sign"></i> Total Price</th>
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
                    <small>Your orders will appear here after purchase</small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Image Zoom Modal -->
    <div class="image-zoom" id="imageZoom" onclick="closeZoom()">
        <img id="zoomedImage" src="" alt="">
    </div>

    <script>
        // Image zoom functionality
        function zoomImage(imageSrc, altText) {
            const modal = document.getElementById('imageZoom');
            const zoomedImg = document.getElementById('zoomedImage');
            zoomedImg.src = imageSrc;
            zoomedImg.alt = altText;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeZoom() {
            const modal = document.getElementById('imageZoom');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close zoom on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeZoom();
            }
        });

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
                    this.style.borderColor = '#dc143c';
                    setTimeout(() => {
                        this.style.borderColor = 'rgba(198, 65, 65, 0.3)';
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

        document.querySelectorAll('.product-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        document.querySelectorAll('.buy-btn').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>