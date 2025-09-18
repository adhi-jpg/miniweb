<?php
session_start();
include "config.php";

// Ensure only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$status_msg = "";
$error_msg = "";

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $event_id = intval($_POST['event_id']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    
    // Check if statuses array exists
    if (!isset($_POST['status']) || empty($_POST['status'])) {
        $error_msg = "❌ No attendance data received. Please mark at least one student's attendance.";
    } else {
        $statuses = $_POST['status'];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($statuses as $user_id => $status) {
            // Skip empty status values
            if (empty($status)) {
                continue;
            }
            
            $user_id = intval($user_id);
            $status_clean = mysqli_real_escape_string($conn, $status);
            
            // Validate status values
            if (!in_array($status_clean, ['present', 'absent'])) {
                continue;
            }

            // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both insert and update
            $upsert_query = "INSERT INTO attendance (event_id, user_id, status, date) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE status = VALUES(status)";
            
            $stmt = mysqli_prepare($conn, $upsert_query);
            mysqli_stmt_bind_param($stmt, "iiss", $event_id, $user_id, $status_clean, $date);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_count++;
            } else {
                $error_count++;
            }
            mysqli_stmt_close($stmt);
        }
        
        if ($success_count > 0) {
            $status_msg = "✅ Successfully saved attendance for $success_count student(s)!";
            if ($error_count > 0) {
                $status_msg .= " ($error_count failed)";
            }
        } else {
            $error_msg = "❌ No attendance records were saved. Please try again.";
        }
    }
}

// Fetch events for dropdown
$events = $conn->query("SELECT event_id, title FROM events ORDER BY date DESC");

// Handle event and date selection
$selected_event = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
$selected_date = isset($_POST['date']) ? $_POST['date'] : date('Y-m-d');

$students = null;
if ($selected_event) {
    // Modified query to only load students with 'confirmed' status
    $students_query = "SELECT u.user_id, s.name, s.roll_number
                      FROM event_participation ep
                      JOIN users u ON ep.user_id = u.user_id
                      JOIN student_profiles s ON u.user_id = s.user_id
                      WHERE ep.event_id = ? AND ep.status = 'confirmed'
                      ORDER BY s.roll_number";
    
    $stmt = mysqli_prepare($conn, $students_query);
    mysqli_stmt_bind_param($stmt, "i", $selected_event);
    mysqli_stmt_execute($stmt);
    $students = mysqli_stmt_get_result($stmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            line-height: 1.6; 
        }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .header p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }
        
        .card {
            background: white; 
            border-radius: 12px; 
            padding: 2rem; 
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
        }
        
        .status-msg {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white; 
            padding: 1rem 1.5rem; 
            border-radius: 8px; 
            margin-bottom: 2rem;
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }
        
        .error-msg {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white; 
            padding: 1rem 1.5rem; 
            border-radius: 8px; 
            margin-bottom: 2rem;
            display: flex; 
            align-items: center; 
            gap: 0.75rem; 
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
        }
        
        .form-grid {
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem; 
            align-items: end;
        }
        
        .form-group { 
            display: flex; 
            flex-direction: column; 
        }
        
        .form-group label {
            font-weight: 600; 
            color: #2c3e50; 
            margin-bottom: 0.5rem;
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            font-size: 0.9rem;
        }
        
        .form-group select, .form-group input {
            padding: 0.875rem 1rem; 
            border: 2px solid #e1e8ed; 
            border-radius: 8px;
            font-size: 1rem; 
            background: white;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 0.875rem 2rem; 
            border: none; 
            border-radius: 8px; 
            font-size: 1rem;
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s ease;
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #45a049); 
            color: white;
            padding: 1rem 3rem; 
            font-size: 1.1rem; 
            margin-top: 2rem;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%; 
            border-collapse: collapse; 
            background: white;
        }
        
        th, td {
            padding: 1rem; 
            text-align: center; 
            border-bottom: 1px solid #f0f0f0;
        }
        
        th {
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
        }
        
        tr:hover {
            background-color: #f8f9ff;
        }
        
        .status-select {
            padding: 0.5rem 1rem; 
            border: 2px solid #e1e8ed; 
            border-radius: 6px;
            font-weight: 600; 
            background: white; 
            min-width: 120px;
            transition: border-color 0.3s ease;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .status-present { color: #4CAF50; font-weight: bold; }
        .status-absent { color: #f44336; font-weight: bold; }
        .status-not-set { color: #9e9e9e; font-style: italic; }
        
        h2 { 
            color: #2c3e50; 
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-box {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border-left: 4px solid #2196f3;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 0 8px 8px 0;
        }
        
        .info-box p {
            color: #1565c0;
            margin: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-check"></i> Attendance Management</h1>
            <p>Manage student attendance for events</p>
        </div>
        
        <?php if ($status_msg): ?>
            <div class="status-msg">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($status_msg) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <!-- Event & Date Selection -->
        <div class="card">
            <h2><i class="fas fa-calendar-alt"></i> Select Event & Date</h2>
            <div class="info-box">
                <p>Choose an event and date to manage attendance for registered students.</p>
            </div>
            
            <form method="POST" class="form-grid">
                <div class="form-group">
                    <label for="event_id"><i class="fas fa-calendar"></i> Select Event</label>
                    <select name="event_id" id="event_id" required>
                        <option value="">-- Choose Event --</option>
                        <?php if ($events): ?>
                            <?php while ($row = $events->fetch_assoc()): ?>
                                <option value="<?= $row['event_id'] ?>" <?= $row['event_id'] == $selected_event ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['title']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date"><i class="fas fa-calendar-day"></i> Date</label>
                    <input type="date" name="date" id="date" value="<?= htmlspecialchars($selected_date) ?>" required>
                </div>

                <div class="form-group">
                    <button type="submit" name="load_students" class="btn btn-primary">
                        <i class="fas fa-search"></i> Load Students
                    </button>
                </div>
            </form>
        </div>

        <?php if ($selected_event && $students && $students->num_rows > 0): ?>
            <div class="card">
                <h2><i class="fas fa-users"></i> Mark Attendance</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $students->num_rows ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="presentCount">0</div>
                        <div class="stat-label">Present</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="absentCount">0</div>
                        <div class="stat-label">Absent</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="unmarkedCount"><?= $students->num_rows ?></div>
                        <div class="stat-label">Unmarked</div>
                    </div>
                </div>

                <form method="POST" id="attendanceForm">
                    <input type="hidden" name="event_id" value="<?= $selected_event ?>">
                    <input type="hidden" name="date" value="<?= htmlspecialchars($selected_date) ?>">

                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> Roll Number</th>
                                    <th><i class="fas fa-user"></i> Student Name</th>
                                    <th><i class="fas fa-clipboard-list"></i> Mark Attendance</th>
                                    <th><i class="fas fa-info-circle"></i> Current Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $students->data_seek(0);
                                while ($row = $students->fetch_assoc()): 
                                    $user_id = $row['user_id'];
                                    
                                    // Check current attendance
                                    $check_query = "SELECT status FROM attendance WHERE event_id = ? AND user_id = ? AND date = ?";
                                    $check_stmt = mysqli_prepare($conn, $check_query);
                                    mysqli_stmt_bind_param($check_stmt, "iis", $selected_event, $user_id, $selected_date);
                                    mysqli_stmt_execute($check_stmt);
                                    $check_result = mysqli_stmt_get_result($check_stmt);
                                    
                                    $existing_status = 'not-set';
                                    $existing_display = 'Not Set';
                                    
                                    if ($check_result && $check_result->num_rows > 0) {
                                        $status_row = $check_result->fetch_assoc();
                                        $existing_status = $status_row['status'];
                                        $existing_display = ucfirst($status_row['status']);
                                    }
                                    
                                    mysqli_stmt_close($check_stmt);
                                ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['roll_number']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['name']) ?></td>
                                        <td>
                                            <select name="status[<?= $row['user_id'] ?>]" class="status-select" onchange="updateStats()">
                                                <option value="">-- Select --</option>
                                                <option value="present" <?= $existing_status === 'present' ? 'selected' : '' ?>>Present</option>
                                                <option value="absent" <?= $existing_status === 'absent' ? 'selected' : '' ?>>Absent</option>
                                            </select>
                                        </td>
                                        <td>
                                            <span class="status-<?= $existing_status ?>">
                                                <?= htmlspecialchars($existing_display) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="text-align: center;">
                        <button type="submit" name="save_attendance" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                    </div>
                </form>
            </div>
            
        <?php elseif ($selected_event): ?>
            <div class="card">
                <h2><i class="fas fa-exclamation-circle"></i> No Students Found</h2>
                <div class="info-box">
                    <p>No students are registered for this event. Please check if students have been enrolled in the selected event.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateStats() {
            const selects = document.querySelectorAll('.status-select');
            let presentCount = 0;
            let absentCount = 0;
            let unmarkedCount = 0;
            
            selects.forEach(select => {
                switch(select.value) {
                    case 'present':
                        presentCount++;
                        break;
                    case 'absent':
                        absentCount++;
                        break;
                    default:
                        unmarkedCount++;
                }
            });
            
            document.getElementById('presentCount').textContent = presentCount;
            document.getElementById('absentCount').textContent = absentCount;
            document.getElementById('unmarkedCount').textContent = unmarkedCount;
        }
        
        // Form validation
        document.getElementById('attendanceForm')?.addEventListener('submit', function(e) {
            const selects = document.querySelectorAll('.status-select');
            const selectedCount = Array.from(selects).filter(s => s.value !== '').length;
            
            if (selectedCount === 0) {
                alert('Please mark attendance for at least one student before saving!');
                e.preventDefault();
                return false;
            }
            
            return confirm(`Are you sure you want to save attendance for ${selectedCount} student(s)?`);
        });
        
        // Initialize stats on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateStats();
        });
    </script>
</body>
</html>