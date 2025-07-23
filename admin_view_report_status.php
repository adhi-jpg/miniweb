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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin – Report Status</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background: #f4f4f4;
    }

    header {
      background: linear-gradient(to right, #8e2de2, #4a00e0);
      color: white;
      padding: 20px;
      text-align: center;
    }

    .container {
      padding: 30px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: white;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    th, td {
      padding: 12px 15px;
      border: 1px solid #ccc;
      text-align: left;
    }

    th {
      background-color: #4a00e0;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .status-submitted {
      color: #007bff;
      font-weight: bold;
    }

    .status-reviewed {
      color: green;
      font-weight: bold;
    }

    .status-rejected {
      color: red;
      font-weight: bold;
    }

    .download-link {
      color: #007bff;
      text-decoration: none;
    }

    .download-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <header>
    <h1>📄 Your Submitted Reports</h1>
  </header>

  <div class="container">
    <?php if ($result->num_rows > 0): ?>
    <table>
      <tr>
        <th>Title</th>
        <th>Description</th>
        <th>File</th>
        <th>Submitted At</th>
        <th>Status</th>
        <th>Feedback</th>
      </tr>
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($row['title']) ?></td>
        <td><?= nl2br(htmlspecialchars($row['description'])) ?></td>
        <td><a href="<?= htmlspecialchars($row['file_path']) ?>" class="download-link" target="_blank"><i class="fas fa-file-download"></i> Download</a></td>
        <td><?= htmlspecialchars($row['submitted_at']) ?></td>
        <td class="status-<?= htmlspecialchars($row['status']) ?>">
          <?php
            if ($row['status'] === 'reviewed') echo '✅ Approved';
            elseif ($row['status'] === 'rejected') echo '❌ Rejected';
            else echo '⏳ Submitted';
          ?>
        </td>
        <td><?= nl2br(htmlspecialchars($row['feedback'])) ?></td>
      </tr>
      <?php endwhile; ?>
    </table>
    <?php else: ?>
      <p>No reports submitted yet.</p>
    <?php endif; ?>
  </div>
</body>
</html>
