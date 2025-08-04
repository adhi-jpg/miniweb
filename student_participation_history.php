<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query with filter
$query = "
    SELECT ep.participation_id, ep.status, e.title AS event_title, e.date
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
    <title>Participation History – Student</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f2f2f2; margin: 0; padding: 0; }
        header { background: linear-gradient(to right, #4a00e0, #8e2de2); padding: 20px; color: white; font-size: 22px; text-align: center; font-weight: bold; }
        .container { max-width: 1000px; margin: 30px auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: center; border-bottom: 1px solid #ddd; }
        th { background: #4a00e0; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .status { font-weight: bold; padding: 5px 10px; border-radius: 5px; }
        .approved { background: #c8e6c9; color: #256029; }
        .rejected { background: #ffcdd2; color: #b71c1c; }
        .pending { background: #fff9c4; color: #827717; }
        .no-data { text-align: center; padding: 20px; color: #666; font-size: 18px; }
        .filters, .export-buttons { margin: 10px 0; display: flex; justify-content: space-between; flex-wrap: wrap; }
        select, button { padding: 10px 15px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; }
        select { background: #f4f4f4; }
        button { background: #4a00e0; color: white; }
        button:hover { background: #3700b3; }
    </style>
</head>
<body>
<header>
    <i class="fas fa-history"></i> My Participation History
</header>
<div class="container">

    <!-- Filter Dropdown -->
    <div class="filters">
        <form method="GET" style="display:flex; gap:10px;">
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
            <button onclick="exportPDF()">Export PDF</button>
            <button onclick="exportCSV()">Export Excel</button>
        </div>
    </div>

    <?php if($result->num_rows > 0): ?>
    <table id="historyTable">
        <tr>
            <th>Participation ID</th>
            <th>Event Title</th>
            <th>Event Date</th>
            <th>Status</th>
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
                    <?= htmlspecialchars(ucfirst($row['status'])) ?>
                </span>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <div class="no-data"><i class="fas fa-info-circle"></i> No participation records found.</div>
    <?php endif; ?>
</div>

<script>
    function exportPDF() {
        const printWindow = window.open('', '', 'width=900,height=650');
        printWindow.document.write('<html><head><title>Participation History</title></head><body>');
        printWindow.document.write('<h2 style="text-align:center;">Participation History</h2>');
        printWindow.document.write(document.getElementById("historyTable").outerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }

    function exportCSV() {
        let table = document.getElementById("historyTable");
        let rows = table.querySelectorAll("tr");
        let csv = [];
        for (let row of rows) {
            let cols = row.querySelectorAll("th,td");
            let rowData = [];
            cols.forEach(col => rowData.push('"' + col.innerText + '"'));
            csv.push(rowData.join(","));
        }
        let csvFile = new Blob([csv.join("\n")], { type: "text/csv" });
        let downloadLink = document.createElement("a");
        downloadLink.download = "participation_history.csv";
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.click();
    }
</script>
</body>
</html>
