<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Approve/Reject action
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['order_id'], $_POST['action'])) {
    $order_id = intval($_POST['order_id']);
    $action = ($_POST['action'] === 'approve') ? 'approved' : 'rejected';
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ? AND status = 'pending'");
    $stmt->bind_param("si", $action, $order_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch orders with student and item details
$query = "
    SELECT o.order_id, o.item_id, o.quantity, o.total_price, o.status, o.ordered_at,
           m.name AS item_name, sp.name AS student_name, sp.roll_number
    FROM orders o
    JOIN merchandise m ON o.item_id = m.item_id
    JOIN student_profiles sp ON o.user_id = sp.user_id
    ORDER BY o.order_id DESC
";
$result = $conn->query($query);

// Calculate statistics
$stats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $stats['total']++;
        if (isset($stats[$row['status']])) {
            $stats[$row['status']]++;
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px; color: #333;
        }

        .main-container {
            max-width: 1600px; margin: 0 auto; background: rgba(255, 255, 255, 0.98); border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.15); backdrop-filter: blur(20px); overflow: hidden;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(50px); } to { opacity: 1; transform: translateY(0); } }

        .header-section {
            background: linear-gradient(135deg, #4a00e0 0%, #6a4c93 100%); color: white; padding: 40px;
            text-align: center; position: relative; overflow: hidden;
        }
        .header-section::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="1" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: sparkle 15s linear infinite;
        }
        @keyframes sparkle { 0% { transform: translateX(-100px) translateY(-100px) rotate(0deg); } 100% { transform: translateX(100px) translateY(100px) rotate(360deg); } }

        .header-section h1 { font-size: 3rem; font-weight: 800; margin-bottom: 15px; position: relative; z-index: 1; text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); }
        .header-section .subtitle { font-size: 1.2rem; opacity: 0.95; position: relative; z-index: 1; font-weight: 300; }

        .dashboard-stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 25px; padding: 35px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-bottom: 1px solid #dee2e6;
        }

        .stat-widget {
            background: white; padding: 25px; border-radius: 18px; text-align: center; position: relative; overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08); transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-widget::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; transition: left 0.6s;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
        }
        .stat-widget:hover::before { left: 100%; }
        .stat-widget:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15); }

        .stat-icon { font-size: 2.5rem; margin-bottom: 15px; display: block; }
        .stat-number { font-size: 2.8rem; font-weight: 900; margin-bottom: 8px; line-height: 1; }
        .stat-label { color: #6c757d; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

        .total-orders { color: #4a00e0; }
        .pending-orders { color: #ffc107; }
        .approved-orders { color: #28a745; }
        .rejected-orders { color: #dc3545; }

        .controls-panel {
            padding: 30px 35px; background: #f8f9fa; border-bottom: 1px solid #e9ecef;
            display: flex; gap: 20px; flex-wrap: wrap; align-items: center; justify-content: space-between;
        }

        .search-container { flex: 1; max-width: 400px; position: relative; }
        .search-input {
            width: 100%; padding: 15px 50px 15px 20px; border: 2px solid #e9ecef; border-radius: 50px;
            font-size: 16px; background: white; transition: all 0.3s ease; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .search-input:focus { outline: none; border-color: #4a00e0; box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1); }
        .search-icon { position: absolute; right: 18px; top: 50%; transform: translateY(-50%); color: #6c757d; font-size: 18px; }

        .filter-select {
            padding: 15px 20px; border: 2px solid #e9ecef; border-radius: 50px; font-size: 16px;
            background: white; cursor: pointer; transition: all 0.3s ease; min-width: 150px;
        }
        .filter-select:focus { outline: none; border-color: #4a00e0; box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1); }

        .data-table-wrapper { overflow-x: auto; background: white; }
        .data-table { width: 100%; border-collapse: collapse; background: white; }
        .data-table th, .data-table td { padding: 20px; text-align: left; border-bottom: 1px solid #f1f3f4; vertical-align: middle; }
        .data-table th {
            background: linear-gradient(135deg, #4a00e0 0%, #6a4c93 100%); color: white; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.8px; font-size: 0.9rem; position: sticky; top: 0; z-index: 100;
        }
        .data-table tbody tr { transition: all 0.3s ease; cursor: pointer; }
        .data-table tbody tr:hover {
            background: linear-gradient(135deg, #f8f9ff 0%, #e8ebff 100%); transform: scale(1.005);
            box-shadow: 0 5px 20px rgba(74, 0, 224, 0.1);
        }

        .order-id-cell { font-weight: 700; color: #4a00e0; font-size: 1.1rem; }
        .student-name { font-weight: 600; color: #2c3e50; }
        .roll-number { font-size: 0.85rem; color: #7f8c8d; font-weight: 500; }
        .item-name-cell { font-weight: 500; color: #34495e; max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
        .quantity-cell { font-weight: 700; color: #4a00e0; font-size: 1.1rem; text-align: center; }
        .price-cell { font-weight: 700; color: #27ae60; font-size: 1.1rem; }
        .date-cell { color: #7f8c8d; font-size: 0.95rem; }

        .status-badge {
            font-weight: 700; text-transform: lowercase; padding: 10px 18px; border-radius: 25px; font-size: 0.9rem;
            display: inline-flex; align-items: center; gap: 8px; min-width: 110px; justify-content: center;
        }
        .status-badge.pending { background: linear-gradient(135deg, #fff3cd, #ffeaa7); color: #856404; }
        .status-badge.approved { background: linear-gradient(135deg, #d4edda, #a8e6a3); color: #155724; }
        .status-badge.rejected { background: linear-gradient(135deg, #f8d7da, #ff7675); color: #721c24; }

        .action-buttons { display: flex; justify-content: center; gap: 12px; }
        .btn {
            padding: 12px 20px; border: none; border-radius: 25px; font-weight: 700; cursor: pointer; font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: inline-flex; align-items: center; gap: 8px;
            text-transform: uppercase; letter-spacing: 0.5px; min-width: 110px; justify-content: center; position: relative; overflow: hidden;
        }
        .btn::before {
            content: ''; position: absolute; top: 50%; left: 50%; width: 0; height: 0; border-radius: 50%; transform: translate(-50%, -50%);
            background: rgba(255, 255, 255, 0.3); transition: width 0.6s, height 0.6s;
        }
        .btn:hover::before { width: 300px; height: 300px; }

        .btn-approve { background: linear-gradient(135deg, #28a745, #20c997); color: white; box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4); }
        .btn-approve:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(40, 167, 69, 0.5); }
        .btn-reject { background: linear-gradient(135deg, #dc3545, #e74c3c); color: white; box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4); }
        .btn-reject:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(220, 53, 69, 0.5); }

        .action-disabled { opacity: 0.6; cursor: not-allowed; color: #6c757d; font-style: italic; display: inline-flex; align-items: center; gap: 8px; font-weight: 500; }

        .empty-state { text-align: center; padding: 100px 40px; color: #6c757d; background: white; }
        .empty-state-icon { font-size: 5rem; margin-bottom: 30px; color: #dee2e6; }
        .empty-state h3 { margin-bottom: 15px; color: #495057; font-size: 1.5rem; }

        .no-results { text-align: center; padding: 60px 20px; color: #6c757d; background: white; }
        .no-results i { font-size: 3rem; margin-bottom: 20px; color: #dee2e6; }

        .loading-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7);
            z-index: 9999; justify-content: center; align-items: center;
        }
        .loading-content { background: white; padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
        .loading-spinner { font-size: 2rem; color: #4a00e0; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            body { padding: 10px; }
            .header-section { padding: 30px 20px; }
            .header-section h1 { font-size: 2.2rem; }
            .dashboard-stats { grid-template-columns: repeat(2, 1fr); gap: 15px; padding: 25px 20px; }
            .controls-panel { flex-direction: column; align-items: stretch; gap: 15px; padding: 25px 20px; }
            .search-container { max-width: none; }
            .data-table th, .data-table td { padding: 15px 10px; font-size: 0.9rem; }
            .action-buttons { flex-direction: column; gap: 8px; }
            .btn { min-width: auto; padding: 10px 16px; font-size: 0.8rem; }
        }

        @media (max-width: 480px) {
            .dashboard-stats { grid-template-columns: 1fr; }
            .header-section h1 { font-size: 1.8rem; }
            .stat-number { font-size: 2.2rem; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <h1><i class="fas fa-shopping-cart"></i> Student Merchandise Orders</h1>
            <p class="subtitle">Manage and track all student merchandise orders efficiently</p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-widget">
                <i class="fas fa-clipboard-list stat-icon total-orders"></i>
                <div class="stat-number total-orders"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-widget">
                <i class="fas fa-clock stat-icon pending-orders"></i>
                <div class="stat-number pending-orders"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-widget">
                <i class="fas fa-check-circle stat-icon approved-orders"></i>
                <div class="stat-number approved-orders"><?= $stats['approved'] ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-widget">
                <i class="fas fa-times-circle stat-icon rejected-orders"></i>
                <div class="stat-number rejected-orders"><?= $stats['rejected'] ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <div class="controls-panel">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by student name, roll number, or item...">
                <i class="fas fa-search search-icon"></i>
            </div>
            <div class="filter-group">
                <select class="filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="pending">Pending Orders</option>
                    <option value="approved">Approved Orders</option>
                    <option value="rejected">Rejected Orders</option>
                </select>
            </div>
        </div>

        <div class="data-table-wrapper">
            <?php if ($result && $result->num_rows > 0): ?>
            <table class="data-table" id="ordersTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> Order ID</th>
                        <th><i class="fas fa-user-graduate"></i> Student</th>
                        <th><i class="fas fa-id-badge"></i> Roll Number</th>
                        <th><i class="fas fa-box"></i> Item</th>
                        <th><i class="fas fa-sort-numeric-up"></i> Qty</th>
                        <th><i class="fas fa-rupee-sign"></i> Total Price</th>
                        <th><i class="fas fa-calendar-alt"></i> Ordered At</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-cogs"></i> Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="order-row" data-status="<?= htmlspecialchars($row['status']) ?>">
                        <td class="order-id-cell">#<?= $row['order_id'] ?></td>
                        <td class="student-name"><?= htmlspecialchars($row['student_name']) ?></td>
                        <td class="roll-number"><?= htmlspecialchars($row['roll_number']) ?></td>
                        <td class="item-name-cell" title="<?= htmlspecialchars($row['item_name']) ?>"><?= htmlspecialchars($row['item_name']) ?></td>
                        <td class="quantity-cell"><?= $row['quantity'] ?></td>
                        <td class="price-cell">₹<?= number_format($row['total_price'], 2) ?></td>
                        <td class="date-cell"><?= $row['ordered_at'] ? date('M d, Y H:i', strtotime($row['ordered_at'])) : '—' ?></td>
                        <td>
                            <span class="status-badge <?= htmlspecialchars($row['status']) ?>">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <i class="fas fa-clock"></i>
                                <?php elseif ($row['status'] === 'approved'): ?>
                                    <i class="fas fa-check"></i>
                                <?php else: ?>
                                    <i class="fas fa-times"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                            <form method="POST" class="action-buttons">
                                <input type="hidden" name="order_id" value="<?= $row['order_id'] ?>">
                                <button type="submit" name="action" value="approve" class="btn btn-approve" onclick="confirmAction(event, 'approve')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button type="submit" name="action" value="reject" class="btn btn-reject" onclick="confirmAction(event, 'reject')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="action-disabled"><i class="fas fa-check-circle"></i> Action taken</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="no-results" id="noResults" style="display: none;">
                <i class="fas fa-search"></i>
                <h3>No Results Found</h3>
                <p>No orders match your current search criteria.</p>
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

    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <i class="fas fa-spinner loading-spinner"></i>
            <h3 style="margin-top: 20px; color: #333;">Processing...</h3>
        </div>
    </div>

    <script>
        function confirmAction(event, action) {
            if (!confirm(`Are you sure you want to ${action} this order?`)) {
                event.preventDefault();
                return false;
            }
            
            document.getElementById('loadingOverlay').style.display = 'flex';
            const button = event.target.closest('button');
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;
            
            setTimeout(() => {
                if (button.disabled) {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    document.getElementById('loadingOverlay').style.display = 'none';
                }
            }, 5000);
            
            return true;
        }

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

                if (dataTable) {
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

            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
                
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    statusFilter.value = '';
                    filterOrders();
                    searchInput.blur();
                }
            });
        });
    </script>
</body>
</html>