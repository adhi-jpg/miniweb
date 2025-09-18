<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle AJAX requests for order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_request'])) {
    header('Content-Type: application/json');
    
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    
    // Validate input
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
        exit();
    }
    
    $allowed_statuses = ['completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit();
    }
    
    try {
        // Check if order exists and is pending
        $check_query = "SELECT order_id, status FROM orders WHERE order_id = ?";
        $check_stmt = $conn->prepare($check_query);
        
        if (!$check_stmt) {
            throw new Exception("Failed to prepare check statement: " . $conn->error);
        }
        
        $check_stmt->bind_param("i", $order_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit();
        }
        
        $order = $result->fetch_assoc();
        $current_status = trim($order['status']);
        
        // Check if order is already processed
        if (!empty($current_status) && $current_status !== 'pending') {
            echo json_encode(['success' => false, 'message' => 'Order has already been processed']);
            exit();
        }
        
        $check_stmt->close();
        
        // Begin transaction for stock management
        $conn->begin_transaction();
        
        // Get order details for stock update
        $order_query = "SELECT item_id, quantity FROM orders WHERE order_id = ?";
        $order_stmt = $conn->prepare($order_query);
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_details = $order_stmt->get_result()->fetch_assoc();
        $order_stmt->close();
        
        // Update the order status
        $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?";
        $update_stmt = $conn->prepare($update_query);
        
        if (!$update_stmt) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }
        
        $update_stmt->bind_param("si", $status, $order_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update order: " . $update_stmt->error);
        }
        
        // If approving order (completed), reduce stock
        if ($status === 'completed' && $order_details) {
            $stock_query = "UPDATE merchandise SET stock = stock - ? WHERE item_id = ? AND stock >= ?";
            $stock_stmt = $conn->prepare($stock_query);
            $stock_stmt->bind_param("iii", $order_details['quantity'], $order_details['item_id'], $order_details['quantity']);
            
            if (!$stock_stmt->execute()) {
                throw new Exception("Failed to update stock: " . $stock_stmt->error);
            }
            
            // Check if stock was actually updated (sufficient stock was available)
            if ($stock_stmt->affected_rows === 0) {
                throw new Exception("Insufficient stock available for this order");
            }
            
            $stock_stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        if ($update_stmt->affected_rows > 0) {
            $action = $status === 'completed' ? 'approved' : 'rejected';
            $stock_msg = $status === 'completed' ? ' and stock has been updated' : '';
            echo json_encode([
                'success' => true, 
                'message' => "Order #{$order_id} has been successfully {$action}{$stock_msg}!"
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made']);
        }
        
        $update_stmt->close();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log("Error updating order status: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit();
}

// Fetch orders for display
$query = "
    SELECT o.order_id, o.item_id, o.quantity, o.total_price, o.status, o.ordered_at, o.payment_method,
           m.name AS item_name, sp.name AS student_name, sp.roll_number
    FROM orders o
    JOIN merchandise m ON o.item_id = m.item_id
    JOIN student_profiles sp ON o.user_id = sp.user_id
    ORDER BY 
        CASE 
            WHEN o.status = '' OR o.status IS NULL OR o.status = 'pending' THEN 1 
            WHEN o.status = 'completed' THEN 2 
            WHEN o.status = 'cancelled' THEN 3 
        END,
        o.order_id DESC
";
$result = $conn->query($query);

// Calculate statistics
$stats = ['total' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0];
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $stats['total']++;
        $status = trim($row['status']);
        if (empty($status) || $status === 'pending') {
            $stats['pending']++;
        } else {
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }
    }
    $result->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Merchandise Orders – MDC Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #4a90e2 100%);
            min-height: 100vh; 
            padding: 25px; 
            color: #333;
            animation: backgroundShift 10s ease-in-out infinite alternate;
        }

        @keyframes backgroundShift {
            0% { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #4a90e2 100%); }
            100% { background: linear-gradient(135deg, #2a5298 0%, #4a90e2 50%, #667eea 100%); }
        }

        .main-container {
            max-width: 1500px; 
            margin: 0 auto; 
            background: rgba(255, 255, 255, 0.95); 
            border-radius: 25px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.2); 
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #667eea 100%); 
            color: white; 
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header-section h1 { 
            font-size: 3rem; 
            font-weight: 800; 
            margin-bottom: 12px; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .header-section .subtitle { 
            font-size: 1.2rem; 
            opacity: 0.95; 
            font-weight: 300;
        }

        .message-alert {
            margin: 25px; 
            padding: 18px 25px; 
            border-radius: 15px; 
            font-weight: 600; 
            display: none; 
            align-items: center; 
            gap: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: 1px solid transparent;
        }
        
        .message-alert.success {
            background: linear-gradient(135deg, #d1f2eb, #a3e6d7); 
            color: #0e7b5a;
            border-color: #00c896;
        }
        
        .message-alert.error {
            background: linear-gradient(135deg, #fdeaea, #fbb8bb); 
            color: #a02834;
            border-color: #e74c3c;
        }
        
        .message-alert .close-btn {
            margin-left: auto; 
            background: none; 
            border: none; 
            font-size: 1.3rem; 
            cursor: pointer; 
            color: inherit; 
            opacity: 0.8;
            transition: all 0.3s;
            width: 30px;
            height: 30px;
            border-radius: 50%;
        }

        .dashboard-stats {
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
            gap: 30px; 
            padding: 40px; 
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%);
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
        }

        .stat-widget {
            background: white; 
            padding: 25px 20px; 
            border-radius: 20px; 
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(102, 126, 234, 0.1);
        }

        .stat-widget:hover { 
            transform: translateY(-8px) scale(1.02); 
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .stat-icon { 
            font-size: 2.5rem; 
            margin-bottom: 15px; 
        }

        .stat-number { 
            font-size: 3rem; 
            font-weight: 900; 
            margin-bottom: 8px; 
            line-height: 1;
        }
        
        .stat-label { 
            color: #6c757d; 
            font-size: 0.95rem; 
            text-transform: uppercase; 
            letter-spacing: 1.5px; 
            font-weight: 600;
        }

        .total-orders { color: #667eea; }
        .pending-orders { color: #f39c12; }
        .completed-orders { color: #00c896; }
        .cancelled-orders { color: #e74c3c; }

        .controls-panel {
            padding: 30px; 
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%); 
            border-bottom: 1px solid #e9ecef;
            display: flex; 
            gap: 20px; 
            flex-wrap: wrap; 
            align-items: center;
        }

        .search-container { 
            flex: 1; 
            max-width: 400px; 
            position: relative; 
        }

        .search-input {
            width: 100%; 
            padding: 15px 50px 15px 20px; 
            border: 2px solid #e9ecef; 
            border-radius: 30px;
            font-size: 15px; 
            background: white;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .search-input:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 5px 25px rgba(102, 126, 234, 0.2);
        }

        .search-icon { 
            position: absolute; 
            right: 18px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: #6c757d; 
            font-size: 1.1rem;
        }

        .filter-select {
            padding: 15px 20px; 
            border: 2px solid #e9ecef; 
            border-radius: 30px; 
            background: white; 
            cursor: pointer; 
            min-width: 150px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .data-table-wrapper { 
            overflow-x: auto; 
            background: white; 
            border-radius: 0 0 25px 25px;
        }

        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 1000px; 
        }

        .data-table th, .data-table td { 
            padding: 18px 15px; 
            text-align: left; 
            border-bottom: 1px solid #f1f3f4; 
        }

        .data-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            font-weight: 700;
            text-transform: uppercase; 
            letter-spacing: 1px; 
            font-size: 0.85rem;
        }

        .data-table tbody tr:hover { 
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%); 
        }

        .order-id-cell { 
            font-weight: 700; 
            color: #667eea; 
            font-size: 1.05rem;
        }

        .student-name { 
            font-weight: 600; 
            color: #2c3e50; 
        }

        .roll-number { 
            font-size: 0.9rem; 
            color: #7f8c8d; 
            font-weight: 500;
        }

        .item-name-cell { 
            font-weight: 500; 
            max-width: 180px; 
            overflow: hidden; 
            text-overflow: ellipsis; 
            color: #495057;
        }

        .quantity-cell { 
            font-weight: 700; 
            color: #667eea; 
            text-align: center; 
            font-size: 1.1rem;
        }

        .price-cell { 
            font-weight: 700; 
            color: #00c896; 
            font-size: 1.05rem;
        }

        .date-cell { 
            color: #7f8c8d; 
            font-size: 0.9rem; 
            white-space: nowrap; 
        }

        .payment-method-cell { 
            font-size: 0.85rem; 
            color: #495057; 
            text-transform: capitalize;
            padding: 5px 12px; 
            background: linear-gradient(135deg, #f8f9fa, #e9ecef); 
            border-radius: 12px; 
            display: inline-block;
            font-weight: 500;
        }

        .status-badge {
            font-weight: 700; 
            text-transform: capitalize; 
            padding: 8px 16px; 
            border-radius: 25px; 
            font-size: 0.85rem; 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            min-width: 90px; 
            justify-content: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }

        .status-badge.pending { 
            background: linear-gradient(135deg, #fff3cd, #ffeaa7); 
            color: #856404; 
        }

        .status-badge.completed { 
            background: linear-gradient(135deg, #d1f2eb, #a3e6d7); 
            color: #0e7b5a;
        }

        .status-badge.cancelled { 
            background: linear-gradient(135deg, #fdeaea, #fbb8bb); 
            color: #a02834;
        }

        .actions-cell {
            white-space: nowrap;
            width: 180px;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin: 0 3px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .approve-btn {
            background: linear-gradient(135deg, #00c896, #00b383);
            color: white;
        }

        .approve-btn:hover {
            background: linear-gradient(135deg, #00b383, #009770);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 200, 150, 0.3);
        }

        .reject-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
        }

        .reject-btn:hover {
            background: linear-gradient(135deg, #c0392b, #a93226);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.3);
        }

        .action-btn:disabled {
            background: linear-gradient(135deg, #bdc3c7, #95a5a6);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .updated-row { 
            background: linear-gradient(135deg, #d4edda, #a3e6d7) !important; 
            animation: highlightFade 4s ease-out; 
        }

        @keyframes highlightFade {
            0% { 
                background: linear-gradient(135deg, #d4edda, #a3e6d7) !important; 
                transform: scale(1.02);
            }
            75% { 
                background: linear-gradient(135deg, #d4edda, #a3e6d7) !important; 
            }
            100% { 
                background: white !important; 
                transform: scale(1);
            }
        }

        .empty-state { 
            text-align: center; 
            padding: 100px 30px; 
            color: #6c757d; 
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        }

        .empty-state-icon { 
            font-size: 5rem; 
            margin-bottom: 25px; 
            color: #dee2e6; 
            opacity: 0.7;
        }

        .empty-state h3 { 
            margin-bottom: 15px; 
            color: #495057; 
            font-size: 1.5rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            body { padding: 15px; }
            .header-section { padding: 25px 20px; }
            .header-section h1 { font-size: 2.2rem; }
            .dashboard-stats { 
                grid-template-columns: repeat(2, 1fr); 
                padding: 30px 25px; 
                gap: 20px;
            }
            .controls-panel { 
                flex-direction: column; 
                align-items: stretch; 
                padding: 25px 20px;
            }
            .data-table th, .data-table td { 
                padding: 12px 8px; 
                font-size: 0.85rem; 
            }
            .action-btn {
                font-size: 0.75rem;
                padding: 6px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h1><i class="fas fa-shopping-cart"></i> Student Merchandise Orders</h1>
            <p class="subtitle">Manage and track all student merchandise orders</p>
        </div>

        <div class="message-alert" id="messageAlert">
            <i class="fas fa-check-circle"></i>
            <span id="messageText"></span>
            <button class="close-btn" onclick="closeMessage()">&times;</button>
        </div>

        <div class="dashboard-stats">
            <div class="stat-widget">
                <i class="fas fa-clipboard-list stat-icon total-orders"></i>
                <div class="stat-number total-orders" id="totalStat"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-widget">
                <i class="fas fa-clock stat-icon pending-orders"></i>
                <div class="stat-number pending-orders" id="pendingStat"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-widget">
                <i class="fas fa-check-circle stat-icon completed-orders"></i>
                <div class="stat-number completed-orders" id="completedStat"><?= $stats['completed'] ?></div>
                <div class="stat-label">Approved Orders</div>
            </div>
            <div class="stat-widget">
                <i class="fas fa-times-circle stat-icon cancelled-orders"></i>
                <div class="stat-number cancelled-orders" id="cancelledStat"><?= $stats['cancelled'] ?></div>
                <div class="stat-label">Rejected Orders</div>
            </div>
        </div>

        <div class="controls-panel">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Search orders...">
                <i class="fas fa-search search-icon"></i>
            </div>
            <select class="filter-select" id="statusFilter">
                <option value="">All Orders</option>
                <option value="pending">Pending Orders</option>
                <option value="completed">Approved Orders</option>
                <option value="cancelled">Rejected Orders</option>
            </select>
        </div>

        <div class="data-table-wrapper">
            <?php if ($result && $result->num_rows > 0): ?>
            <table class="data-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Student</th>
                        <th>Roll No.</th>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Total Price</th>
                        <th>Payment</th>
                        <th>Ordered At</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $status = trim($row['status']);
                        $displayStatus = empty($status) || $status === 'pending' ? 'pending' : $status;
                        $isPending = empty($status) || $status === 'pending';
                    ?>
                    <tr class="order-row" data-status="<?= htmlspecialchars($displayStatus) ?>" data-order-id="<?= $row['order_id'] ?>">
                        <td class="order-id-cell">#<?= $row['order_id'] ?></td>
                        <td class="student-name"><?= htmlspecialchars($row['student_name']) ?></td>
                        <td class="roll-number"><?= htmlspecialchars($row['roll_number']) ?></td>
                        <td class="item-name-cell" title="<?= htmlspecialchars($row['item_name']) ?>"><?= htmlspecialchars($row['item_name']) ?></td>
                        <td class="quantity-cell"><?= $row['quantity'] ?></td>
                        <td class="price-cell">₹<?= number_format($row['total_price'], 2) ?></td>
                        <td class="payment-method-cell"><?= $row['payment_method'] ? htmlspecialchars($row['payment_method']) : 'N/A' ?></td>
                        <td class="date-cell"><?= $row['ordered_at'] ? date('M d, Y H:i', strtotime($row['ordered_at'])) : '—' ?></td>
                        <td class="status-cell">
                            <span class="status-badge <?= htmlspecialchars($displayStatus) ?>">
                                <i class="fas <?= $displayStatus === 'pending' ? 'fa-clock' : ($displayStatus === 'completed' ? 'fa-check' : 'fa-times') ?>"></i>
                                <?= $displayStatus === 'completed' ? 'approved' : ($displayStatus === 'cancelled' ? 'rejected' : $displayStatus) ?>
                            </span>
                        </td>
                        <td class="actions-cell">
                            <?php if ($isPending): ?>
                                <button class="action-btn approve-btn" onclick="updateOrderStatus(<?= $row['order_id'] ?>, 'completed', this)">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="action-btn reject-btn" onclick="updateOrderStatus(<?= $row['order_id'] ?>, 'cancelled', this)">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            <?php else: ?>
                                <span style="font-size: 0.85rem; color: #6c757d;">
                                    <?= $displayStatus === 'completed' ? 'Approved' : 'Rejected' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-inbox empty-state-icon"></i>
                <h3>No Orders Found</h3>
                <p>There are currently no merchandise orders to display.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showMessage(message, type) {
            const messageAlert = document.getElementById('messageAlert');
            const messageText = document.getElementById('messageText');
            const icon = messageAlert.querySelector('i');
            
            messageText.textContent = message;
            messageAlert.className = `message-alert ${type}`;
            icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-times-circle';
            messageAlert.style.display = 'flex';
            
            if (type === 'success') {
                setTimeout(closeMessage, 4000);
            }
        }

        function closeMessage() {
            document.getElementById('messageAlert').style.display = 'none';
        }

        function updateOrderStatus(orderId, status, button) {
            if (!confirm(`Are you sure you want to ${status === 'completed' ? 'approve' : 'reject'} order #${orderId}?`)) {
                return;
            }
            
            const row = button.closest('tr');
            row.classList.add('loading');
            
            const formData = new FormData();
            formData.append('ajax_request', '1');
            formData.append('order_id', orderId);
            formData.append('status', status);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                row.classList.remove('loading');
                
                if (data.success) {
                    // Update the status badge
                    const statusCell = row.querySelector('.status-cell');
                    const statusBadge = statusCell.querySelector('.status-badge');
                    statusBadge.className = `status-badge ${status}`;
                    statusBadge.innerHTML = `<i class="fas ${status === 'completed' ? 'fa-check' : 'fa-times'}"></i> ${status === 'completed' ? 'approved' : 'rejected'}`;
                    
                    // Update the actions cell
                    const actionsCell = row.querySelector('.actions-cell');
                    actionsCell.innerHTML = `<span style="font-size: 0.85rem; color: #6c757d;">${status === 'completed' ? 'Approved' : 'Rejected'}</span>`;
                    
                    // Update row data attribute
                    row.setAttribute('data-status', status);
                    
                    // Add highlight animation
                    row.classList.add('updated-row');
                    setTimeout(() => {
                        row.classList.remove('updated-row');
                    }, 4000);
                    
                    // Update statistics
                    updateStats();
                    
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message || 'An error occurred', 'error');
                }
            })
            .catch(error => {
                row.classList.remove('loading');
                console.error('Error:', error);
                showMessage('Network error occurred', 'error');
            });
        }

        function updateStats() {
            const rows = document.querySelectorAll('.order-row');
            const stats = { total: 0, pending: 0, completed: 0, cancelled: 0 };
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                stats.total++;
                if (stats[status] !== undefined) {
                    stats[status]++;
                }
            });
            
            document.getElementById('totalStat').textContent = stats.total;
            document.getElementById('pendingStat').textContent = stats.pending;
            document.getElementById('completedStat').textContent = stats.completed;
            document.getElementById('cancelledStat').textContent = stats.cancelled;
        }

        // Search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const orderRows = document.querySelectorAll('.order-row');

            function filterOrders() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const statusValue = statusFilter.value.toLowerCase();

                orderRows.forEach(row => {
                    const studentName = row.querySelector('.student-name').textContent.toLowerCase();
                    const rollNumber = row.querySelector('.roll-number').textContent.toLowerCase();
                    const itemName = row.querySelector('.item-name-cell').textContent.toLowerCase();
                    const orderId = row.querySelector('.order-id-cell').textContent.toLowerCase();
                    const status = row.dataset.status.toLowerCase();

                    const matchesSearch = !searchTerm || 
                                        studentName.includes(searchTerm) || 
                                        rollNumber.includes(searchTerm) || 
                                        itemName.includes(searchTerm) ||
                                        orderId.includes(searchTerm);
                    
                    const matchesStatus = !statusValue || status === statusValue;

                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            searchInput.addEventListener('input', filterOrders);
            statusFilter.addEventListener('change', filterOrders);
        });
    </script>
</body>
</html>