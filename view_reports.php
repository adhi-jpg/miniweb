<?php
session_start();
include "config.php";

// Ensure only faculty can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}

$status_msg = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);
    $action = $_POST['action'];
    $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);

    if (in_array($action, ['reviewed', 'rejected'])) {
        $query = "UPDATE reports SET status = '$action', feedback = '$feedback' WHERE id = $report_id";
        if ($conn->query($query)) {
            $status_msg = "✅ Report #$report_id updated successfully.";
        } else {
            $status_msg = "❌ Error updating report.";
        }
    }
}

// Fetch all submitted reports
$result = $conn->query("SELECT * FROM reports WHERE status = 'submitted' ORDER BY submitted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty – Review Reports</title>
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
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 30px;
            backdrop-filter: blur(10px);
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 2.5rem;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-filter {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 12px 20px 12px 45px;
            border: 2px solid #e0e6ed;
            border-radius: 25px;
            font-size: 14px;
            width: 300px;
            transition: all 0.3s ease;
            background: #fff;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        .filter-select {
            padding: 12px 20px;
            border: 2px solid #e0e6ed;
            border-radius: 25px;
            font-size: 14px;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            flex: 1;
            min-width: 150px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 5px;
        }

        .status {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(21, 87, 36, 0.1);
            font-weight: 500;
            display: none;
        }

        .status.show {
            display: block;
        }

        .status.error {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            color: #721c24;
            box-shadow: 0 5px 15px rgba(114, 28, 36, 0.1);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background: #f8f9ff;
            transform: scale(1.01);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-reviewed {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .download-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        textarea {
            padding: 12px;
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
            transition: all 0.3s ease;
            width: 100%;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        select {
            padding: 10px 15px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 13px;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .action-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .action-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .no-reports {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
            font-size: 18px;
        }

        .no-reports-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .modal-buttons {
            text-align: center;
            margin-top: 20px;
        }

        .modal-buttons button {
            margin: 0 10px;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
                margin: 10px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .stats {
                flex-direction: column;
            }
            
            th, td {
                padding: 10px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Faculty – Review Reports</h1>
        
        <!-- Controls -->
        <div class="controls">
            <div class="search-filter">
                <div class="search-box">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="searchInput" placeholder="Search reports...">
                </div>
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="reviewed">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select id="sortBy" class="filter-select">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="title">Sort by Title</option>
                    <option value="author">Sort by Author</option>
                </select>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats" id="statsContainer">
            <div class="stat-card">
                <span class="stat-number" id="totalCount">0</span>
                <span class="stat-label">Total Reports</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="pendingCount">0</span>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="approvedCount">0</span>
                <span class="stat-label">Approved</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="rejectedCount">0</span>
                <span class="stat-label">Rejected</span>
            </div>
        </div>

        <!-- Status Message -->
        <?php if (isset($status_msg) && $status_msg): ?>
            <div class="status show"><?= htmlspecialchars($status_msg) ?></div>
        <?php endif; ?>

        <!-- Loading -->
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Processing...</p>
        </div>

        <?php if (isset($result) && $result->num_rows > 0): ?>
            <!-- Table -->
            <div class="table-container">
                <table id="reportsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Submitted By</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>File</th>
                            <th>Submitted At</th>
                            <th>Status</th>
                            <th>Feedback</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr data-id="<?= $row['id'] ?>" data-status="<?= htmlspecialchars($row['status'] ?? 'pending') ?>">
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td><?= htmlspecialchars($row['submitted_by']) ?></td>
                                <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                <td>
                                    <div title="<?= htmlspecialchars($row['description']) ?>">
                                        <?= strlen($row['description']) > 100 ? 
                                            htmlspecialchars(substr($row['description'], 0, 100)) . '...' : 
                                            htmlspecialchars($row['description']) ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= htmlspecialchars($row['file_path']) ?>" class="download-btn" target="_blank">
                                        📎 Download
                                    </a>
                                </td>
                                <td><?= date('M j, Y g:i A', strtotime($row['submitted_at'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= htmlspecialchars($row['status'] ?? 'pending') ?>">
                                        <?= ucfirst(htmlspecialchars($row['status'] ?? 'pending')) ?>
                                    </span>
                                </td>
                                <td>
                                    <div title="<?= htmlspecialchars($row['feedback']) ?>">
                                        <?= $row['feedback'] ? 
                                            (strlen($row['feedback']) > 50 ? 
                                                htmlspecialchars(substr($row['feedback'], 0, 50)) . '...' : 
                                                htmlspecialchars($row['feedback'])) : 
                                            '<em>No feedback yet</em>' ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="post" class="review-form" data-report-id="<?= $row['id'] ?>">
                                        <input type="hidden" name="report_id" value="<?= $row['id'] ?>">
                                        <textarea 
                                            name="feedback" 
                                            placeholder="Enter feedback..."
                                            required
                                        ><?= htmlspecialchars($row['feedback']) ?></textarea>
                                        <select name="action" required>
                                            <option value="">Select Action</option>
                                            <option value="reviewed" <?= ($row['status'] ?? '') === 'reviewed' ? 'selected' : '' ?>>
                                                Approve
                                            </option>
                                            <option value="rejected" <?= ($row['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>
                                                Reject
                                            </option>
                                        </select>
                                        <button type="submit" class="action-btn">
                                            <?= ($row['status'] ?? 'pending') === 'pending' ? 'Submit Review' : 'Update Review' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- No reports message -->
            <div class="no-reports">
                <div class="no-reports-icon">📋</div>
                <p>No submitted reports available.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3 id="modalTitle">Confirm Action</h3>
            <p id="modalMessage"></p>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button id="confirmBtn" class="btn-confirm">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Initialize statistics and functionality on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateStats();
            setupEventListeners();
            setupFormHandlers();
        });

        function calculateStats() {
            const rows = document.querySelectorAll('tbody tr');
            let total = rows.length;
            let pending = 0;
            let approved = 0;
            let rejected = 0;

            rows.forEach(row => {
                const status = row.getAttribute('data-status') || 'pending';
                switch(status) {
                    case 'pending':
                        pending++;
                        break;
                    case 'reviewed':
                        approved++;
                        break;
                    case 'rejected':
                        rejected++;
                        break;
                }
            });

            document.getElementById('totalCount').textContent = total;
            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('approvedCount').textContent = approved;
            document.getElementById('rejectedCount').textContent = rejected;
        }

        function setupEventListeners() {
            // Search functionality
            document.getElementById('searchInput').addEventListener('input', filterReports);
            
            // Status filter
            document.getElementById('statusFilter').addEventListener('change', filterReports);
            
            // Sort functionality
            document.getElementById('sortBy').addEventListener('change', sortReports);
        }

        function setupFormHandlers() {
            const forms = document.querySelectorAll('.review-form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const action = formData.get('action');
                    const feedback = formData.get('feedback').trim();
                    
                    if (!action) {
                        showMessage('Please select an action (Approve/Reject)', 'error');
                        return;
                    }
                    
                    if (!feedback) {
                        showMessage('Please provide feedback before submitting', 'error');
                        return;
                    }
                    
                    // Show confirmation modal
                    showConfirmModal(
                        'Confirm Review Submission',
                        `Are you sure you want to ${action === 'reviewed' ? 'approve' : 'reject'} this report?`,
                        () => submitForm(form)
                    );
                });
            });
        }

        function submitForm(form) {
            const submitBtn = form.querySelector('.action-btn');
            const originalText = submitBtn.textContent;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            document.getElementById('loading').style.display = 'block';
            
            // Submit the form
            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.text())
            .then(html => {
                // Reload the page to show updated data
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred while processing the request', 'error');
                
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                document.getElementById('loading').style.display = 'none';
            });
        }

        function filterReports() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('tbody tr');

            let visibleCount = 0;

            rows.forEach(row => {
                const title = row.children[2].textContent.toLowerCase();
                const author = row.children[1].textContent.toLowerCase();
                const description = row.children[3].textContent.toLowerCase();
                const status = row.getAttribute('data-status');

                const matchesSearch = !searchTerm || 
                    title.includes(searchTerm) ||
                    author.includes(searchTerm) ||
                    description.includes(searchTerm);

                const matchesStatus = !statusFilter || status === statusFilter;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Show/hide no results message
            const table = document.querySelector('.table-container');
            const noResults = document.querySelector('.no-reports');
            
            if (visibleCount === 0 && rows.length > 0) {
                table.style.display = 'none';
                if (!noResults) {
                    const noResultsDiv = document.createElement('div');
                    noResultsDiv.className = 'no-reports';
                    noResultsDiv.innerHTML = `
                        <div class="no-reports-icon">🔍</div>
                        <p>No reports found matching your criteria.</p>
                    `;
                    document.querySelector('.container').appendChild(noResultsDiv);
                }
            } else {
                table.style.display = 'block';
                if (noResults && rows.length > 0) {
                    noResults.remove();
                }
            }
        }

        function sortReports() {
            const tbody = document.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const sortBy = document.getElementById('sortBy').value;

            rows.sort((a, b) => {
                switch(sortBy) {
                    case 'newest':
                        const dateA = new Date(a.children[5].textContent);
                        const dateB = new Date(b.children[5].textContent);
                        return dateB - dateA;
                    case 'oldest':
                        const dateA2 = new Date(a.children[5].textContent);
                        const dateB2 = new Date(b.children[5].textContent);
                        return dateA2 - dateB2;
                    case 'title':
                        return a.children[2].textContent.localeCompare(b.children[2].textContent);
                    case 'author':
                        return a.children[1].textContent.localeCompare(b.children[1].textContent);
                    default:
                        return 0;
                }
            });

            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
        }

        function showMessage(message, type) {
            // Remove existing dynamic messages
            const existingMsg = document.querySelector('.status.dynamic');
            if (existingMsg) {
                existingMsg.remove();
            }

            const messageDiv = document.createElement('div');
            messageDiv.className = `status dynamic ${type === 'error' ? 'error' : ''}`;
            messageDiv.textContent = message;
            
            const statsContainer = document.getElementById('statsContainer');
            statsContainer.insertAdjacentElement('afterend', messageDiv);
            
            // Trigger show animation
            setTimeout(() => messageDiv.classList.add('show'), 10);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                messageDiv.classList.remove('show');
                setTimeout(() => messageDiv.remove(), 300);
            }, 5000);
        }

        function showConfirmModal(title, message, onConfirm) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('confirmBtn').onclick = () => {
                onConfirm();
                closeModal();
            };
            document.getElementById('confirmModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>