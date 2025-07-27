<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$status_msg = "";

// Handle approval/rejection
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['participation_id'], $_POST['action'])) {
    $participation_id = intval($_POST['participation_id']);
    $action = $_POST['action'];

    if (in_array($action, ['confirmed', 'rejected'])) {
        $update = "UPDATE event_participation SET status = '$action' WHERE participation_id = $participation_id";
        if ($conn->query($update)) {
            $status_msg = "✅ Status updated successfully.";
        } else {
            $status_msg = "❌ Failed to update status.";
        }
    }
}

// Query to display registrations
$sql = "SELECT 
            ep.participation_id, 
            ep.event_id, 
            sp.name, 
            sp.roll_number, 
            ep.status
        FROM event_participation ep
        JOIN student_profiles sp ON ep.user_id = sp.user_id
        ORDER BY ep.event_id, ep.participation_id";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Event Registrations – Admin</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f4f4;
      margin: 0;
    }

    header {
      background: linear-gradient(to right, #4a00e0, #8e2de2);
      color: white;
      padding: 20px;
      text-align: center;
    }

    .container {
      padding: 30px;
    }

    .status-msg {
      margin-bottom: 20px;
      padding: 12px;
      border-radius: 8px;
      font-weight: 600;
      color: #155724;
      background-color: #d4edda;
      border: 1px solid #c3e6cb;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
    }

    th, td {
      padding: 12px 14px;
      border: 1px solid #ddd;
      text-align: left;
    }

    th {
      background-color: #4a00e0;
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .status {
      font-weight: bold;
      text-transform: capitalize;
    }

    .status.pending {
      color: orange;
    }

    .status.confirmed {
      color: green;
    }

    .status.rejected {
      color: red;
    }

    form {
      display: flex;
      gap: 8px;
      align-items: center;
    }

    button {
      padding: 6px 12px;
      font-size: 13px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-weight: bold;
    }

    .approve-btn {
      background-color: #28a745;
      color: white;
    }

    .reject-btn {
      background-color: #dc3545;
      color: white;
    }

    button:hover {
      opacity: 0.9;
    }
  </style>
</head>
<body>
  <header>
    <h1>📋 Event Participation Status</h1>
  </header>

  <div class="container">
    <?php if ($status_msg): ?>
      <div class="status-msg"><?= $status_msg ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Participation ID</th>
            <th>Event ID</th>
            <th>Student Name</th>
            <th>Roll Number</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['participation_id']) ?></td>
              <td><?= htmlspecialchars($row['event_id']) ?></td>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td><?= htmlspecialchars($row['roll_number']) ?></td>
              <td class="status <?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></td>
              <td>
                <?php if (strtolower($row['status']) === 'pending'): ?>
                  <form method="POST" style="margin: 0;">
                    <input type="hidden" name="participation_id" value="<?= $row['participation_id'] ?>">
                    <button type="submit" name="action" value="confirmed" class="approve-btn">Approve</button>
                    <button type="submit" name="action" value="rejected" class="reject-btn">Reject</button>
                  </form>
                <?php else: ?>
                  <span>Already <?= htmlspecialchars($row['status']) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p>No participation records found.</p>
    <?php endif; ?>
  </div>
</body>
</html>
