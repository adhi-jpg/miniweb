<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle filters
$selected_event = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-01');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');

// Fetch events for dropdown
$events = $conn->query("SELECT event_id, title FROM events ORDER BY date DESC");

$attendance_data = [];
$present_count = $absent_count = 0;
$student_stats = [];

if ($selected_event) {
    $attendance_records = $conn->query("
        SELECT s.roll_number, s.name, a.status, a.date
        FROM attendance a
        JOIN users u ON a.user_id = u.user_id
        JOIN student_profiles s ON u.user_id = s.user_id
        WHERE a.event_id = $selected_event 
        AND a.date BETWEEN '$from_date' AND '$to_date'
        ORDER BY s.roll_number, a.date
    ");

    while ($row = $attendance_records->fetch_assoc()) {
        $attendance_data[] = $row;
        if ($row['status'] == 'present') $present_count++;
        if ($row['status'] == 'absent') $absent_count++;

        // Student-wise count
        $roll = $row['roll_number'];
        if (!isset($student_stats[$roll])) {
            $student_stats[$roll] = ['name' => $row['name'], 'present' => 0, 'total' => 0];
        }
        $student_stats[$roll]['total']++;
        if ($row['status'] == 'present') $student_stats[$roll]['present']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea, #764ba2); min-height: 100vh; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 1rem; }
        
        .card { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card:hover { transform: translateY(-2px); transition: 0.3s; }
        
        header { background: rgba(255,255,255,0.1); backdrop-filter: blur(20px); color: white; padding: 2rem 0; text-align: center; }
        header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: #2c3e50; margin-bottom: 0.5rem; }
        .form-group select, .form-group input { padding: 0.75rem; border: 2px solid #e1e8ed; border-radius: 8px; font-size: 1rem; }
        .form-group select:focus, .form-group input:focus { outline: none; border-color: #667eea; }
        
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.15); }
        .btn-export { background: white; color: #2c3e50; border: 2px solid #667eea; margin: 0.5rem 0.5rem 0 0; }
        .btn-export:hover { background: #667eea; color: white; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 12px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2rem; font-weight: 700; color: #667eea; }
        .stat-label { color: #2c3e50; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: center; border-bottom: 1px solid #f0f4f8; }
        th { background: linear-gradient(135deg, #667eea, #764ba2); color: white; font-weight: 600; }
        tr:hover td { background-color: #f8fafc; }
        
        .status-present { background: #4CAF50; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; }
        .status-absent { background: #f44336; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; }
        
        .percentage-bar { width: 100%; height: 4px; background: #e1e8ed; border-radius: 2px; margin-top: 0.25rem; }
        .percentage-fill { height: 100%; background: #4CAF50; border-radius: 2px; transition: width 0.8s ease; }
        
        .chart-container { max-width: 400px; margin: 2rem auto; background: white; padding: 1.5rem; border-radius: 12px; }
        .no-data { text-align: center; padding: 3rem; color: #6c757d; }
        
        @media (max-width: 768px) {
            .container { padding: 0.5rem; }
            header h1 { font-size: 1.8rem; }
            .form-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .btn-export { width: 100%; margin: 0.25rem 0; }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-chart-line"></i> Attendance Dashboard</h1>
        <p>Monitor and analyze student attendance</p>
    </header>

    <div class="container">
        <div class="card">
            <form method="POST" class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-calendar-event"></i> Event</label>
                    <select name="event_id" required>
                        <option value="">Choose Event</option>
                        <?php while ($row = $events->fetch_assoc()): ?>
                            <option value="<?= $row['event_id'] ?>" <?= $row['event_id'] == $selected_event ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> From</label>
                    <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> To</label>
                    <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Analyze
                    </button>
                </div>
            </form>
        </div>

        <?php if ($selected_event && !empty($attendance_data)): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $present_count + $absent_count ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #4CAF50;"><?= $present_count ?></div>
                    <div class="stat-label">Present</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #f44336;"><?= $absent_count ?></div>
                    <div class="stat-label">Absent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #2196F3;">
                        <?= ($present_count + $absent_count) > 0 ? round(($present_count / ($present_count + $absent_count)) * 100, 1) : 0 ?>%
                    </div>
                    <div class="stat-label">Rate</div>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-table"></i> Detailed Records</h2>
                <div style="overflow-x: auto;">
                    <table id="attendanceTable">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_data as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['roll_number']) ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                    <td><span class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h2><i class="fas fa-percentage"></i> Student Analysis</h2>
                <div style="overflow-x: auto;">
                    <table id="percentageTable">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Present</th>
                                <th>Total</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($student_stats as $roll => $stats): 
                                $percent = ($stats['total'] > 0) ? round(($stats['present'] / $stats['total']) * 100, 2) : 0;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($roll) ?></td>
                                    <td><?= htmlspecialchars($stats['name']) ?></td>
                                    <td style="color: #4CAF50; font-weight: 600;"><?= $stats['present'] ?></td>
                                    <td><?= $stats['total'] ?></td>
                                    <td>
                                        <div style="font-weight: 700;"><?= $percent ?>%</div>
                                        <div class="percentage-bar">
                                            <div class="percentage-fill" style="width: <?= $percent ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>

            <div class="card">
                <h3><i class="fas fa-download"></i> Export Reports</h3>
                <button class="btn btn-export" onclick="exportToExcel('attendanceTable', 'Detailed_Report')">
                    <i class="fas fa-file-excel"></i> Excel Detailed
                </button>
                <button class="btn btn-export" onclick="exportToExcel('percentageTable', 'Percentage_Report')">
                    <i class="fas fa-chart-bar"></i> Excel Analysis
                </button>
                <button class="btn btn-export" onclick="exportToPDF()">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button class="btn btn-export" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>

        <?php else: ?>
            <div class="card">
                <div class="no-data">
                    <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3><?= $selected_event ? 'No Records Found' : 'Select Event to View Data' ?></h3>
                    <p><?= $selected_event ? 'No attendance records for selected criteria.' : 'Choose an event and date range to analyze attendance.' ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        <?php if ($selected_event && !empty($attendance_data)): ?>
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Present', 'Absent'],
                datasets: [{
                    data: [<?= $present_count ?>, <?= $absent_count ?>],
                    backgroundColor: ['#4CAF50', '#f44336'],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        <?php endif; ?>

        function exportToExcel(tableId, filename) {
            const table = document.getElementById(tableId);
            const html = table.outerHTML.replace(/ /g, '%20');
            const link = document.createElement('a');
            link.href = 'data:application/vnd.ms-excel,' + html;
            link.download = filename + '_' + new Date().toISOString().slice(0,10) + '.xls';
            link.click();
        }

        function exportToPDF() {
            const w = window.open('', '', 'width=800,height=600');
            w.document.write(`
                <html><head><title>Attendance Report</title>
                <style>body{font-family:Arial;margin:20px}table{width:100%;border-collapse:collapse;margin:20px 0}th,td{border:1px solid #ddd;padding:8px;text-align:center}th{background:#667eea;color:white}</style>
                </head><body><h1>📊 Attendance Report</h1>
            `);
            w.document.write(document.getElementById('attendanceTable').outerHTML);
            w.document.write('<h2>📈 Analysis</h2>');
            w.document.write(document.getElementById('percentageTable').outerHTML);
            w.document.write('</body></html>');
            w.document.close();
            w.print();
        }
    </script>
</body>
</html>