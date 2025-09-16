<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle AJAX request for modal view
if (isset($_GET['action']) && $_GET['action'] == 'get_details') {
    header('Content-Type: application/json');
    
    $confirmation_id = intval($_GET['id']);
    
    $query = "SELECT dc.*, o.order_id, o.quantity, o.total_price, m.name as item_name, 
              sp.name as student_name, sp.roll_number
              FROM delivery_confirmations dc
              JOIN orders o ON dc.order_id = o.order_id
              JOIN merchandise m ON o.item_id = m.item_id
              JOIN student_profiles sp ON dc.user_id = sp.user_id
              WHERE dc.id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $confirmation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $files = json_decode($data['proof_files'], true) ?: [];
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'files' => $files
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No data found']);
    }
    exit();
}

// Handle file download
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    if (file_exists($file) && strpos($file, 'uploads/') === 0) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
    }
    exit();
}

// Get all confirmations
$query = "SELECT dc.id, dc.order_id, dc.delivery_notes, dc.proof_files, dc.submitted_at,
          o.quantity, o.total_price, m.name as item_name,
          sp.name as student_name, sp.roll_number
          FROM delivery_confirmations dc
          JOIN orders o ON dc.order_id = o.order_id
          JOIN merchandise m ON o.item_id = m.item_id
          JOIN student_profiles sp ON dc.user_id = sp.user_id
          ORDER BY dc.submitted_at DESC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Confirmations - Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning: linear-gradient(135deg, #fdbb2d 0%, #22c1c3 100%);
            --danger: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);
            --dark: #2c3e50;
            --light: #ecf0f1;
            --white: #ffffff;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 60px rgba(0,0,0,0.15);
            --border-radius: 15px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            animation: slideInDown 0.8s ease-out;
        }

        .header h1 {
            color: var(--white);
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 2px 2px 10px rgba(0,0,0,0.3);
        }

        .header p {
            color: rgba(255,255,255,0.9);
            font-size: 1.2rem;
            font-weight: 300;
        }

        .main-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            backdrop-filter: blur(20px);
            animation: slideInUp 0.8s ease-out;
        }

        .card-header {
            background: var(--primary);
            padding: 2rem;
            text-align: center;
        }

        .card-header h2 {
            color: var(--white);
            font-size: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 2rem;
            background: rgba(255,255,255,0.05);
        }

        .stat-item {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--dark);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .table-container {
            overflow-x: auto;
            margin: 2rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .table thead th {
            color: var(--white);
            font-weight: 600;
            padding: 1.5rem 1rem;
            text-align: left;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .table tbody tr:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1.2rem 1rem;
            vertical-align: middle;
        }

        .student-info {
            display: flex;
            flex-direction: column;
        }

        .student-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .student-roll {
            color: #666;
            font-size: 0.85rem;
        }

        .badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--white);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-files {
            background: var(--success);
        }

        .badge-no-files {
            background: #95a5a6;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 0.2rem;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--white);
        }

        .btn-success {
            background: var(--success);
            color: var(--white);
        }

        .btn-warning {
            background: var(--warning);
            color: var(--white);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--white);
            margin: 5% auto;
            padding: 0;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: modalSlideIn 0.4s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.7) translateY(-50px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            background: var(--primary);
            color: var(--white);
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .modal-body {
            padding: 2rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-card {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid;
            border-image: var(--primary) 1;
        }

        .detail-label {
            color: #666;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .detail-value {
            color: var(--dark);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .notes-section {
            background: rgba(255, 193, 7, 0.1);
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            border-left: 4px solid #ffc107;
        }

        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .file-card {
            background: var(--white);
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .file-card:hover {
            border-color: rgba(102, 126, 234, 0.6);
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .file-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: var(--primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .file-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .file-image:hover {
            transform: scale(1.05);
        }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border-left: 4px solid #c33;
        }

        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .header h1 { font-size: 2rem; }
            .modal-content { width: 95%; margin: 10% auto; }
            .detail-grid { grid-template-columns: 1fr; }
            .files-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-truck-fast"></i> Delivery Confirmations</h1>
            <p>Management System</p>
        </div>

        <div class="main-card">
            <div class="card-header">
                <h2><i class="fas fa-clipboard-check"></i> Delivery Confirmations</h2>
            </div>

            <?php
            $total = $result ? $result->num_rows : 0;
            $with_files = 0;
            $with_notes = 0;

            if ($result && $result->num_rows > 0) {
                $result->data_seek(0);
                while ($row = $result->fetch_assoc()) {
                    $files = json_decode($row['proof_files'], true);
                    if ($files && count($files) > 0) $with_files++;
                    if (!empty($row['delivery_notes'])) $with_notes++;
                }
                $result->data_seek(0);
            }
            ?>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?= $total ?></div>
                    <div class="stat-label">Total Confirmations</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $with_files ?></div>
                    <div class="stat-label">With Files</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?= $with_notes ?></div>
                    <div class="stat-label">With Notes</div>
                </div>
            </div>

            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user-graduate"></i> Student</th>
                            <th><i class="fas fa-shopping-bag"></i> Item</th>
                            <th><i class="fas fa-paperclip"></i> Files</th>
                            <th><i class="fas fa-calendar"></i> Date</th>
                            <th><i class="fas fa-tools"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                $files = json_decode($row['proof_files'], true);
                                $file_count = $files ? count($files) : 0;
                                ?>
                                <tr>
                                    <td><strong>#<?= $row['id'] ?></strong></td>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-name"><?= htmlspecialchars($row['student_name']) ?></div>
                                            <div class="student-roll"><?= htmlspecialchars($row['roll_number']) ?></div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars(substr($row['item_name'], 0, 30)) ?><?= strlen($row['item_name']) > 30 ? '...' : '' ?></td>
                                    <td>
                                        <?php if ($file_count > 0): ?>
                                            <span class="badge badge-files"><?= $file_count ?> files</span>
                                        <?php else: ?>
                                            <span class="badge badge-no-files">No files</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($row['submitted_at'])) ?></td>
                                    <td>
                                        <button class="btn btn-primary view-details-btn" data-id="<?= $row['id'] ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if ($file_count > 0 && $files): ?>
                                            <a href="?download=<?= urlencode($files[0]) ?>" class="btn btn-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <h3>No Confirmations Found</h3>
                                        <p>No delivery confirmations have been submitted yet.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Confirmation Details</h3>
                <button class="close-btn" id="closeModal">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading details...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing...');
            
            // Get modal elements
            const modal = document.getElementById('detailModal');
            const modalBody = document.getElementById('modalBody');
            const modalTitle = document.getElementById('modalTitle');
            const closeBtn = document.getElementById('closeModal');
            
            // Add event listeners to all view buttons
            const viewButtons = document.querySelectorAll('.view-details-btn');
            console.log('Found', viewButtons.length, 'view buttons');
            
            viewButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    console.log('View button clicked for ID:', id);
                    viewDetails(id);
                });
            });
            
            // Close modal event listeners
            closeBtn.addEventListener('click', closeModal);
            
            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
            
            // Close modal with escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && modal.classList.contains('show')) {
                    closeModal();
                }
            });
        });
        
        function viewDetails(id) {
            console.log('Loading details for ID:', id);
            
            const modal = document.getElementById('detailModal');
            const modalBody = document.getElementById('modalBody');
            
            // Show modal first
            modal.classList.add('show');
            
            // Show loading state
            modalBody.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading details...</p>
                </div>
            `;
            
            // Make AJAX request
            fetch(window.location.pathname + '?action=get_details&id=' + encodeURIComponent(id))
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.success) {
                        showDetails(data.data, data.files);
                    } else {
                        throw new Error(data.message || 'No data found');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            Error loading details: ${error.message}
                            <br><small>Please try again or contact administrator.</small>
                        </div>
                    `;
                });
        }
        
        function showDetails(data, files) {
            console.log('Displaying details:', data);
            
            let filesHtml = '';
            if (files && files.length > 0) {
                filesHtml = `
                    <div style="margin-top: 2rem;">
                        <h4 style="margin-bottom: 1rem; color: var(--dark);"><i class="fas fa-paperclip" style="margin-right: 0.5rem;"></i>Attached Files</h4>
                        <div class="files-grid">
                `;
                
                files.forEach(file => {
                    const fileName = file.split('/').pop();
                    const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(fileName);
                    
                    filesHtml += `
                        <div class="file-card">
                            ${isImage ? 
                                `<img src="${escapeHtml(file)}" class="file-image" onclick="window.open('${escapeHtml(file)}', '_blank')" alt="${escapeHtml(fileName)}">` :
                                `<i class="fas fa-file file-icon"></i>`
                            }
                            <div style="font-weight: 500; margin-bottom: 1rem; word-break: break-word;">${escapeHtml(fileName)}</div>
                            <a href="?download=${encodeURIComponent(file)}" class="btn btn-success">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    `;
                });
                
                filesHtml += '</div></div>';
            }
            
            document.getElementById('modalTitle').textContent = `Confirmation #${data.id}`;
            document.getElementById('modalBody').innerHTML = `
                <div class="detail-grid">
                    <div class="detail-card">
                        <div class="detail-label">Confirmation ID</div>
                        <div class="detail-value">#${data.id}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Order ID</div>
                        <div class="detail-value">#${data.order_id}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Student Name</div>
                        <div class="detail-value">${escapeHtml(data.student_name)}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Roll Number</div>
                        <div class="detail-value">${escapeHtml(data.roll_number)}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Item</div>
                        <div class="detail-value">${escapeHtml(data.item_name)}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Quantity</div>
                        <div class="detail-value">${data.quantity}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Total Price</div>
                        <div class="detail-value">₹${parseFloat(data.total_price).toFixed(2)}</div>
                    </div>
                    <div class="detail-card">
                        <div class="detail-label">Submitted Date</div>
                        <div class="detail-value">${new Date(data.submitted_at).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}</div>
                    </div>
                </div>
                
                ${data.delivery_notes ? `
                    <div class="notes-section">
                        <h4 style="margin-bottom: 1rem; color: var(--dark);"><i class="fas fa-sticky-note" style="margin-right: 0.5rem;"></i>Delivery Notes</h4>
                        <div style="background: white; padding: 1rem; border-radius: 8px; line-height: 1.6;">
                            ${escapeHtml(data.delivery_notes).replace(/\n/g, '<br>')}
                        </div>
                    </div>
                ` : ''}
                
                ${filesHtml}
            `;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        function closeModal() {
            const modal = document.getElementById('detailModal');
            modal.classList.remove('show');
        }
    </script>
</body>
</html>