<?php
session_start();
include 'config.php';
// Ensure only admin can access
if ($_SESSION['role'] != 'admin') {
    die("Access Denied!");
}
$admin_id = $_SESSION['user_id'];
// Handle submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $faculty_id = $_POST['faculty_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $proposed_date = $_POST['proposed_date'];
    $venue = $_POST['venue'];
    $stmt = $conn->prepare("INSERT INTO event_proposals (admin_id, faculty_id, title, description, proposed_date, venue, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iissss", $admin_id, $faculty_id, $title, $description, $proposed_date, $venue);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Proposal submitted successfully!</div>";
    } else {
        echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}
// Get all faculty for dropdown
$faculty = $conn->query("SELECT f.user_id, f.name FROM faculty_profiles f");
// Get previous submissions
$previous = $conn->query("
    SELECT ep.proposal_id, ep.title, ep.description, ep.proposed_date, ep.venue, ep.status, ep.feedback, f.name AS faculty_name
    FROM event_proposals ep
    JOIN faculty_profiles f ON ep.faculty_id = f.user_id
    WHERE ep.admin_id = $admin_id
    ORDER BY ep.proposal_id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Proposal Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            color: white;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .card-title {
            color: #5a67d8;
            font-size: 1.8rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #5a67d8;
            background: white;
            box-shadow: 0 0 0 3px rgba(90, 103, 216, 0.1);
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 auto;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideIn 0.5s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.3s ease;
        }

        tr:hover td {
            background-color: #f8fafc;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #718096;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .card {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 10px 8px;
            }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .required {
            color: #e53e3e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-plus"></i> Event Proposal System</h1>
            <p>Submit and manage your event proposals efficiently</p>
        </div>

        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-plus-circle"></i>
                Submit New Event Proposal
            </h2>
            
            <form method="POST" id="proposalForm">
                <div class="form-group">
                    <label for="faculty_id">
                        <i class="fas fa-user-tie"></i> Faculty Reviewer <span class="required">*</span>
                    </label>
                    <select name="faculty_id" id="faculty_id" required>
                        <option value="">-- Select Faculty Member --</option>
                        <?php while ($f = $faculty->fetch_assoc()): ?>
                            <option value="<?= $f['user_id'] ?>"><?= htmlspecialchars($f['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">
                        <i class="fas fa-heading"></i> Event Title <span class="required">*</span>
                    </label>
                    <input type="text" name="title" id="title" required placeholder="Enter the event title">
                </div>

                <div class="form-group">
                    <label for="description">
                        <i class="fas fa-align-left"></i> Event Description <span class="required">*</span>
                    </label>
                    <textarea name="description" id="description" required placeholder="Provide a detailed description of the event..."></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="proposed_date">
                            <i class="fas fa-calendar-alt"></i> Proposed Date <span class="required">*</span>
                        </label>
                        <input type="date" name="proposed_date" id="proposed_date" required>
                    </div>

                    <div class="form-group">
                        <label for="venue">
                            <i class="fas fa-map-marker-alt"></i> Venue <span class="required">*</span>
                        </label>
                        <input type="text" name="venue" id="venue" required placeholder="Enter the venue location">
                    </div>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    Submit Proposal
                </button>
            </form>
        </div>

        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-history"></i>
                Your Previous Proposals
            </h2>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-heading"></i> Title</th>
                            <th><i class="fas fa-align-left"></i> Description</th>
                            <th><i class="fas fa-calendar"></i> Date</th>
                            <th><i class="fas fa-map-marker-alt"></i> Venue</th>
                            <th><i class="fas fa-user-tie"></i> Faculty Reviewer</th>
                            <th><i class="fas fa-flag"></i> Status</th>
                            <th><i class="fas fa-comment"></i> Feedback</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($previous->num_rows > 0): ?>
                            <?php while ($row = $previous->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>
                                    <td><?= htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : '') ?></td>
                                    <td>
                                        <i class="fas fa-calendar-day"></i>
                                        <?= date('M d, Y', strtotime($row['proposed_date'])) ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-location-arrow"></i>
                                        <?= htmlspecialchars($row['venue']) ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-user"></i>
                                        <?= htmlspecialchars($row['faculty_name']) ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?= $row['status'] ?>">
                                            <?php
                                            $icons = ['pending' => 'clock', 'approved' => 'check', 'rejected' => 'times'];
                                            echo '<i class="fas fa-' . $icons[$row['status']] . '"></i> ';
                                            echo ucfirst($row['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['feedback'])): ?>
                                            <?= htmlspecialchars(substr($row['feedback'], 0, 100)) . (strlen($row['feedback']) > 100 ? '...' : '') ?>
                                        <?php else: ?>
                                            <span style="color: #718096; font-style: italic;">No feedback yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <div>No proposals submitted yet.</div>
                                    <small>Your submitted proposals will appear here.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Set minimum date to today
        document.getElementById('proposed_date').min = new Date().toISOString().split('T')[0];
        
        // Form validation
        document.getElementById('proposalForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('input[required], select[required], textarea[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#e53e3e';
                    isValid = false;
                } else {
                    field.style.borderColor = '#e2e8f0';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });

        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>
</html>