<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query with filter - added feedback column
$query = "
    SELECT ep.participation_id, ep.status, ep.feedback, 
           e.title AS event_title, e.date
    FROM event_participation ep
    JOIN events e ON ep.event_id = e.event_id
    WHERE ep.user_id = ?";
    
if ($status_filter !== 'all') {
    $query .= " AND ep.status = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $user_id, $status_filter);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participation History – Student</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        header { 
            background: linear-gradient(135deg, #4a00e0 0%, #8e2de2 50%, #ff6b6b 100%);
            padding: 25px; 
            color: white; 
            font-size: 26px; 
            text-align: center; 
            font-weight: 700;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 30px; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 25px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        th, td { 
            padding: 18px 15px; 
            text-align: center; 
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
        }
        
        th { 
            background: linear-gradient(135deg, #4a00e0 0%, #8e2de2 100%);
            color: white; 
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        tr { 
            transition: all 0.3s ease;
        }
        
        tr:nth-child(even) { 
            background: rgba(116, 75, 162, 0.02);
        }
        
        tr:hover { 
            background: rgba(116, 75, 162, 0.08);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .status { 
            font-weight: 600; 
            padding: 8px 16px; 
            border-radius: 25px; 
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .approved { 
            background: linear-gradient(135deg, #4caf50, #66bb6a);
            color: white;
        }
        
        .rejected { 
            background: linear-gradient(135deg, #f44336, #ef5350);
            color: white;
        }
        
        .pending { 
            background: linear-gradient(135deg, #ff9800, #ffb74d);
            color: white;
        }
        
        .feedback-cell {
            max-width: 350px;
            text-align: left;
            padding: 15px !important;
        }
        
        .feedback-content {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #4a00e0;
            font-size: 14px;
            line-height: 1.6;
            margin: 8px 0;
            box-shadow: 0 4px 12px rgba(74, 0, 224, 0.1);
            position: relative;
        }
        
        .feedback-content::before {
            content: '"';
            position: absolute;
            top: 5px;
            left: 8px;
            font-size: 24px;
            color: #4a00e0;
            opacity: 0.3;
        }
        
        .no-feedback {
            color: #999;
            font-style: italic;
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            padding: 15px;
            background: rgba(0,0,0,0.02);
            border-radius: 8px;
        }
        
        .view-feedback-btn {
            background: linear-gradient(135deg, #4a00e0 0%, #8e2de2 100%);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 0, 224, 0.3);
        }
        
        .view-feedback-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 0, 224, 0.4);
            background: linear-gradient(135deg, #3700b3 0%, #7b1fa2 100%);
        }
        
        .no-data { 
            text-align: center; 
            padding: 50px 20px; 
            color: #666; 
            font-size: 18px;
            background: rgba(0,0,0,0.02);
            border-radius: 15px;
            margin-top: 20px;
        }
        
        .filters, .export-buttons { 
            margin: 15px 0; 
            display: flex; 
            justify-content: space-between; 
            flex-wrap: wrap;
            gap: 15px;
        }
        
        select, button { 
            padding: 12px 20px; 
            border: none; 
            border-radius: 25px; 
            font-weight: 600; 
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        select { 
            background: rgba(255,255,255,0.9);
            border: 2px solid rgba(74, 0, 224, 0.2);
            color: #4a00e0;
        }
        
        select:focus {
            outline: none;
            border-color: #4a00e0;
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
        }
        
        button { 
            background: linear-gradient(135deg, #4a00e0 0%, #8e2de2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(74, 0, 224, 0.3);
        }
        
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 0, 224, 0.4);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 80px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease;
            position: relative;
        }
        
        .modal h3 {
            color: #4a00e0;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 700;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f2ff;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            color: #999;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .close:hover { 
            color: #4a00e0;
            background: rgba(74, 0, 224, 0.1);
            transform: rotate(90deg);
        }
        
        #modalFeedbackContent {
            background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
            padding: 20px;
            border-radius: 15px;
            border-left: 5px solid #4a00e0;
            font-size: 16px;
            line-height: 1.7;
            color: #333;
            margin-top: 15px;
            box-shadow: 0 5px 20px rgba(74, 0, 224, 0.1);
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { 
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .container { 
                margin: 10px; 
                padding: 20px;
                border-radius: 15px;
            }
            table { font-size: 13px; }
            th, td { padding: 12px 8px; }
            .filters { 
                flex-direction: column; 
                gap: 15px;
                align-items: stretch;
            }
            .export-buttons { 
                justify-content: center;
                gap: 10px;
            }
            .modal-content {
                margin: 10% auto;
                padding: 20px;
                width: 95%;
            }
            .feedback-cell {
                max-width: 250px;
            }
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #4a00e0, #8e2de2);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #3700b3, #7b1fa2);
        }
    </style>
</head>
<body>
<header>
    <i class="fas fa-history"></i> My Participation History
</header>
<div class="container">

    <!-- Filter Dropdown -->
    <div class="filters">
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <label for="status"><b>Filter by Status:</b></label>
            <select name="status" id="status" onchange="this.form.submit()">
                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
        </form>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <button onclick="exportPDF()"><i class="fas fa-file-pdf"></i> Export PDF</button>
            <button onclick="exportCSV()"><i class="fas fa-file-excel"></i> Export CSV</button>
        </div>
    </div>

    <?php if($result->num_rows > 0): ?>
    <table id="historyTable">
        <tr>
            <th>Participation ID</th>
            <th>Event Title</th>
            <th>Event Date</th>
            <th>Status</th>
            <th>Admin Feedback</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['participation_id']) ?></td>
            <td><?= htmlspecialchars($row['event_title']) ?></td>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td>
                <span class="status 
                    <?= strtolower($row['status']) == 'approved' ? 'approved' :
                       (strtolower($row['status']) == 'rejected' ? 'rejected' : 'pending') ?>">
                    <i class="fas <?= strtolower($row['status']) == 'approved' ? 'fa-check-circle' :
                                   (strtolower($row['status']) == 'rejected' ? 'fa-times-circle' : 'fa-clock') ?>"></i>
                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                </span>
            </td>
            <td class="feedback-cell">
                <?php if (!empty($row['feedback'])): ?>
                    <?php if (strlen($row['feedback']) > 50): ?>
                        <div class="feedback-content">
                            <?= htmlspecialchars(substr($row['feedback'], 0, 50)) ?>...
                        </div>
                        <button class="view-feedback-btn" onclick="showFeedback(`<?= htmlspecialchars($row['feedback'], ENT_QUOTES) ?>`, `<?= htmlspecialchars($row['event_title'], ENT_QUOTES) ?>`)">
                            <i class="fas fa-eye"></i> View Full Feedback
                        </button>
                    <?php else: ?>
                        <div class="feedback-content">
                            <?= htmlspecialchars($row['feedback']) ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="no-feedback">
                        <i class="fas fa-minus-circle"></i> No feedback available
                    </span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <div class="no-data">
            <i class="fas fa-info-circle"></i> No participation records found.
            <?php if ($status_filter !== 'all'): ?>
            <a href="?status=all" style="color: #4a00e0; text-decoration: none;">View all records</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal for feedback -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeFeedbackModal()">&times;</span>
        <h3 id="modalEventTitle">Admin Feedback</h3>
        <div id="modalFeedbackContent" style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #4a00e0; margin-top: 10px;"></div>
    </div>
</div>

<script>
    function exportPDF() {
        const printWindow = window.open('', '', 'width=900,height=650');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Participation History</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .status { font-weight: bold; }
                    </style>
                </head>
                <body>
                    <h2 style="text-align:center;">My Participation History</h2>
                    ${document.getElementById("historyTable").outerHTML}
                    <p style="text-align:center; margin-top:20px; font-size:12px;">
                        Generated on ${new Date().toLocaleDateString()}
                    </p>
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    function exportCSV() {
        let table = document.getElementById("historyTable");
        let rows = table.querySelectorAll("tr");
        let csv = [];
        
        for (let i = 0; i < rows.length; i++) {
            let cols = rows[i].querySelectorAll("th,td");
            let rowData = [];
            cols.forEach(col => {
                // Clean up text content and remove HTML
                let text = col.innerText || col.textContent || '';
                text = text.replace(/\s+/g, ' ').trim();
                rowData.push('"' + text.replace(/"/g, '""') + '"');
            });
            csv.push(rowData.join(","));
        }
        
        let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        let downloadLink = document.createElement("a");
        downloadLink.download = "participation_history_" + new Date().toISOString().split('T')[0] + ".csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.click();
    }

    function showFeedback(feedback, eventTitle) {
        document.getElementById('modalEventTitle').textContent = 'Feedback for: ' + eventTitle;
        document.getElementById('modalFeedbackContent').textContent = feedback;
        document.getElementById('feedbackModal').style.display = 'block';
    }

    function closeFeedbackModal() {
        document.getElementById('feedbackModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        let modal = document.getElementById('feedbackModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>
</body>
</html>