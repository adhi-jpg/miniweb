<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$status_msg = "";

// Handle approval/rejection - SIMPLIFIED VERSION
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['participation_id'], $_POST['action'])) {
    $participation_id = intval($_POST['participation_id']);
    $action = $_POST['action'];

    if (in_array($action, ['confirmed', 'rejected'])) {
        
        // Get the event_id for this participation
        $get_event_query = "SELECT event_id, status FROM event_participation WHERE participation_id = $participation_id";
        $event_result = $conn->query($get_event_query);
        
        if ($event_result && $event_result->num_rows > 0) {
            $event_data = $event_result->fetch_assoc();
            $event_id = $event_data['event_id'];
            $current_status = $event_data['status'];
            
            // Update the participation status
            $update_query = "UPDATE event_participation SET status = '$action' WHERE participation_id = $participation_id";
            
            if ($conn->query($update_query)) {
                
                // Only reduce count when approving a pending registration
                if ($action === 'confirmed' && $current_status === 'pending') {
                    $reduce_query = "UPDATE events SET max_participants = max_participants - 1 WHERE event_id = $event_id AND max_participants > 0";
                    
                    if ($conn->query($reduce_query)) {
                        $status_msg = "✅ Registration approved! Available spots reduced by 1.";
                    } else {
                        $status_msg = "✅ Registration approved, but failed to reduce count.";
                    }
                    
                } elseif ($action === 'rejected' && $current_status === 'confirmed') {
                    // Increase count when rejecting a previously confirmed registration
                    $increase_query = "UPDATE events SET max_participants = max_participants + 1 WHERE event_id = $event_id";
                    
                    if ($conn->query($increase_query)) {
                        $status_msg = "✅ Registration rejected! Available spots increased by 1.";
                    } else {
                        $status_msg = "✅ Registration rejected, but failed to increase count.";
                    }
                    
                } else {
                    $status_msg = "✅ Status updated successfully.";
                }
                
            } else {
                $status_msg = "❌ Failed to update status: " . $conn->error;
            }
            
        } else {
            $status_msg = "❌ Participation record not found.";
        }
    }
}

// Simple query to get all registrations
$sql = "SELECT 
            ep.participation_id, 
            ep.event_id, 
            e.title as event_title,
            e.max_participants as available_spots,
            sp.name, 
            sp.roll_number, 
            ep.status
        FROM event_participation ep
        JOIN student_profiles sp ON ep.user_id = sp.user_id
        JOIN events e ON ep.event_id = e.event_id
        ORDER BY ep.event_id, ep.participation_id";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Registrations – Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2rem;
        }
        
        .status-msg {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        .status-msg.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-msg.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 700;
            color: #495057;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            margin: 2px;
            transition: all 0.2s;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover { background: #218838; }
        .btn-approve:disabled { background: #6c757d; cursor: not-allowed; }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover { background: #c82333; }
        
        .event-info {
            font-size: 14px;
            color: #6c757d;
        }
        
        .event-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 4px;
        }
        
        .spots-info {
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .spots-low { background: #fff3cd; color: #856404; }
        .spots-none { background: #f8d7da; color: #721c24; }
        
        .no-records {
            text-align: center;
            padding: 50px;
            color: #6c757d;
        }
        
        /* Loading spinner for buttons */
        .loading {
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-clipboard-list"></i> Event Registration Management</h1>
        
        <?php if ($status_msg): ?>
            <div class="status-msg <?= str_contains($status_msg, '✅') ? 'success' : 'error' ?>">
                <?= htmlspecialchars($status_msg) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Details</th>
                            <th>Student</th>
                            <th>Roll No</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 
                            $spots = intval($row['available_spots']);
                            $spots_class = '';
                            if ($spots <= 0) $spots_class = 'spots-none';
                            elseif ($spots <= 5) $spots_class = 'spots-low';
                        ?>
                            <tr>
                                <td><strong><?= $row['participation_id'] ?></strong></td>
                                <td>
                                    <div class="event-title"><?= htmlspecialchars($row['event_title']) ?></div>
                                    <div class="spots-info <?= $spots_class ?>">
                                        <?= $spots ?> spots remaining
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['roll_number']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="participation_id" value="<?= $row['participation_id'] ?>">
                                            <button type="submit" name="action" value="confirmed" 
                                                    class="btn btn-approve" 
                                                    <?= ($spots <= 0) ? 'disabled' : '' ?>
                                                    onclick="return confirm('Approve registration for <?= htmlspecialchars($row['name']) ?>?\\n\\nThis will reduce available spots by 1.')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="rejected" 
                                                    class="btn btn-reject"
                                                    onclick="return confirm('Reject registration for <?= htmlspecialchars($row['name']) ?>?')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small style="color: #6c757d; font-style: italic;">
                                            Already <?= $row['status'] ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-records">
                <i class="fas fa-inbox fa-3x" style="margin-bottom: 20px; opacity: 0.3;"></i>
                <h3>No Registrations Found</h3>
                <p>There are no event registrations to display.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add loading state to buttons when clicked
        document.querySelectorAll('button[type="submit"]').forEach(function(button) {
            button.addEventListener('click', function(e) {
                if (this.onclick && this.onclick(e) === false) {
                    return;
                }
                
                setTimeout(() => {
                    this.classList.add('loading');
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                }, 100);
            });
        });
    </script>
</body>
</html>