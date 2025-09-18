<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$status_msg = "";

// Handle approval/rejection with feedback
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['participation_id'], $_POST['action'])) {
    $participation_id = intval($_POST['participation_id']);
    $action = trim($_POST['action']);
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';

    // Debug: Let's see what we're receiving
    error_log("Debug - Action received: " . $action);
    error_log("Debug - Participation ID: " . $participation_id);
    error_log("Debug - Feedback: " . $feedback);

    // Validate action to ensure only allowed values are processed
    if (in_array($action, ['confirmed', 'rejected', 'feedback'])) {
        
        // Get the event_id for this participation
        $get_event_query = "SELECT event_id, status FROM event_participation WHERE participation_id = $participation_id";
        $event_result = $conn->query($get_event_query);
        
        if ($event_result && $event_result->num_rows > 0) {
            $event_data = $event_result->fetch_assoc();
            $event_id = $event_data['event_id'];
            $current_status = $event_data['status'];
            
            // Handle different actions
            if ($action === 'feedback') {
                // Only update feedback, don't change status
                $update_query = "UPDATE event_participation SET feedback = '" . $conn->real_escape_string($feedback) . "' WHERE participation_id = $participation_id";
                
                if ($conn->query($update_query)) {
                    $status_msg = "✅ Feedback updated successfully.";
                } else {
                    $status_msg = "❌ Failed to update feedback: " . $conn->error;
                }
            } else {
                // For approve/reject actions, explicitly set the status
                if ($action === 'confirmed') {
                    $status_value = 'confirmed';
                } else if ($action === 'rejected') {
                    $status_value = 'rejected';
                } else {
                    $status_value = $action;
                }
                
                // Update both status and feedback for approve/reject actions
                $update_query = "UPDATE event_participation SET status = '" . $conn->real_escape_string($status_value) . "', feedback = '" . $conn->real_escape_string($feedback) . "' WHERE participation_id = $participation_id";
                
                error_log("Debug - Update query: " . $update_query);
                
                if ($conn->query($update_query)) {
                    
                    // Check if the update actually affected any rows
                    if ($conn->affected_rows > 0) {
                        
                        // Only reduce count when approving a pending registration
                        if ($status_value === 'confirmed' && $current_status === 'pending') {
                            $reduce_query = "UPDATE events SET max_participants = max_participants - 1 WHERE event_id = $event_id AND max_participants > 0";
                            
                            if ($conn->query($reduce_query)) {
                                $status_msg = "✅ Registration approved! Available spots reduced by 1.";
                            } else {
                                $status_msg = "✅ Registration approved, but failed to reduce count.";
                            }
                            
                        } elseif ($status_value === 'rejected' && $current_status === 'confirmed') {
                            // Increase count when rejecting a previously confirmed registration
                            $increase_query = "UPDATE events SET max_participants = max_participants + 1 WHERE event_id = $event_id";
                            
                            if ($conn->query($increase_query)) {
                                $status_msg = "✅ Registration rejected! Available spots increased by 1.";
                            } else {
                                $status_msg = "✅ Registration rejected, but failed to increase count.";
                            }
                            
                        } elseif ($status_value === 'rejected') {
                            // Simple rejection (from pending or other status)
                            $status_msg = "✅ Registration rejected successfully.";
                        } else {
                            $status_msg = "✅ Status updated successfully to: " . $status_value;
                        }
                        
                    } else {
                        $status_msg = "❌ No rows were updated. Check if participation ID exists.";
                    }
                    
                } else {
                    $status_msg = "❌ Failed to update status: " . $conn->error;
                    error_log("MySQL Error: " . $conn->error);
                }
            }
            
        } else {
            $status_msg = "❌ Participation record not found.";
        }
        
    } else {
        $status_msg = "❌ Invalid action specified: " . $action;
    }
}

// Modified query to include admin_feedback from event_participation table
$sql = "SELECT 
            ep.participation_id, 
            ep.event_id, 
            e.title as event_title,
            e.max_participants as available_spots,
            e.audition_video,
            sp.name, 
            sp.roll_number, 
            ep.status,
            ep.feedback
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
        
        .btn-video {
            background: #6f42c1;
            color: white;
        }
        
        .btn-feedback {
            background: #17a2b8;
            color: white;
        }
        
        .btn-feedback:hover { background: #138496; }
        
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
        
        /* Video Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-content {
            position: relative;
            margin: 5% auto;
            width: 80%;
            max-width: 800px;
            background: white;
            border-radius: 10px;
            padding: 20px;
        }
        
        .close {
            position: absolute;
            right: 15px;
            top: 10px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .video-container {
            text-align: center;
            margin-top: 20px;
        }
        
        .video-container video {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .video-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        /* Feedback Modal Styles */
        .feedback-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .feedback-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            margin-bottom: 15px;
        }
        
        .feedback-form textarea:focus {
            outline: none;
            border-color: #80bdff;
        }
        
        .feedback-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .existing-feedback {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px 16px;
            margin-top: 8px;
            font-size: 13px;
            border-radius: 4px;
        }
        
        .existing-feedback strong {
            color: #1976d2;
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
                            <th>Video</th>
                            <th>Feedback</th>
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
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($row['audition_video']) && file_exists($row['audition_video'])): ?>
                                        <button type="button" class="btn btn-video" onclick="openVideoModal('<?= htmlspecialchars($row['audition_video']) ?>', '<?= htmlspecialchars($row['event_title']) ?>')">
                                            <i class="fas fa-play"></i> View Video
                                        </button>
                                    <?php else: ?>
                                        <small style="color: #6c757d; font-style: italic;">
                                            No video
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['feedback'])): ?>
                                        <div class="existing-feedback">
                                            <strong>Admin Feedback:</strong><br>
                                            <?= nl2br(htmlspecialchars($row['feedback'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <small style="color: #6c757d; font-style: italic;">
                                            No feedback yet
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <button type="button" class="btn btn-approve" onclick="openFeedbackModal(<?= $row['participation_id'] ?>, '<?= htmlspecialchars($row['name']) ?>', '<?= htmlspecialchars($row['event_title']) ?>', 'confirmed', <?= $spots ?>)">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button type="button" class="btn btn-reject" onclick="openFeedbackModal(<?= $row['participation_id'] ?>, '<?= htmlspecialchars($row['name']) ?>', '<?= htmlspecialchars($row['event_title']) ?>', 'rejected', <?= $spots ?>)">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    <?php else: ?>
                                        <small style="color: #6c757d; font-style: italic;">
                                            Already <?= ucfirst($row['status']) ?>
                                        </small>
                                        <br>
                                        <button type="button" class="btn btn-feedback" onclick="openFeedbackModal(<?= $row['participation_id'] ?>, '<?= htmlspecialchars($row['name']) ?>', '<?= htmlspecialchars($row['event_title']) ?>', 'feedback', <?= $spots ?>)">
                                            <i class="fas fa-comment"></i> Update Feedback
                                        </button>
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

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeFeedbackModal()">&times;</span>
            <div class="video-info">
                <h3><i class="fas fa-comment-alt"></i> Provide Feedback</h3>
                <p><strong>Student:</strong> <span id="modalStudentName"></span></p>
                <p><strong>Event:</strong> <span id="modalEventName"></span></p>
                <p><strong>Action:</strong> <span id="modalAction"></span></p>
            </div>
            <form method="POST" id="feedbackForm" class="feedback-form">
                <input type="hidden" name="participation_id" id="modalParticipationId">
                <input type="hidden" name="action" id="modalActionValue">
                
                <label for="feedbackText" style="display: block; margin-bottom: 8px; font-weight: 600; color: #495057;">
                    <i class="fas fa-pencil-alt"></i> Feedback Message:
                </label>
                <textarea name="feedback" id="feedbackText" placeholder="Enter your feedback for the student..." required></textarea>
                
                <div class="feedback-actions">
                    <button type="button" class="btn" onclick="closeFeedbackModal()" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-approve" id="submitFeedback">
                        <i class="fas fa-paper-plane"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeVideoModal()">&times;</span>
            <div class="video-info">
                <h3><i class="fas fa-video"></i> Audition Video</h3>
                <p><strong>Event:</strong> <span id="modalEventTitle"></span></p>
            </div>
            <div class="video-container">
                <video id="modalVideo" controls width="100%">
                    <p>Your browser doesn't support HTML5 video. <a id="videoDownload" href="#">Download the video</a> instead.</p>
                </video>
            </div>
        </div>
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

        // Feedback Modal Functions
        function openFeedbackModal(participationId, studentName, eventTitle, action, availableSpots) {
            // Check if approving and no spots available
            if (action === 'confirmed' && availableSpots <= 0) {
                alert('Cannot approve - no spots available for this event!');
                return;
            }
            
            const modal = document.getElementById('feedbackModal');
            const modalStudentName = document.getElementById('modalStudentName');
            const modalEventName = document.getElementById('modalEventName');
            const modalAction = document.getElementById('modalAction');
            const modalParticipationId = document.getElementById('modalParticipationId');
            const modalActionValue = document.getElementById('modalActionValue');
            const feedbackText = document.getElementById('feedbackText');
            const submitButton = document.getElementById('submitFeedback');
            
            modalStudentName.textContent = studentName;
            modalEventName.textContent = eventTitle;
            modalParticipationId.value = participationId;
            modalActionValue.value = action;
            
            // Set action text and button style
            if (action === 'confirmed') {
                modalAction.textContent = 'Approve Registration';
                modalAction.style.color = '#28a745';
                submitButton.className = 'btn btn-approve';
                submitButton.innerHTML = '<i class="fas fa-check"></i> Approve & Send Feedback';
                feedbackText.placeholder = 'Congratulations! Your registration has been approved. Additional notes...';
            } else if (action === 'rejected') {
                modalAction.textContent = 'Reject Registration';
                modalAction.style.color = '#dc3545';
                submitButton.className = 'btn btn-reject';
                submitButton.innerHTML = '<i class="fas fa-times"></i> Reject & Send Feedback';
                feedbackText.placeholder = 'Sorry, your registration has been rejected. Reason...';
            } else {
                modalAction.textContent = 'Update Feedback';
                modalAction.style.color = '#17a2b8';
                submitButton.className = 'btn btn-feedback';
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Update Feedback';
                feedbackText.placeholder = 'Update feedback message...';
            }
            
            feedbackText.value = '';
            modal.style.display = 'block';
            feedbackText.focus();
        }

        function closeFeedbackModal() {
            const modal = document.getElementById('feedbackModal');
            modal.style.display = 'none';
        }
        
        function openVideoModal(videoPath, eventTitle) {
            const modal = document.getElementById('videoModal');
            const modalVideo = document.getElementById('modalVideo');
            const modalEventTitle = document.getElementById('modalEventTitle');
            const videoDownload = document.getElementById('videoDownload');
            
            modalEventTitle.textContent = eventTitle;
            modalVideo.src = videoPath;
            videoDownload.href = videoPath;
            
            modal.style.display = 'block';
        }

        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const modalVideo = document.getElementById('modalVideo');
            
            modal.style.display = 'none';
            modalVideo.pause();
            modalVideo.src = '';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const videoModal = document.getElementById('videoModal');
            const feedbackModal = document.getElementById('feedbackModal');
            if (event.target === videoModal) {
                closeVideoModal();
            } else if (event.target === feedbackModal) {
                closeFeedbackModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeVideoModal();
                closeFeedbackModal();
            }
        });
    </script>
</body>
</html>