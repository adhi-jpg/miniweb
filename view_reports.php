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
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 50%, #0f0f0f 100%);
            min-height: 100vh; padding: 20px; color: #fff;
            position: relative; overflow-x: hidden;
        }

        body::before {
            content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 30% 70%, rgba(255, 215, 0, 0.05) 0%, transparent 60%),
                        radial-gradient(circle at 70% 30%, rgba(255, 223, 87, 0.03) 0%, transparent 60%);
            animation: glow 8s ease-in-out infinite alternate; pointer-events: none; z-index: -1;
        }

        @keyframes glow {
            0% { opacity: 0.3; transform: scale(1); }
            100% { opacity: 0.6; transform: scale(1.1); }
        }

        .container {
            max-width: 1400px; margin: 0 auto;
            background: rgba(26, 26, 26, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.7), 0 0 100px rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        h1 {
            text-align: center; margin-bottom: 30px; font-size: 2.5rem; font-weight: 600;
            background: linear-gradient(45deg, #ffd700, #ffdf57, #ffd23f);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; text-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
        }

        .status {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.08));
            border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e;
            padding: 15px 20px; margin-bottom: 20px; border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .status.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.08));
            border-color: rgba(239, 68, 68, 0.3); color: #ef4444;
        }

        .table-container {
            overflow-x: auto; border-radius: 15px;
            background: rgba(15, 15, 15, 0.9); border: 1px solid rgba(255, 215, 0, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        table { width: 100%; border-collapse: collapse; }

        th {
            background: linear-gradient(135deg, #1a1a1a, #2a2a2a);
            color: #ffd700; padding: 18px 15px; text-align: left; font-weight: 600;
            position: sticky; top: 0; z-index: 10;
            border-bottom: 2px solid rgba(255, 215, 0, 0.3);
        }

        td { 
            padding: 15px; border-bottom: 1px solid rgba(255, 215, 0, 0.1); 
            vertical-align: top; 
        }

        tr { transition: all 0.3s ease; }

        tbody tr:hover {
            background: rgba(255, 215, 0, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .status-badge {
            padding: 6px 12px; border-radius: 20px; font-size: 12px;
            font-weight: 600; text-transform: uppercase;
        }

        .status-pending { background: rgba(255, 215, 0, 0.2); color: #ffd700; }
        .status-reviewed { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
        .status-rejected { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

        .download-btn {
            background: linear-gradient(135deg, #ffd700, #ffdf57);
            color: #000; padding: 8px 16px; border: none; border-radius: 20px;
            text-decoration: none; font-size: 12px; font-weight: 500;
            transition: all 0.3s ease; display: inline-block;
        }

        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.4);
            color: #000; text-decoration: none;
        }

        textarea {
            width: 100%; padding: 12px; 
            background: rgba(15, 15, 15, 0.8); color: #fff;
            border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px;
            font-family: inherit; resize: vertical; min-height: 80px;
        }

        textarea:focus {
            outline: none; border-color: #ffd700;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }

        select {
            width: 100%; padding: 10px; margin-top: 10px;
            background: rgba(15, 15, 15, 0.8); color: #fff;
            border: 1px solid rgba(255, 215, 0, 0.3); border-radius: 8px;
        }

        select:focus { outline: none; border-color: #ffd700; }

        .action-btn {
            background: linear-gradient(135deg, #ffd700, #ffdf57);
            color: #000; padding: 12px 20px; border: none; border-radius: 8px;
            cursor: pointer; font-weight: 600; transition: all 0.3s ease;
            width: 100%; margin-top: 10px;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.4);
        }

        .action-btn:disabled {
            background: #666; cursor: not-allowed;
            transform: none; box-shadow: none;
        }

        .no-reports {
            text-align: center; padding: 60px 20px; color: #999;
        }

        .no-reports-icon {
            font-size: 4rem; margin-bottom: 20px; opacity: 0.5;
        }

        @media (max-width: 768px) {
            .container { padding: 15px; margin: 10px; }
            h1 { font-size: 2rem; }
            th, td { padding: 10px 8px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Faculty – Review Reports</h1>

        <?php if (isset($status_msg) && $status_msg): ?>
            <div class="status <?= str_contains($status_msg, '❌') ? 'error' : '' ?>">
                <?= htmlspecialchars($status_msg) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($result) && $result->num_rows > 0): ?>
            <div class="table-container">
                <table>
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
                            <tr>
                                <td><strong>#<?= $row['id'] ?></strong></td>
                                <td><?= htmlspecialchars($row['submitted_by']) ?></td>
                                <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                <td>
                                    <?= strlen($row['description']) > 100 ? 
                                        htmlspecialchars(substr($row['description'], 0, 100)) . '...' : 
                                        htmlspecialchars($row['description']) ?>
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
                                    <?= $row['feedback'] ? 
                                        (strlen($row['feedback']) > 50 ? 
                                            htmlspecialchars(substr($row['feedback'], 0, 50)) . '...' : 
                                            htmlspecialchars($row['feedback'])) : 
                                        '<em style="color: #999;">No feedback yet</em>' ?>
                                </td>
                                <td>
                                    <form method="post" class="review-form">
                                        <input type="hidden" name="report_id" value="<?= $row['id'] ?>">
                                        <textarea 
                                            name="feedback" 
                                            placeholder="Enter feedback..."
                                            required
                                        ><?= htmlspecialchars($row['feedback']) ?></textarea>
                                        <select name="action" required>
                                            <option value="">Select Action</option>
                                            <option value="reviewed" <?= ($row['status'] ?? '') === 'reviewed' ? 'selected' : '' ?>>
                                                ✅ Approve
                                            </option>
                                            <option value="rejected" <?= ($row['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>
                                                ❌ Reject
                                            </option>
                                        </select>
                                        <button type="submit" class="action-btn">
                                            <?= ($row['status'] ?? 'pending') === 'pending' ? '💾 Submit Review' : '🔄 Update Review' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-reports">
                <div class="no-reports-icon">📋</div>
                <p>No submitted reports available.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.review-form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = form.querySelector('select[name="action"]').value;
                    const feedback = form.querySelector('textarea[name="feedback"]').value.trim();
                    
                    if (!action) {
                        alert('Please select an action (Approve/Reject)');
                        e.preventDefault();
                        return;
                    }
                    
                    if (!feedback) {
                        alert('Please provide feedback before submitting');
                        e.preventDefault();
                        return;
                    }
                    
                    const actionText = action === 'reviewed' ? 'approve' : 'reject';
                    if (!confirm(`Are you sure you want to ${actionText} this report?`)) {
                        e.preventDefault();
                        return;
                    }
                    
                    const submitBtn = form.querySelector('.action-btn');
                    submitBtn.disabled = true;
                    submitBtn.textContent = '⏳ Processing...';
                });
            });
        });
    </script>
</body>
</html>