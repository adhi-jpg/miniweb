<?php
session_start();
include "config.php";

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

// Check if this is a POST request with payment data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['payment_status']) || $_POST['payment_status'] !== 'success') {
    header("Location: student_buy_merchandise.php");
    exit();
}

// Get payment details
$user_id = $_SESSION['user_id'];
$item_id = intval($_POST['item_id']);
$quantity = intval($_POST['quantity']);
$total_price = floatval($_POST['total_price']);
$payment_method = $_POST['payment_method'];
$transaction_id = $_POST['transaction_id'] ?? '';
$timestamp = $_POST['timestamp'] ?? date('Y-m-d H:i:s');

// Verify the item exists and has sufficient stock
$item_query = $conn->prepare("SELECT * FROM merchandise WHERE item_id = ?");
$item_query->bind_param("i", $item_id);
$item_query->execute();
$item = $item_query->get_result()->fetch_assoc();

if (!$item || $item['stock'] < $quantity) {
    die("Error: Item not found or insufficient stock!");
}

// Verify the total price matches
$calculated_total = $item['price'] * $quantity;
if (abs($calculated_total - $total_price) > 0.01) {
    die("Error: Price mismatch!");
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Insert the order into orders table
    $insert_order = $conn->prepare("INSERT INTO orders (user_id, item_id, quantity, total_price, payment_method, status, ordered_at) VALUES (?, ?, ?, ?, ?, 'completed', NOW())");
    $insert_order->bind_param("iiids", $user_id, $item_id, $quantity, $total_price, $payment_method);
    
    if (!$insert_order->execute()) {
        throw new Exception("Failed to insert order: " . $insert_order->error);
    }
    
    $order_id = $conn->insert_id;
    
    // Update merchandise stock
    $update_stock = $conn->prepare("UPDATE merchandise SET stock = stock - ? WHERE item_id = ?");
    $update_stock->bind_param("ii", $quantity, $item_id);
    
    if (!$update_stock->execute()) {
        throw new Exception("Failed to update stock: " . $update_stock->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    $success_message = "Order placed successfully!";
    $order_success = true;
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $error_message = "Database error: " . $e->getMessage();
    $order_success = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .success-container {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .success-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px 30px;
        }
        
        .error-header {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            color: white;
            padding: 40px 30px;
        }
        
        .success-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: checkmark 0.8s ease-in-out;
        }
        
        .error-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: shake 0.8s ease-in-out;
        }
        
        .success-header h1, .error-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .success-body {
            padding: 40px 30px;
        }
        
        .order-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border-left: 5px solid #28a745;
        }
        
        .error-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            border-left: 5px solid #dc3545;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px 0;
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: #333;
        }
        
        .detail-value {
            color: #666;
        }
        
        .total-row {
            border-top: 2px solid #ddd;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4a00e0, #8e2de2);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .transaction-info {
            background: #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-top: 25px;
            font-size: 14px;
            color: #666;
        }
        
        .countdown {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        @keyframes checkmark {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .success-container {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <?php if ($order_success): ?>
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1>Payment Successful!</h1>
                <p>Your order has been placed successfully</p>
            </div>
            
            <div class="success-body">
                <div class="order-details">
                    <h3><i class="fas fa-receipt"></i> Order Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">#<?= $order_id ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Item:</span>
                        <span class="detail-value"><?= htmlspecialchars($item['name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Quantity:</span>
                        <span class="detail-value"><?= $quantity ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?= ucfirst($payment_method) ?></span>
                    </div>
                    <div class="detail-row total-row">
                        <span class="detail-label">Total Paid:</span>
                        <span class="detail-value">₹<?= number_format($total_price, 2) ?></span>
                    </div>
                </div>
                
                <?php if (!empty($transaction_id)): ?>
                <div class="transaction-info">
                    <h4><i class="fas fa-info-circle"></i> Transaction Information</h4>
                    <p><strong>Transaction ID:</strong> <?= htmlspecialchars($transaction_id) ?></p>
                    <p><strong>Date & Time:</strong> <?= date('d M Y, h:i A', strtotime($timestamp)) ?></p>
                    <p><small>Please save this information for your records</small></p>
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="student_buy_merchandise.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Continue Shopping
                    </a>
                    <a href="student_dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
                
                <div class="countdown">
                    <p><i class="fas fa-clock"></i> You will be redirected to the merchandise page in <span id="countdown">10</span> seconds</p>
                </div>
            </div>
            
        <?php else: ?>
            <div class="error-header">
                <div class="error-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h1>Order Processing Failed</h1>
                <p>There was an error processing your order</p>
            </div>
            
            <div class="success-body">
                <div class="error-details">
                    <h3><i class="fas fa-exclamation-circle"></i> Error Details</h3>
                    <p><?= isset($error_message) ? htmlspecialchars($error_message) : 'An unexpected error occurred' ?></p>
                    <p><strong>Don't worry:</strong> Your payment was successful, but there was an issue saving your order. Please contact support with your transaction details.</p>
                </div>
                
                <?php if (!empty($transaction_id)): ?>
                <div class="transaction-info">
                    <h4><i class="fas fa-info-circle"></i> Transaction Information</h4>
                    <p><strong>Transaction ID:</strong> <?= htmlspecialchars($transaction_id) ?></p>
                    <p><strong>Amount:</strong> ₹<?= number_format($total_price, 2) ?></p>
                    <p><small>Please provide this information when contacting support</small></p>
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="contact_support.php" class="btn btn-primary">
                        <i class="fas fa-headset"></i> Contact Support
                    </a>
                    <a href="dashboard_student.php" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        <?php if ($order_success): ?>
        // Countdown and auto-redirect
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'student_buy_merchandise.php';
            }
        }, 1000);
        
        // Stop countdown if user clicks anywhere
        document.addEventListener('click', () => {
            clearInterval(timer);
            countdownElement.parentElement.innerHTML = '<i class="fas fa-hand-pointer"></i> Auto-redirect stopped';
        });
        <?php endif; ?>
        
        // Prevent back button to payment page
        window.history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function(event) {
            window.history.pushState(null, null, window.location.href);
        });
    </script>
</body>
</html>