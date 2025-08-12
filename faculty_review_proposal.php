<?php
session_start();
include 'config.php';

// Ensure only faculty can access
if ($_SESSION['role'] != 'faculty') {
    die("Access Denied!");
}

$faculty_id = $_SESSION['user_id'];

// Approve or Reject
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE event_proposals SET status='approved' WHERE proposal_id=$id AND faculty_id=$faculty_id");
} elseif (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE event_proposals SET status='rejected' WHERE proposal_id=$id AND faculty_id=$faculty_id");
}

// Get proposals assigned to this faculty
$sql = "SELECT ep.proposal_id, ep.title, ep.description, ep.proposed_date, ep.venue, ep.status, u.email AS admin_email 
        FROM event_proposals ep
        JOIN users u ON ep.admin_id = u.user_id
        WHERE ep.faculty_id = $faculty_id";
$proposals = $conn->query($sql);

// Count statistics
$stats_sql = "SELECT 
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                COUNT(*) as total_count
              FROM event_proposals WHERE faculty_id = $faculty_id";
$stats = $conn->query($stats_sql)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Event Proposal Review System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',sans-serif;line-height:1.6;color:#333;background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh}
        .container{max-width:1400px;margin:0 auto;padding:20px}
        .header{text-align:center;margin-bottom:30px;color:white}
        .header h1{font-size:2.5rem;margin-bottom:10px;text-shadow:2px 2px 4px rgba(0,0,0,0.3)}
        .header p{font-size:1.1rem;opacity:0.9}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}
        .stat-card{background:white;border-radius:15px;padding:25px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.1);transition:all 0.3s ease}
        .stat-card:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,0.15)}
        .stat-card .icon{font-size:3rem;margin-bottom:15px}
        .stat-card .number{font-size:2.5rem;font-weight:bold;margin-bottom:10px}
        .stat-card .label{font-size:1rem;color:#666;text-transform:uppercase;letter-spacing:1px}
        .stat-total{color:#5a67d8} .stat-pending{color:#f6ad55} .stat-approved{color:#68d391} .stat-rejected{color:#fc8181}
        .main-card{background:white;border-radius:15px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,0.1);overflow:hidden}
        .card-header{display:flex;justify-content:between;align-items:center;margin-bottom:25px;padding-bottom:20px;border-bottom:2px solid #f0f4f8}
        .card-title{color:#5a67d8;font-size:1.8rem;display:flex;align-items:center;gap:10px}
        .filter-tabs{display:flex;gap:10px;margin-bottom:20px}
        .filter-tab{padding:8px 16px;border:none;background:#f0f4f8;color:#666;border-radius:20px;cursor:pointer;transition:all 0.3s;font-size:0.9rem}
        .filter-tab:hover{background:#e2e8f0}
        .filter-tab.active{background:#5a67d8;color:white}
        .table-container{overflow-x:auto;border-radius:10px;box-shadow:0 0 20px rgba(0,0,0,0.05)}
        table{width:100%;border-collapse:collapse;background:white;min-width:800px}
        th{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:18px 15px;text-align:left;font-weight:600;text-transform:uppercase;font-size:0.85rem;letter-spacing:0.5px;position:sticky;top:0;z-index:10}
        td{padding:18px 15px;border-bottom:1px solid #e2e8f0;vertical-align:top}
        tr:hover td{background-color:#f8fafc}
        .proposal-row{transition:all 0.3s}
        .proposal-title{font-weight:600;color:#2d3748;margin-bottom:5px}
        .proposal-description{color:#718096;line-height:1.5;max-width:300px}
        .description-preview{display:block} .description-full{display:none}
        .read-more{color:#5a67d8;cursor:pointer;font-size:0.9rem;text-decoration:underline}
        .date-info,.venue-info,.admin-info{display:flex;align-items:center;gap:8px;color:#4a5568}
        .status-badge{padding:6px 14px;border-radius:20px;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;display:inline-flex;align-items:center;gap:6px}
        .status-pending{background:#fef5e7;color:#d69e2e}
        .status-approved{background:#e6fffa;color:#38a169}
        .status-rejected{background:#fed7d7;color:#e53e3e}
        .action-buttons{display:flex;gap:8px}
        .btn{padding:8px 16px;border:none;border-radius:6px;cursor:pointer;font-size:0.85rem;font-weight:500;text-decoration:none;transition:all 0.3s;display:inline-flex;align-items:center;gap:6px}
        .btn-approve{background:#48bb78;color:white}
        .btn-approve:hover{background:#38a169;transform:translateY(-1px);box-shadow:0 4px 12px rgba(72,187,120,0.4)}
        .btn-reject{background:#f56565;color:white}
        .btn-reject:hover{background:#e53e3e;transform:translateY(-1px);box-shadow:0 4px 12px rgba(245,101,101,0.4)}
        .no-action{color:#a0aec0;font-style:italic;display:flex;align-items:center;gap:6px}
        .empty-state{text-align:center;padding:60px 20px;color:#718096}
        .empty-state i{font-size:4rem;margin-bottom:20px;opacity:0.3}
        .empty-state h3{font-size:1.5rem;margin-bottom:10px;color:#4a5568}
        .loading{display:none;text-align:center;padding:40px;color:#666}
        .loading i{font-size:2rem;animation:spin 1s linear infinite}
        @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
        .modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);animation:fadeIn 0.3s}
        .modal-content{background-color:white;margin:10% auto;padding:30px;border-radius:15px;width:90%;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,0.3);animation:slideIn 0.3s}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes slideIn{from{transform:translateY(-50px);opacity:0}to{transform:translateY(0);opacity:1}}
        .modal h3{color:#2d3748;margin-bottom:15px;display:flex;align-items:center;gap:10px}
        .modal-buttons{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
        .btn-secondary{background:#e2e8f0;color:#4a5568}
        .btn-secondary:hover{background:#cbd5e0}
        @media (max-width:768px){.container{padding:15px}.header h1{font-size:2rem}.stats-grid{grid-template-columns:repeat(2,1fr)}.main-card{padding:20px}.filter-tabs{flex-wrap:wrap}.action-buttons{flex-direction:column}table{font-size:0.9rem}th,td{padding:12px 10px}}
        @media (max-width:480px){.stats-grid{grid-template-columns:1fr}.stat-card{padding:20px}}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clipboard-check"></i> Event Proposal Review</h1>
            <p>Review and manage submitted event proposals</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon stat-total">
                    <i class="fas fa-list-alt"></i>
                </div>
                <div class="number stat-total"><?= $stats['total_count'] ?></div>
                <div class="label">Total Proposals</div>
            </div>
            <div class="stat-card">
                <div class="icon stat-pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="number stat-pending"><?= $stats['pending_count'] ?></div>
                <div class="label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="icon stat-approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="number stat-approved"><?= $stats['approved_count'] ?></div>
                <div class="label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="icon stat-rejected">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="number stat-rejected"><?= $stats['rejected_count'] ?></div>
                <div class="label">Rejected</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-tasks"></i>
                    Proposal Management
                </h2>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterProposals('all')">
                    <i class="fas fa-list"></i> All Proposals
                </button>
                <button class="filter-tab" onclick="filterProposals('pending')">
                    <i class="fas fa-clock"></i> Pending
                </button>
                <button class="filter-tab" onclick="filterProposals('approved')">
                    <i class="fas fa-check"></i> Approved
                </button>
                <button class="filter-tab" onclick="filterProposals('rejected')">
                    <i class="fas fa-times"></i> Rejected
                </button>
            </div>

            <div class="loading" id="loading">
                <i class="fas fa-spinner"></i>
                <p>Processing...</p>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-heading"></i> Title</th>
                            <th><i class="fas fa-align-left"></i> Description</th>
                            <th><i class="fas fa-calendar"></i> Date</th>
                            <th><i class="fas fa-map-marker-alt"></i> Venue</th>
                            <th><i class="fas fa-user"></i> Submitted By</th>
                            <th><i class="fas fa-flag"></i> Status</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($proposals->num_rows > 0): ?>
                            <?php while ($row = $proposals->fetch_assoc()): ?>
                            <tr class="proposal-row" data-status="<?= $row['status'] ?>">
                                <td>
                                    <div class="proposal-title"><?= htmlspecialchars($row['title']) ?></div>
                                </td>
                                <td>
                                    <div class="proposal-description">
                                        <?php 
                                        $description = htmlspecialchars($row['description']);
                                        if (strlen($description) > 100):
                                        ?>
                                            <span class="description-preview">
                                                <?= substr($description, 0, 100) ?>...
                                                <span class="read-more" onclick="toggleDescription(this)">Read more</span>
                                            </span>
                                            <span class="description-full">
                                                <?= $description ?>
                                                <span class="read-more" onclick="toggleDescription(this)">Show less</span>
                                            </span>
                                        <?php else: ?>
                                            <?= $description ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= date('M d, Y', strtotime($row['proposed_date'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="venue-info">
                                        <i class="fas fa-location-arrow"></i>
                                        <?= htmlspecialchars($row['venue']) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="admin-info">
                                        <i class="fas fa-envelope"></i>
                                        <?= htmlspecialchars($row['admin_email']) ?>
                                    </div>
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
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <div class="action-buttons">
                                            <button class="btn btn-approve" onclick="confirmAction('approve', <?= $row['proposal_id'] ?>, '<?= htmlspecialchars($row['title']) ?>')">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-reject" onclick="confirmAction('reject', <?= $row['proposal_id'] ?>, '<?= htmlspecialchars($row['title']) ?>')">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-action">
                                            <i class="fas fa-lock"></i>
                                            No Action Available
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h3>No Proposals Found</h3>
                                    <p>No event proposals have been assigned to you for review yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle"></h3>
            <p id="modalMessage"></p>
            <div class="modal-buttons">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn" id="confirmButton" onclick="executeAction()">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        let currentAction,currentId;
        function filterProposals(status){document.querySelectorAll('.filter-tab').forEach(t=>t.classList.remove('active'));event.target.classList.add('active');document.querySelectorAll('.proposal-row').forEach(r=>r.style.display=status==='all'||r.dataset.status===status?'':'none')}
        function toggleDescription(e){const c=e.closest('.proposal-description'),p=c.querySelector('.description-preview'),f=c.querySelector('.description-full');if(p.style.display!=='none'){p.style.display='none';f.style.display='block'}else{p.style.display='block';f.style.display='none'}}
        function confirmAction(a,i,t){currentAction=a;currentId=i;const m=document.getElementById('confirmModal'),mt=document.getElementById('modalTitle'),mm=document.getElementById('modalMessage'),cb=document.getElementById('confirmButton');if(a==='approve'){mt.innerHTML='<i class="fas fa-check-circle" style="color:#48bb78;"></i> Approve Proposal';mm.textContent=`Are you sure you want to approve "${t}"?`;cb.className='btn btn-approve';cb.innerHTML='<i class="fas fa-check"></i> Approve'}else{mt.innerHTML='<i class="fas fa-times-circle" style="color:#f56565;"></i> Reject Proposal';mm.textContent=`Are you sure you want to reject "${t}"?`;cb.className='btn btn-reject';cb.innerHTML='<i class="fas fa-times"></i> Reject'}m.style.display='block'}
        function closeModal(){document.getElementById('confirmModal').style.display='none';currentAction=currentId=null}
        function executeAction(){if(currentAction&&currentId){document.getElementById('loading').style.display='block';window.location.href=`?${currentAction}=${currentId}`}}
        window.onclick=e=>{if(e.target===document.getElementById('confirmModal'))closeModal()}
        if(window.location.search.includes('approve=')||window.location.search.includes('reject=')){const a=window.location.search.includes('approve=')?'approved':'rejected',m=document.createElement('div');m.className='alert alert-success';m.innerHTML=`<i class="fas fa-check-circle"></i> Proposal has been ${a} successfully!`;m.style.cssText='position:fixed;top:20px;right:20px;background:#d4edda;color:#155724;padding:15px 20px;border-radius:8px;border-left:5px solid #28a745;z-index:1001;animation:slideIn 0.5s';document.body.appendChild(m);setTimeout(()=>{m.style.opacity='0';m.style.transform='translateX(100%)';setTimeout(()=>m.remove(),300)},3000);history.replaceState({},'',window.location.pathname)}
        document.querySelectorAll('.proposal-row').forEach(r=>{r.addEventListener('mouseenter',function(){this.style.transform='scale(1.01)';this.style.boxShadow='0 5px 15px rgba(0,0,0,0.1)'});r.addEventListener('mouseleave',function(){this.style.transform='scale(1)';this.style.boxShadow='none'})})
    </script>
</body>
</html>