<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}



// Fetch orders
$query = "
    SELECT o.order_id, o.item_id, o.quantity, o.total_price, o.status, o.ordered_at, o.payment_method,
           m.name AS item_name, sp.name AS student_name, sp.roll_number
    FROM orders o
    JOIN merchandise m ON o.item_id = m.item_id
    JOIN student_profiles sp ON o.user_id = sp.user_id
    ORDER BY 
        CASE o.status WHEN 'pending' THEN 1 WHEN 'approved' THEN 2 WHEN 'rejected' THEN 3 END,
        o.order_id DESC
";
$result = $conn->query($query);

// Calculate statistics
$stats = ['total' => 0, 'pending' => 0];
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $stats['total']++;
        if ($row['status'] === 'pending') {
            $stats['pending']++;
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

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="40" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="40" cy="80" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translateX(-100px); }
            100% { transform: translateX(100px); }
        }

        .header-section h1 { 
            font-size: 3rem; 
            font-weight: 800; 
            margin-bottom: 12px; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            position: relative;
            z-index: 2;
        }
        
        .header-section .subtitle { 
            font-size: 1.2rem; 
            opacity: 0.95; 
            font-weight: 300;
            position: relative;
            z-index: 2;
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
        
        .message-alert .close-btn:hover { 
            opacity: 1; 
            background: rgba(0,0,0,0.1);
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
            position: relative;
            overflow: hidden;
        }

        .stat-widget::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.8), transparent);
            transition: left 0.5s;
        }

        .stat-widget:hover { 
            transform: translateY(-8px) scale(1.02); 
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
        }

        .stat-widget:hover::before {
            left: 100%;
        }

        .stat-icon { 
            font-size: 2.5rem; 
            margin-bottom: 15px; 
            transition: transform 0.3s;
        }
        
        .stat-widget:hover .stat-icon {
            transform: scale(1.1);
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
        .approved-orders { color: #00c896; }
        .rejected-orders { color: #e74c3c; }

        .controls-panel {
            padding: 30px; 
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%); 
            border-bottom: 1px solid #e9ecef;
            display: flex; 
            gap: 20px; 
            flex-wrap: wrap; 
            align-items: center;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05);
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
            transform: translateY(-2px);
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

        .filter-select:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 5px 25px rgba(102, 126, 234, 0.2);
        }

        .data-table-wrapper { 
            overflow-x: auto; 
            background: white; 
            border-radius: 0 0 25px 25px;
        }

        .data-table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 800px; 
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
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tbody tr {
            transition: all 0.3s;
        }

        .data-table tbody tr:hover { 
            background: linear-gradient(135deg, #f8f9ff 0%, #e8f2ff 100%); 
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            border: 2px solid transparent;
        }

        .status-badge.pending { 
            background: linear-gradient(135deg, #fff3cd, #ffeaa7); 
            color: #856404; 
            border-color: #f39c12;
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

        .empty-state p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .no-results { 
            text-align: center; 
            padding: 60px 20px; 
            color: #6c757d; 
            display: none; 
            background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
        }

        .no-results i { 
            font-size: 3rem; 
            margin-bottom: 20px; 
            color: #dee2e6; 
        }

        .no-results h3 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            font-weight: 600;
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

        @media (max-width: 768px) {
            body { padding: 15px; }
            
            .header-section { padding: 25px 20px; }
            .header-section h1 { font-size: 2.2rem; }
            
            .dashboard-stats { 
                grid-template-columns: repeat(2, 1fr); 
                padding: 30px 25px; 
                gap: 20px;
            }
            
            .stat-widget { padding: 20px 15px; }
            .stat-number { font-size: 2.2rem; }
            
            .controls-panel { 
                flex-direction: column; 
                align-items: stretch; 
                padding: 25px 20px;
            }
            
            .search-container { max-width: none; }
            
            .data-table th, .data-table td { 
                padding: 12px 8px; 
                font-size: 0.85rem; 
            }
        }

        @media (max-width: 480px) {
            .dashboard-stats { 
                grid-template-columns: 1fr; 
            }
            
            .header-section h1 { font-size: 1.8rem; }
            .header-section .subtitle { font-size: 1rem; }
            
            .data-table th, .data-table td { 
                padding: 10px 6px; 
                font-size: 0.8rem; 
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
        </div>

        <div class="controls-panel">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Search orders...">
                <i class="fas fa-search search-icon"></i>
            </div>
            <select class="filter-select" id="statusFilter">
                <option value="">All Orders</option>
                <option value="pending">Pending Orders</option>
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
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="order-row" data-status="<?= htmlspecialchars($row['status']) ?>" data-order-id="<?= $row['order_id'] ?>">
                        <td class="order-id-cell">#<?= $row['order_id'] ?></td>
                        <td class="student-name"><?= htmlspecialchars($row['student_name']) ?></td>
                        <td class="roll-number"><?= htmlspecialchars($row['roll_number']) ?></td>
                        <td class="item-name-cell" title="<?= htmlspecialchars($row['item_name']) ?>"><?= htmlspecialchars($row['item_name']) ?></td>
                        <td class="quantity-cell"><?= $row['quantity'] ?></td>
                        <td class="price-cell">₹<?= number_format($row['total_price'], 2) ?></td>
                        <td class="payment-method-cell"><?= $row['payment_method'] ? htmlspecialchars($row['payment_method']) : 'N/A' ?></td>
                        <td class="date-cell"><?= $row['ordered_at'] ? date('M d, Y H:i', strtotime($row['ordered_at'])) : '—' ?></td>
                        <td class="status-cell">
                            <span class="status-badge <?= htmlspecialchars($row['status']) ?>">
                                <i class="fas fa-clock"></i>
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="no-results" id="noResults">
                <i class="fas fa-search"></i>
                <h3>No Results Found</h3>
                <p>Try adjusting your search criteria</p>
            </div>
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

        // Search and filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const statusFilter = document.getElementById('statusFilter');
            const orderRows = document.querySelectorAll('.order-row');
            const noResults = document.getElementById('noResults');
            const dataTable = document.getElementById('ordersTable');

            function filterOrders() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const statusValue = statusFilter.value.toLowerCase();
                let visibleCount = 0;

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
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (dataTable && noResults) {
                    if (visibleCount === 0 && orderRows.length > 0) {
                        dataTable.style.display = 'none';
                        noResults.style.display = 'block';
                    } else {
                        dataTable.style.display = 'table';
                        noResults.style.display = 'none';
                    }
                }
            }

            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(filterOrders, 300);
            });

            statusFilter.addEventListener('change', filterOrders);
        });
    </script>
</body>
</html>