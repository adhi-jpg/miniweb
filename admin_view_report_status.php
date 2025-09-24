<?php
session_start();
include "config.php";

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get all reports submitted by this admin
$sql = "SELECT * FROM reports WHERE submitted_by = $admin_id ORDER BY submitted_at DESC";
$result = $conn->query($sql);

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
              FROM reports WHERE submitted_by = $admin_id";
$stats = $conn->query($stats_sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Report Status Dashboard</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

    .main-container {
      max-width: 1400px;
      margin: 0 auto;
      background: white;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 25px 50px rgba(0,0,0,0.15);
      backdrop-filter: blur(10px);
    }

    header {
      background: linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%);
      color: white;
      padding: 40px 30px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    header::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 20%, transparent 70%);
      animation: float 6s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-20px); }
    }

    header h1 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 10px;
      position: relative;
      z-index: 2;
    }

    header .subtitle {
      font-size: 1.1rem;
      opacity: 0.9;
      position: relative;
      z-index: 2;
    }

    .container {
      padding: 40px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 25px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      padding: 25px;
      border-radius: 15px;
      text-align: center;
      border-left: 6px solid #8e2de2;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, transparent 0%, rgba(142, 45, 226, 0.05) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .stat-card:hover::before {
      opacity: 1;
    }

    .stat-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }

    .stat-number {
      font-size: 2.5rem;
      font-weight: 900;
      color: #8e2de2;
      margin-bottom: 8px;
      position: relative;
      z-index: 2;
    }

    .stat-label {
      color: #6c757d;
      font-weight: 600;
      font-size: 1rem;
      position: relative;
      z-index: 2;
    }

    .stat-icon {
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 2rem;
      color: rgba(142, 45, 226, 0.2);
      z-index: 1;
    }

    .table-wrapper {
      background: white;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 15px 35px rgba(0,0,0,0.1);
      border: 1px solid rgba(0,0,0,0.05);
      position: relative;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
    }

    th, td {
      padding: 20px 18px;
      text-align: left;
      border-bottom: 1px solid rgba(0,0,0,0.06);
      transition: all 0.3s ease;
    }

    th {
      background: linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%);
      color: white;
      font-weight: 700;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
      position: sticky;
      top: 0;
      z-index: 10;
      box-shadow: 0 2px 10px rgba(142, 45, 226, 0.3);
    }

    tbody tr {
      transition: all 0.3s ease;
      cursor: pointer;
    }

    tbody tr:hover {
      background: linear-gradient(90deg, rgba(142, 45, 226, 0.03), rgba(74, 0, 224, 0.03));
      transform: translateX(8px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }

    tbody tr:nth-child(even) {
      background-color: rgba(248, 249, 250, 0.7);
    }

    tbody tr:nth-child(even):hover {
      background: linear-gradient(90deg, rgba(142, 45, 226, 0.05), rgba(74, 0, 224, 0.05));
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 18px;
      border-radius: 25px;
      font-weight: 700;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      min-width: 120px;
      justify-content: center;
      transition: all 0.3s ease;
    }

    .status-submitted {
      background: linear-gradient(135deg, #e3f2fd, #bbdefb);
      color: #1565c0;
      border: 2px solid #2196f3;
      box-shadow: 0 4px 15px rgba(33, 150, 243, 0.2);
    }

    .status-reviewed {
      background: linear-gradient(135deg, #e8f5e8, #c8e6c9);
      color: #2e7d32;
      border: 2px solid #4caf50;
      box-shadow: 0 4px 15px rgba(76, 175, 80, 0.2);
    }

    .status-rejected {
      background: linear-gradient(135deg, #ffebee, #ffcdd2);
      color: #c62828;
      border: 2px solid #f44336;
      box-shadow: 0 4px 15px rgba(244, 67, 54, 0.2);
    }

    .download-link {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 12px 20px;
      background: linear-gradient(135deg, #007bff, #0056b3);
      color: white;
      text-decoration: none;
      border-radius: 10px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    }

    .download-link:hover {
      background: linear-gradient(135deg, #0056b3, #004085);
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4);
      text-decoration: none;
      color: white;
    }

    .no-reports {
      text-align: center;
      padding: 80px 20px;
      color: #6c757d;
    }

    .no-reports i {
      font-size: 5rem;
      color: #dee2e6;
      margin-bottom: 25px;
      display: block;
    }

    .no-reports h3 {
      font-size: 1.8rem;
      margin-bottom: 15px;
      color: #495057;
    }

    .no-reports p {
      font-size: 1.2rem;
      opacity: 0.8;
      max-width: 400px;
      margin: 0 auto;
      line-height: 1.6;
    }

    .description-cell, .feedback-cell {
      max-width: 300px;
      word-wrap: break-word;
      line-height: 1.5;
    }

    .title-cell {
      font-weight: 700;
      color: #495057;
      font-size: 15px;
    }

    .date-cell {
      color: #6c757d;
      font-family: 'Courier New', monospace;
      font-size: 13px;
    }

    @media (max-width: 768px) {
      body {
        padding: 10px;
      }

      .main-container {
        border-radius: 15px;
      }

      header {
        padding: 30px 20px;
      }

      header h1 {
        font-size: 2rem;
      }

      .container {
        padding: 25px;
      }

      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
      }

      .stat-card {
        padding: 20px;
      }

      .stat-number {
        font-size: 2rem;
      }

      .table-wrapper {
        overflow-x: auto;
        border-radius: 12px;
      }

      table {
        min-width: 800px;
      }

      th, td {
        padding: 15px 12px;
        font-size: 14px;
      }

      .status-badge {
        padding: 8px 14px;
        font-size: 12px;
        min-width: 100px;
      }

      .download-link {
        padding: 10px 16px;
        font-size: 13px;
      }
    }

    @media (max-width: 480px) {
      header h1 {
        font-size: 1.7rem;
      }

      .container {
        padding: 20px;
      }

      .stats-grid {
        grid-template-columns: 1fr 1fr;
      }

      th, td {
        padding: 12px 8px;
        font-size: 13px;
      }

      .description-cell, .feedback-cell {
        max-width: 200px;
      }
    }

    /* Scrollbar Styling */
    .table-wrapper::-webkit-scrollbar {
      height: 10px;
    }

    .table-wrapper::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }

    .table-wrapper::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #8e2de2, #4a00e0);
      border-radius: 10px;
    }

    .table-wrapper::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #7e1dd2, #3a00b0);
    }

    /* Loading Animation */
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
    }

    .loading {
      animation: pulse 1.5s ease-in-out infinite;
    }
  </style>
</head>
<body>
  <div class="main-container">
    <header>
      <h1><i class="fas fa-chart-line"></i> Report Status Dashboard</h1>
      <p class="subtitle">Track Your Submitted Reports & Their Review Status</p>
    </header>

    <div class="container">
      <!-- Statistics Cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <i class="fas fa-file-alt stat-icon"></i>
          <div class="stat-number"><?= $stats['total'] ?></div>
          <div class="stat-label">Total Reports</div>
        </div>
        <div class="stat-card">
          <i class="fas fa-clock stat-icon"></i>
          <div class="stat-number"><?= $stats['submitted'] ?></div>
          <div class="stat-label">Pending Review</div>
        </div>
        <div class="stat-card">
          <i class="fas fa-check-circle stat-icon"></i>
          <div class="stat-number"><?= $stats['approved'] ?></div>
          <div class="stat-label">Approved</div>
        </div>
        <div class="stat-card">
          <i class="fas fa-times-circle stat-icon"></i>
          <div class="stat-number"><?= $stats['rejected'] ?></div>
          <div class="stat-label">Rejected</div>
        </div>
      </div>

      <?php if ($result->num_rows > 0): ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th><i class="fas fa-heading"></i> Title</th>
                <th><i class="fas fa-align-left"></i> Description</th>
                <th><i class="fas fa-download"></i> File</th>
                <th><i class="fas fa-calendar"></i> Submitted</th>
                <th><i class="fas fa-info-circle"></i> Status</th>
                <th><i class="fas fa-comment"></i> Feedback</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="title-cell"><?= htmlspecialchars($row['title']) ?></td>
                <td class="description-cell"><?= nl2br(htmlspecialchars($row['description'])) ?></td>
                <td>
                  <a href="<?= htmlspecialchars($row['file_path']) ?>" class="download-link" target="_blank">
                    <i class="fas fa-file-download"></i>
                    <span>Download</span>
                  </a>
                </td>
                <td class="date-cell"><?= date('M d, Y g:i A', strtotime($row['submitted_at'])) ?></td>
                <td>
                  <span class="status-badge status-<?= htmlspecialchars($row['status']) ?>">
                    <?php
                      if ($row['status'] === 'approved') {
                        echo '<i class="fas fa-check"></i> Approved';
                      } elseif ($row['status'] === 'rejected') {
                        echo '<i class="fas fa-times"></i> Rejected';
                      } else {
                        echo '<i class="fas fa-clock"></i> Pending';
                      }
                    ?>
                  </span>
                </td>
                <td class="feedback-cell">
                  <?php if (!empty($row['feedback'])): ?>
                    <?= nl2br(htmlspecialchars($row['feedback'])) ?>
                  <?php else: ?>
                    <span style="color: #6c757d; font-style: italic;">No feedback yet</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="no-reports">
          <i class="fas fa-file-plus"></i>
          <h3>No Reports Submitted</h3>
          <p>You haven't submitted any reports yet. When you do, they'll appear here with their current status.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>