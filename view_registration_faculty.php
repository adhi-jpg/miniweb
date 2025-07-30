<?php
session_start();
include "config.php";
// Optional: check faculty role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: login.php");
    exit();
}
// Get event participations with student info
$query = "
    SELECT ep.participation_id, ep.event_id, ep.user_id, ep.status,
           sp.name AS student_name, sp.roll_number
    FROM event_participation ep
    JOIN student_profiles sp ON ep.user_id = sp.user_id
    ORDER BY ep.participation_id DESC
";
$result = $conn->query($query);
// Stats
$stats = ['total' => 0, 'confirmed' => 0, 'pending' => 0];
if ($result && $result->num_rows > 0) {
    $result->data_seek(0);
    while ($row = $result->fetch_assoc()) {
        $stats['total']++;
        if (isset($stats[$row['status']])) {
            $stats[$row['status']]++;
        }
    }
    $result->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Participation Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1100px;
            margin: 38px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(44,63,156,0.09);
            padding: 38px 4vw 25px 4vw;
        }
        .page-header h1 {
            font-size: 2.15rem;
            margin-bottom: 7px;
            color: #28286b;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .page-header .subtitle {
            color: #5e6392;
            font-size: 1.06rem;
            margin-bottom: 14px;
        }
        .stats-row {
            margin: 30px 0 18px 0;
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }
        .stat-card {
            min-width: 155px;
            background: #f7faff;
            border-radius: 13px;
            padding: 19px 24px 17px 18px;
            text-align: center;
        }
        .stat-label {
            color: #7e8ba2;
            font-size: .9rem;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 900;
            color: #2c335d;
            margin-bottom: 3px;
        }
        .stat-card.confirmed .stat-number { color: #19995b;}
        .stat-card.pending .stat-number { color: #e6a14d;}
        .stat-card.total .stat-number { color: #3b50bb;}
        .controls-bar {
            margin-bottom: 28px;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-box {
            position: relative;
            flex: 1;
            max-width: 350px;
        }
        .search-box input {
            width: 100%;
            font-size: 16px;
            padding: 11px 40px 11px 16px;
            border-radius: 30px;
            border: 1.5px solid #dedede;
            background: #fff;
            outline: none;
            transition: border-color 0.2s;
        }
        .search-box input:focus {
            border-color: #4188f7;
        }
        .search-box i {
            position: absolute;
            right: 15px; top: 50%;
            transform: translateY(-50%);
            color: #8796ab;
            font-size: 1.14rem;
        }
        .filter-box select {
            padding: 10px 22px;
            border-radius: 22px;
            font-size: 15px;
            border: 1.5px solid #dedede;
            background: #fff;
            outline: none;
            transition: border-color 0.2s;
        }
        .filter-box select:focus {
            border-color: #4188f7;
        }
        .table-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 16px rgba(44, 46, 156, 0.05);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }
        thead tr {
            background: #f2f6fc;
        }
        th, td {
            padding: 15px 7px;
            font-size: 1rem;
            text-align: left;
        }
        th {
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 700;
            color: #42536c;
        }
        tbody tr {
            border-bottom: 1px solid #eef0f6;
            transition: background 0.16s;
        }
        tbody tr:hover {
            background: #eaf3fc;
        }
        .status-badge {
            border-radius: 13px;
            font-weight: 700;
            padding: 6px 18px;
            font-size: 0.97rem;
            min-width: 88px;
            display: inline-block;
        }
        .status-badge.confirmed {
            background: #e4f8ef;
            color: #13723c;
            border: 1.1px solid #60daab;
        }
        .status-badge.pending {
            background: #fff5e0;
            color: #ad650d;
            border: 1.05px solid #ffe6b4;
        }
        .no-results {
            text-align: center;
            padding: 65px 12px;
            color: #a0aac3;
        }
        .no-results i {
            font-size: 2.4rem;
            margin-bottom: 13px;
            color: #e0e0ed;
        }
        @media (max-width: 760px) {
            .container { padding:20px 2vw 10px 2vw;}
            .stats-row { gap: 10px;}
            th, td { font-size: .97rem; }
        }
        @media (max-width: 480px) {
            .stat-card { min-width: 120px; padding: 10px; }
            th, td {font-size: .93rem;}
            .stats-row { flex-direction: column; gap: 6px;}
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-calendar-check"></i> Event Participations</h1>
        <div class="subtitle">View and search all student event participation records</div>
    </div>
    <div class="stats-row">
        <div class="stat-card total">
            <div class="stat-label">Total</div>
            <div class="stat-number"><?= $stats['total'] ?></div>
        </div>
        <div class="stat-card confirmed">
            <div class="stat-label">Confirmed</div>
            <div class="stat-number"><?= $stats['confirmed'] ?></div>
        </div>
        <div class="stat-card pending">
            <div class="stat-label">Pending</div>
            <div class="stat-number"><?= $stats['pending'] ?></div>
        </div>
    </div>
    <div class="controls-bar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by name, roll, event ID...">
            <i class="fas fa-search"></i>
        </div>
        <div class="filter-box">
            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
            </select>
        </div>
    </div>
    <div class="table-container">
        <?php if ($result && $result->num_rows > 0): ?>
        <table id="participationTable">
            <thead>
                <tr>
                    <th>Participation ID</th>
                    <th>Event ID</th>
                    <th>Student Name</th>
                    <th>Roll Number</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="participation-row" data-status="<?= htmlspecialchars($row['status']) ?>">
                    <td><?= htmlspecialchars($row['participation_id']) ?></td>
                    <td><?= htmlspecialchars($row['event_id']) ?></td>
                    <td class="student-name"><?= htmlspecialchars($row['student_name']) ?></td>
                    <td class="roll-number"><?= htmlspecialchars($row['roll_number']) ?></td>
                    <td>
                        <span class="status-badge <?= htmlspecialchars($row['status']) ?>">
                            <?= ($row['status']==='confirmed') ? '<i class="fas fa-check"></i>' : '<i class="fas fa-clock"></i>'; ?>
                            <?= htmlspecialchars($row['status']) ?>
                        </span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <div class="no-results" id="noResults" style="display:none;">
            <i class="fas fa-search"></i>
            <h3>No Results Found</h3>
            <p>No records match your criteria.</p>
        </div>
        <?php else: ?>
        <div class="no-results">
            <i class="fas fa-inbox"></i>
            <h3>No Participation Records Found</h3>
            <p>No event participation records to display.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const participationRows = document.querySelectorAll('.participation-row');
    const noResults = document.getElementById('noResults');
    const dataTable = document.getElementById('participationTable');
    function filterRows() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const statusValue = statusFilter.value.toLowerCase();
        let visibleCount = 0;
        participationRows.forEach(row => {
            const name = row.querySelector('.student-name').textContent.toLowerCase();
            const roll = row.querySelector('.roll-number').textContent.toLowerCase();
            const eventid = row.children[1].textContent.toLowerCase();
            const status = row.dataset.status.toLowerCase();
            const matchesSearch = !searchTerm || name.includes(searchTerm) || roll.includes(searchTerm) || eventid.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        if (dataTable) {
            if (visibleCount === 0 && participationRows.length > 0) {
                dataTable.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                dataTable.style.display = 'table';
                noResults.style.display = 'none';
            }
        }
    }
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterRows, 230);
    });
    statusFilter.addEventListener('change', filterRows);
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
        if (e.key === 'Escape') {
            searchInput.value = '';
            statusFilter.value = '';
            filterRows();
            searchInput.blur();
        }
    });
});
</script>
</body>
</html>
