<?php
session_start();
include "config.php";

// Only allow students
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_event = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;

// Fetch student details
$student = $conn->query("SELECT s.name, s.roll_number FROM student_profiles s WHERE s.user_id = $user_id")->fetch_assoc();
$student_name = $student['name'];
$roll_number = $student['roll_number'];

// Fetch events
$events = $conn->query("SELECT event_id, title, date FROM events ORDER BY date DESC");

// Attendance status for selected event
$attendance_status = "";
if ($selected_event) {
    $check = $conn->query("SELECT status, date FROM attendance WHERE user_id = $user_id AND event_id = $selected_event");
    $attendance_status = ($check->num_rows > 0) ? ucfirst($check->fetch_assoc()['status']) : "No record found";
}

// Overall attendance data
$total_classes = 0;
$total_present = 0;
$attendance_history = [];
$result = $conn->query("SELECT e.title, a.status, a.date 
                        FROM attendance a 
                        JOIN events e ON a.event_id = e.event_id 
                        WHERE a.user_id = $user_id 
                        ORDER BY a.date DESC");
while ($row = $result->fetch_assoc()) {
    $attendance_history[] = $row;
    $total_classes++;
    if ($row['status'] === 'present') $total_present++;
}
$percentage = ($total_classes > 0) ? round(($total_present / $total_classes) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance Portal</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; line-height: 1.6; 
        }
        
        .background-pattern {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.05; z-index: -1;
            background-image: radial-gradient(circle at 25% 25%, #fff 2px, transparent 2px);
            background-size: 50px 50px;
        }
        
        header {
            background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: white; padding: 2rem 0; text-align: center;
        }
        
        header h1 { font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
        header p { font-size: 1.1rem; opacity: 0.9; }
        
        .student-info {
            background: rgba(255, 255, 255, 0.15); margin-top: 1rem; padding: 1rem 2rem;
            border-radius: 25px; display: inline-flex; align-items: center; gap: 1rem;
        }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        
        .card {
            background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); transition: all 0.3s ease;
        }
        .card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.15); transform: translateY(-2px); }
        
        .form-card { display: flex; align-items: end; gap: 1.5rem; flex-wrap: wrap; }
        
        .form-group { display: flex; flex-direction: column; min-width: 250px; }
        .form-group label {
            font-weight: 600; color: #2c3e50; margin-bottom: 0.5rem;
            text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .form-group select {
            padding: 0.875rem 1rem; border: 2px solid #e1e8ed; border-radius: 8px;
            font-size: 1rem; transition: all 0.3s ease; background: white;
        }
        .form-group select:focus {
            outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 0.875rem 2rem; border: none; border-radius: 8px; font-size: 1rem;
            font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2); color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        
        .btn-export {
            background: white; color: #2c3e50; border: 2px solid #667eea;
            margin: 0.5rem 0.5rem 0 0; padding: 0.75rem 1.5rem;
        }
        .btn-export:hover { background: #667eea; color: white; }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem; margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white; padding: 2rem; border-radius: 12px; text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); position: relative; overflow: hidden;
        }
        
        .stat-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }
        
        .stat-number {
            font-size: 3rem; font-weight: 700; color: #667eea; margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #2c3e50; font-weight: 600; text-transform: uppercase;
            letter-spacing: 0.5px; font-size: 0.9rem;
        }
        
        .status-display {
            text-align: center; padding: 2rem; border-radius: 12px; margin: 1.5rem 0;
            font-size: 1.2rem; font-weight: 600;
        }
        
        .status-present {
            background: linear-gradient(135deg, #4CAF50, #45a049); color: white;
        }
        
        .status-absent {
            background: linear-gradient(135deg, #f44336, #d32f2f); color: white;
        }
        
        .status-no-record {
            background: linear-gradient(135deg, #ff9800, #f57c00); color: white;
        }
        
        .percentage-display {
            text-align: center; margin: 2rem 0;
        }
        
        .percentage-circle {
            width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 1rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700; color: white;
            position: relative;
        }
        
        .chart-container {
            max-width: 400px; margin: 2rem auto; padding: 1.5rem;
            background: #f8fafc; border-radius: 12px;
        }
        
        .certificate-section {
            text-align: center; padding: 2rem; border-radius: 12px; margin-top: 2rem;
        }
        
        .certificate-available {
            background: linear-gradient(135deg, #4CAF50, #45a049); color: white;
        }
        
        .certificate-unavailable {
            background: linear-gradient(135deg, #f44336, #d32f2f); color: white;
        }
        
        .certificate-btn {
            background: rgba(255,255,255,0.2); color: white; border: 2px solid white;
            padding: 1rem 2rem; margin-top: 1rem; font-size: 1.1rem;
        }
        .certificate-btn:hover { background: white; color: #4CAF50; }
        
        table {
            width: 100%; border-collapse: collapse; font-size: 0.95rem;
            background: white; border-radius: 8px; overflow: hidden;
        }
        
        th {
            background: linear-gradient(135deg, #667eea, #764ba2); color: white;
            font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
            padding: 1.25rem 1rem; text-align: center;
        }
        
        td {
            padding: 1rem; text-align: center; border-bottom: 1px solid #f0f4f8;
            transition: all 0.3s ease;
        }
        
        tr:hover td { background-color: #f8fafc; }
        
        .status-badge {
            padding: 0.25rem 0.75rem; border-radius: 20px; font-weight: 600;
            font-size: 0.85rem; text-transform: uppercase;
        }
        
        .badge-present { background: #4CAF50; color: white; }
        .badge-absent { background: #f44336; color: white; }
        
        .no-data {
            text-align: center; padding: 4rem; color: #6c757d;
        }
        .no-data i { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        
        .progress-bar {
            width: 100%; height: 8px; background: #e1e8ed; border-radius: 4px;
            overflow: hidden; margin: 1rem 0;
        }
        
        .progress-fill {
            height: 100%; background: linear-gradient(90deg, #4CAF50, #45a049);
            border-radius: 4px; transition: width 1s ease;
        }
        
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            header h1 { font-size: 2rem; }
            .form-card { flex-direction: column; align-items: stretch; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .stat-number { font-size: 2rem; }
            .btn-export { width: 100%; margin: 0.25rem 0; }
            th, td { padding: 0.75rem 0.5rem; font-size: 0.85rem; }
        }
        
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .student-info { flex-direction: column; text-align: center; }
        }
        
        .fade-in { animation: fadeIn 0.6s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .loading {
            display: inline-block; width: 20px; height: 20px;
            border: 2px solid #f3f3f3; border-top: 2px solid #667eea;
            border-radius: 50%; animation: spin 1s linear infinite;
        }
        
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    
    <header>
        <h1><i class="fas fa-user-graduate"></i> My Attendance Portal</h1>
        <p>Track your attendance and academic progress</p>
        <div class="student-info">
            <i class="fas fa-user-circle"></i>
            <div>
                <strong><?= htmlspecialchars($student_name) ?></strong>
                <br>Roll: <?= htmlspecialchars($roll_number) ?>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Event Selection -->
        <div class="card fade-in">
            <form method="POST" class="form-card">
                <div class="form-group">
                    <label for="event_id"><i class="fas fa-calendar-event"></i> Select Event</label>
                    <select name="event_id" id="event_id" required>
                        <option value="">-- Choose Event to Check --</option>
                        <?php while ($row = $events->fetch_assoc()): ?>
                            <option value="<?= $row['event_id'] ?>" <?= ($row['event_id'] == $selected_event) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['title']) ?> (<?= date('M d, Y', strtotime($row['date'])) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Check Status
                    </button>
                </div>
            </form>
        </div>

        <?php if ($selected_event): ?>
            <div class="card fade-in">
                <h2><i class="fas fa-clipboard-check"></i> Event Attendance Status</h2>
                <div class="status-display status-<?= strtolower(str_replace(' ', '-', $attendance_status)) ?>">
                    <i class="fas fa-<?= $attendance_status === 'Present' ? 'check-circle' : ($attendance_status === 'Absent' ? 'times-circle' : 'question-circle') ?>"></i>
                    <strong>Status: <?= htmlspecialchars($attendance_status) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <!-- Overall Statistics -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-number"><?= $total_classes ?></div>
                <div class="stat-label">Total Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #4CAF50;"><?= $total_present ?></div>
                <div class="stat-label">Present</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #f44336;"><?= $total_classes - $total_present ?></div>
                <div class="stat-label">Absent</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: <?= $percentage >= 75 ? '#4CAF50' : ($percentage >= 50 ? '#ff9800' : '#f44336') ?>;"><?= $percentage ?>%</div>
                <div class="stat-label">Attendance Rate</div>
            </div>
        </div>

        <!-- Overall Performance -->
        <div class="card fade-in">
            <h2><i class="fas fa-chart-pie"></i> Overall Attendance Summary</h2>
            
            <div class="percentage-display">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $percentage ?>%; background: <?= $percentage >= 75 ? 'linear-gradient(90deg, #4CAF50, #45a049)' : ($percentage >= 50 ? 'linear-gradient(90deg, #ff9800, #f57c00)' : 'linear-gradient(90deg, #f44336, #d32f2f)') ?>;"></div>
                </div>
                <p style="font-size: 1.2rem; font-weight: 600; color: #2c3e50;">
                    Your attendance rate: <span style="color: <?= $percentage >= 75 ? '#4CAF50' : ($percentage >= 50 ? '#ff9800' : '#f44336') ?>;"><?= $percentage ?>%</span>
                </p>
            </div>

            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>

            <!-- Certificate Section -->
            <div class="certificate-section <?= $percentage >= 75 ? 'certificate-available' : 'certificate-unavailable' ?>">
                <?php if ($percentage >= 75): ?>
                    <i class="fas fa-certificate" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h3>🎉 Congratulations!</h3>
                    <p>Your attendance is excellent! You're eligible for an attendance certificate.</p>
                    <button class="btn certificate-btn" onclick="downloadCertificate()">
                        <i class="fas fa-download"></i> Download Certificate
                    </button>
                <?php else: ?>
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <h3>Improve Your Attendance</h3>
                    <p>You need at least 75% attendance to earn a certificate. Current: <?= $percentage ?>%</p>
                    <p>Attend <?= max(0, ceil((75 * $total_classes / 100) - $total_present)) ?> more sessions to reach 75%!</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="card fade-in">
            <h2><i class="fas fa-history"></i> Attendance History</h2>
            
            <?php if (!empty($attendance_history)): ?>
                <div style="overflow-x: auto;">
                    <table id="attendanceTable">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-event"></i> Event</th>
                                <th><i class="fas fa-calendar-day"></i> Date</th>
                                <th><i class="fas fa-check-circle"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_history as $row): ?>
                                <tr>
                                    <td style="text-align: left; font-weight: 600;"><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                    <td>
                                        <span class="status-badge badge-<?= strtolower($row['status']) ?>">
                                            <i class="fas fa-<?= $row['status'] === 'present' ? 'check' : 'times' ?>"></i>
                                            <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Export Buttons -->
                <div style="margin-top: 2rem; text-align: center;">
                    <button class="btn btn-export" onclick="exportTableToExcel('attendanceTable', 'My_Attendance_History')">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <button class="btn btn-export" onclick="exportTableToPDF()">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-export" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Attendance Records</h3>
                    <p>You don't have any attendance records yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize chart
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: { 
                labels: ['Present', 'Absent'], 
                datasets: [{ 
                    data: [<?= $total_present ?>, <?= $total_classes - $total_present ?>], 
                    backgroundColor: ['#4CAF50', '#f44336'],
                    borderWidth: 2,
                    hoverOffset: 10
                }] 
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            font: { size: 14 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = <?= $total_classes ?>;
                                const percentage = total > 0 ? ((context.parsed / total) * 100).toFixed(1) : 0;
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    duration: 2000
                }
            }
        });

        // Export functions
        function exportTableToExcel(tableID, filename = '') {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading"></span> Exporting...';
            btn.disabled = true;
            
            setTimeout(() => {
                const dataType = 'application/vnd.ms-excel';
                const tableSelect = document.getElementById(tableID);
                const tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
                filename = filename ? filename + '_' + new Date().toISOString().slice(0,10) + '.xls' : 'attendance_data.xls';
                
                const downloadLink = document.createElement("a");
                document.body.appendChild(downloadLink);
                downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
                downloadLink.download = filename;
                downloadLink.click();
                document.body.removeChild(downloadLink);
                
                btn.innerHTML = originalText;
                btn.disabled = false;
                showNotification('Excel file downloaded successfully!', 'success');
            }, 1000);
        }

        function exportTableToPDF() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading"></span> Generating...';
            btn.disabled = true;
            
            setTimeout(() => {
                const table = document.getElementById("attendanceTable");
                const w = window.open('', '', 'height=700,width=900');
                w.document.write(`
                    <html>
                        <head>
                            <title>My Attendance History</title>
                            <style>
                                body { font-family: Arial, sans-serif; margin: 20px; }
                                h1 { color: #667eea; text-align: center; margin-bottom: 30px; }
                                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                                th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
                                th { background-color: #667eea; color: white; }
                                .student-info { text-align: center; margin-bottom: 20px; padding: 15px; background: #f0f4f8; border-radius: 8px; }
                            </style>
                        </head>
                        <body>
                            <h1>📚 My Attendance History</h1>
                            <div class="student-info">
                                <strong>Student:</strong> <?= htmlspecialchars($student_name) ?> | 
                                <strong>Roll Number:</strong> <?= htmlspecialchars($roll_number) ?> |
                                <strong>Attendance Rate:</strong> <?= $percentage ?>%
                            </div>
                `);
                w.document.write(table.outerHTML);
                w.document.write(`
                            <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
                                <p>Generated on: ${new Date().toLocaleDateString()}</p>
                            </div>
                        </body>
                    </html>
                `);
                w.document.close();
                w.print();
                
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 1000);
        }

        function downloadCertificate() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading"></span> Generating...';
            btn.disabled = true;
            
            setTimeout(() => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Certificate Header
                doc.setFontSize(24);
                doc.setTextColor(102, 126, 234);
                doc.text("ATTENDANCE CERTIFICATE", 105, 40, { align: 'center' });
                
                doc.setFontSize(16);
                doc.setTextColor(0, 0, 0);
                doc.text("This is to certify that", 105, 60, { align: 'center' });
                
                doc.setFontSize(20);
                doc.setTextColor(102, 126, 234);
                doc.text("<?= $student_name ?>", 105, 80, { align: 'center' });
                
                doc.setFontSize(14);
                doc.setTextColor(0, 0, 0);
                doc.text("Roll Number: <?= $roll_number ?>", 105, 95, { align: 'center' });
                
                doc.text("has maintained excellent attendance with", 105, 115, { align: 'center' });
                
                doc.setFontSize(18);
                doc.setTextColor(76, 175, 80);
                doc.text("<?= $percentage ?>% Attendance Rate", 105, 135, { align: 'center' });
                
                doc.setFontSize(12);
                doc.setTextColor(0, 0, 0);
                doc.text("Total Sessions Attended: <?= $total_present ?> out of <?= $total_classes ?>", 105, 155, { align: 'center' });
                
                doc.text("Date of Issue: " + new Date().toLocaleDateString(), 105, 180, { align: 'center' });
                
                // Border
                doc.setDrawColor(102, 126, 234);
                doc.setLineWidth(2);
                doc.rect(15, 15, 180, 250);
                
                doc.save("Attendance_Certificate_<?= $student_name ?>.pdf");
                
                btn.innerHTML = originalText;
                btn.disabled = false;
                showNotification('Certificate downloaded successfully!', 'success');
            }, 2000);
        }

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; padding: 1rem 1.5rem;
                background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                color: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 1000; font-weight: 600; transform: translateX(400px);
                transition: transform 0.3s ease;
            `;
            notification.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
            
            document.body.appendChild(notification);
            setTimeout(() => notification.style.transform = 'translateX(0)', 100);
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bar
            setTimeout(() => {
                const progressFill = document.querySelector('.progress-fill');
                if (progressFill) {
                    progressFill.style.width = '<?= $percentage ?>%';
                }
            }, 500);
            
            // Add staggered animations
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((el, index) => {
                el.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>