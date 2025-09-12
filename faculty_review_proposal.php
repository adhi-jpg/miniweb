<?php
session_start();
include 'config.php';

// Ensure only faculty can access
if ($_SESSION['role'] != 'faculty') {
    die("Access Denied!");
}

$faculty_id = $_SESSION['user_id'];

// Handle feedback update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $proposal_id = intval($_POST['proposal_id']);
    $feedback_text = mysqli_real_escape_string($conn, $_POST['feedback_text']);
    
    $feedback_sql = "UPDATE event_proposals SET feedback='$feedback_text' WHERE proposal_id=$proposal_id AND faculty_id=$faculty_id";
    
    if ($conn->query($feedback_sql)) {
        echo "<script>alert('Feedback submitted successfully!');</script>";
    }
}

// Approve or Reject
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE event_proposals SET status='approved' WHERE proposal_id=$id AND faculty_id=$faculty_id");
} elseif (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    $conn->query("UPDATE event_proposals SET status='rejected' WHERE proposal_id=$id AND faculty_id=$faculty_id");
}

// Get proposals assigned to this faculty
$sql = "SELECT ep.proposal_id, ep.title, ep.description, ep.proposed_date, ep.venue, ep.status, ep.feedback, u.email AS admin_email 
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
        body{font-family:'Segoe UI',sans-serif;line-height:1.6;color:#f5f5f5;background:radial-gradient(ellipse at center, #1a1a1a 0%, #000000 100%);min-height:100vh;overflow-x:hidden}
        body::before{content:'';position:fixed;top:0;left:0;width:100%;height:100%;background:linear-gradient(45deg, rgba(255,215,0,0.02) 0%, rgba(255,215,0,0.05) 50%, rgba(255,215,0,0.02) 100%);pointer-events:none;z-index:1}
        .container{max-width:1400px;margin:0 auto;padding:20px;position:relative;z-index:2}
        .header{text-align:center;margin-bottom:30px;color:#ffd700}
        .header h1{font-size:2.5rem;margin-bottom:10px;text-shadow:0 0 20px rgba(255,215,0,0.3);background:linear-gradient(45deg,#ffd700,#ffed4e);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
        .header p{font-size:1.1rem;opacity:0.9;color:#e5e5e5;text-shadow:0 2px 4px rgba(0,0,0,0.5)}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:30px}
        .stat-card{background:linear-gradient(135deg,#1a1a1a 0%,#2a2a2a 100%);border:1px solid rgba(255,215,0,0.2);border-radius:15px;padding:25px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.5),inset 0 1px 0 rgba(255,215,0,0.1);transition:all 0.3s ease;position:relative;overflow:hidden}
        .stat-card::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(45deg,transparent 30%,rgba(255,215,0,0.1) 50%,transparent 70%);opacity:0;transition:opacity 0.3s ease}
        .stat-card:hover{transform:translateY(-5px);box-shadow:0 20px 40px rgba(0,0,0,0.6),0 0 30px rgba(255,215,0,0.2);border-color:rgba(255,215,0,0.4)}
        .stat-card:hover::before{opacity:1}
        .stat-card .icon{font-size:3rem;margin-bottom:15px;filter:drop-shadow(0 0 10px rgba(255,215,0,0.3))}
        .stat-card .number{font-size:2.5rem;font-weight:bold;margin-bottom:10px;color:#ffd700;text-shadow:0 0 15px rgba(255,215,0,0.5)}
        .stat-card .label{font-size:1rem;color:#b8b8b8;text-transform:uppercase;letter-spacing:1px}
        .stat-total{color:#ffd700} .stat-pending{color:#ffaa00} .stat-approved{color:#32cd32} .stat-rejected{color:#ff6b6b}
        .main-card{background:linear-gradient(135deg,#1a1a1a 0%,#2a2a2a 100%);border:1px solid rgba(255,215,0,0.2);border-radius:15px;padding:30px;box-shadow:0 10px 30px rgba(0,0,0,0.5),inset 0 1px 0 rgba(255,215,0,0.1);overflow:hidden;position:relative}
        .main-card::before{content:'';position:absolute;top:0;right:0;width:200px;height:200px;background:radial-gradient(circle,rgba(255,215,0,0.1) 0%,transparent 70%);border-radius:50%;transform:translate(50%,-50%)}
        .card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:25px;padding-bottom:20px;border-bottom:2px solid rgba(255,215,0,0.2);position:relative;z-index:2}
        .card-title{color:#ffd700;font-size:1.8rem;display:flex;align-items:center;gap:10px;text-shadow:0 0 15px rgba(255,215,0,0.3)}
        .filter-tabs{display:flex;gap:10px;margin-bottom:20px;position:relative;z-index:2}
        .filter-tab{padding:8px 16px;border:1px solid rgba(255,215,0,0.3);background:linear-gradient(135deg,#2a2a2a,#1a1a1a);color:#e5e5e5;border-radius:20px;cursor:pointer;transition:all 0.3s;font-size:0.9rem;position:relative;overflow:hidden}
        .filter-tab::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,215,0,0.2),transparent);transition:left 0.5s}
        .filter-tab:hover{border-color:rgba(255,215,0,0.6);color:#ffd700;box-shadow:0 0 15px rgba(255,215,0,0.2)}
        .filter-tab:hover::before{left:100%}
        .filter-tab.active{background:linear-gradient(135deg,#ffd700,#ffed4e);color:#000;border-color:#ffd700;box-shadow:0 0 20px rgba(255,215,0,0.4);font-weight:600}
        .table-container{overflow-x:auto;border-radius:10px;box-shadow:0 0 20px rgba(0,0,0,0.5);position:relative;z-index:2}
        table{width:100%;border-collapse:collapse;background:linear-gradient(135deg,#1a1a1a 0%,#2a2a2a 100%);min-width:900px;border:1px solid rgba(255,215,0,0.2)}
        th{background:linear-gradient(135deg,#000000 0%,#1a1a1a 50%,#ffd700 100%);color:#000;padding:18px 15px;text-align:left;font-weight:600;text-transform:uppercase;font-size:0.85rem;letter-spacing:0.5px;position:sticky;top:0;z-index:10;border-bottom:2px solid #ffd700;text-shadow:0 1px 2px rgba(255,215,0,0.3)}
        td{padding:18px 15px;border-bottom:1px solid rgba(255,215,0,0.1);vertical-align:top;color:#e5e5e5}
        tr:hover td{background-color:rgba(255,215,0,0.05);border-color:rgba(255,215,0,0.2)}
        .proposal-row{transition:all 0.3s;position:relative}
        .proposal-row::before{content:'';position:absolute;left:0;top:0;width:3px;height:100%;background:linear-gradient(to bottom,transparent,#ffd700,transparent);opacity:0;transition:opacity 0.3s}
        .proposal-row:hover::before{opacity:1}
        .proposal-title{font-weight:600;color:#ffd700;margin-bottom:5px;text-shadow:0 0 10px rgba(255,215,0,0.3)}
        .proposal-description{color:#b8b8b8;line-height:1.5;max-width:300px}
        .description-preview{display:block} .description-full{display:none}
        .read-more{color:#ffd700;cursor:pointer;font-size:0.9rem;text-decoration:underline;transition:color 0.3s}
        .read-more:hover{color:#ffed4e}
        .date-info,.venue-info,.admin-info{display:flex;align-items:center;gap:8px;color:#c5c5c5}
        .date-info i,.venue-info i,.admin-info i{color:#ffd700}
        .status-badge{padding:6px 14px;border-radius:20px;font-size:0.8rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;display:inline-flex;align-items:center;gap:6px;border:1px solid;box-shadow:0 0 15px rgba(0,0,0,0.3)}
        .status-pending{background:linear-gradient(135deg,#2a1f00,#4a3000);color:#ffaa00;border-color:#ffaa00}
        .status-approved{background:linear-gradient(135deg,#002a00,#004a00);color:#32cd32;border-color:#32cd32}
        .status-rejected{background:linear-gradient(135deg,#2a0000,#4a0000);color:#ff6b6b;border-color:#ff6b6b}
        .action-buttons{display:flex;gap:8px}
        .btn{padding:8px 16px;border:1px solid;border-radius:6px;cursor:pointer;font-size:0.85rem;font-weight:500;text-decoration:none;transition:all 0.3s;display:inline-flex;align-items:center;gap:6px;position:relative;overflow:hidden}
        .btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.2),transparent);transition:left 0.5s}
        .btn:hover::before{left:100%}
        .btn-approve{background:linear-gradient(135deg,#32cd32,#228b22);color:white;border-color:#32cd32;box-shadow:0 0 15px rgba(50,205,50,0.3)}
        .btn-approve:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(50,205,50,0.5),0 0 20px rgba(50,205,50,0.4)}
        .btn-reject{background:linear-gradient(135deg,#ff6b6b,#e53e3e);color:white;border-color:#ff6b6b;box-shadow:0 0 15px rgba(255,107,107,0.3)}
        .btn-reject:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(255,107,107,0.5),0 0 20px rgba(255,107,107,0.4)}
        .btn-feedback{background:linear-gradient(135deg,#ffd700,#ffed4e);color:#000;border-color:#ffd700;box-shadow:0 0 15px rgba(255,215,0,0.3);font-weight:600}
        .btn-feedback:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(255,215,0,0.5),0 0 20px rgba(255,215,0,0.4)}
        .no-action{color:#666;font-style:italic;display:flex;align-items:center;gap:6px}
        .empty-state{text-align:center;padding:60px 20px;color:#b8b8b8}
        .empty-state i{font-size:4rem;margin-bottom:20px;opacity:0.3;color:#ffd700}
        .empty-state h3{font-size:1.5rem;margin-bottom:10px;color:#ffd700}
        .loading{display:none;text-align:center;padding:40px;color:#ffd700}
        .loading i{font-size:2rem;animation:spin 1s linear infinite;filter:drop-shadow(0 0 10px rgba(255,215,0,0.5))}
        @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
        .modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.8);animation:fadeIn 0.3s}
        .modal-content{background:linear-gradient(135deg,#1a1a1a 0%,#2a2a2a 100%);margin:10% auto;padding:30px;border-radius:15px;width:90%;max-width:500px;box-shadow:0 20px 60px rgba(0,0,0,0.8),0 0 40px rgba(255,215,0,0.2);animation:slideIn 0.3s;border:1px solid rgba(255,215,0,0.3);position:relative}
        .modal-content::before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background:linear-gradient(45deg,transparent 30%,rgba(255,215,0,0.05) 50%,transparent 70%);border-radius:15px}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes slideIn{from{transform:translateY(-50px);opacity:0}to{transform:translateY(0);opacity:1}}
        .modal h3{color:#ffd700;margin-bottom:15px;display:flex;align-items:center;gap:10px;text-shadow:0 0 15px rgba(255,215,0,0.3);position:relative;z-index:2}
        .modal p{color:#e5e5e5;position:relative;z-index:2}
        .modal-buttons{display:flex;gap:10px;justify-content:flex-end;margin-top:20px;position:relative;z-index:2}
        .btn-secondary{background:linear-gradient(135deg,#2a2a2a,#1a1a1a);color:#e5e5e5;border-color:rgba(255,215,0,0.3)}
        .btn-secondary:hover{border-color:rgba(255,215,0,0.6);color:#ffd700}
        .form-group{margin-bottom:20px;position:relative;z-index:2}
        .form-label{display:block;margin-bottom:8px;font-weight:600;color:#ffd700;text-shadow:0 0 10px rgba(255,215,0,0.3)}
        .form-textarea{width:100%;padding:12px;border:2px solid rgba(255,215,0,0.3);border-radius:8px;font-family:inherit;font-size:0.9rem;resize:vertical;min-height:100px;background:linear-gradient(135deg,#2a2a2a,#1a1a1a);color:#e5e5e5;box-shadow:inset 0 2px 5px rgba(0,0,0,0.3)}
        .form-textarea:focus{outline:none;border-color:#ffd700;box-shadow:0 0 0 3px rgba(255,215,0,0.2),inset 0 2px 5px rgba(0,0,0,0.3)}
        .form-textarea::placeholder{color:#888}
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
                                    <button class="btn btn-feedback" onclick="openFeedbackModal(<?= $row['proposal_id'] ?>, '<?= htmlspecialchars($row['title']) ?>', '<?= htmlspecialchars($row['feedback'] ?? '') ?>')">
                                        <i class="fas fa-comment"></i> <?= !empty($row['feedback']) ? 'Edit Feedback' : 'Add Feedback' ?>
                                    </button>
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

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-comment"></i> Add Feedback</h3>
            <form method="POST">
                <input type="hidden" name="proposal_id" id="feedbackProposalId">
                <input type="hidden" name="submit_feedback" value="1">
                
                <div class="form-group">
                    <label class="form-label">Your Feedback:</label>
                    <textarea name="feedback_text" id="feedbackText" class="form-textarea" placeholder="Enter your feedback here..." required></textarea>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn btn-secondary" onclick="closeFeedbackModal()">Cancel</button>
                    <button type="submit" class="btn btn-feedback">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentAction,currentId;
        function filterProposals(status){document.querySelectorAll('.filter-tab').forEach(t=>t.classList.remove('active'));event.target.classList.add('active');document.querySelectorAll('.proposal-row').forEach(r=>r.style.display=status==='all'||r.dataset.status===status?'':'none')}
        function toggleDescription(e){const c=e.closest('.proposal-description'),p=c.querySelector('.description-preview'),f=c.querySelector('.description-full');if(p.style.display!=='none'){p.style.display='none';f.style.display='block'}else{p.style.display='block';f.style.display='none'}}
        function confirmAction(a,i,t){currentAction=a;currentId=i;const m=document.getElementById('confirmModal'),mt=document.getElementById('modalTitle'),mm=document.getElementById('modalMessage'),cb=document.getElementById('confirmButton');if(a==='approve'){mt.innerHTML='<i class="fas fa-check-circle" style="color:#32cd32;"></i> Approve Proposal';mm.textContent=`Are you sure you want to approve "${t}"?`;cb.className='btn btn-approve';cb.innerHTML='<i class="fas fa-check"></i> Approve'}else{mt.innerHTML='<i class="fas fa-times-circle" style="color:#ff6b6b;"></i> Reject Proposal';mm.textContent=`Are you sure you want to reject "${t}"?`;cb.className='btn btn-reject';cb.innerHTML='<i class="fas fa-times"></i> Reject'}m.style.display='block'}
        function closeModal(){document.getElementById('confirmModal').style.display='none';currentAction=currentId=null}
        function executeAction(){if(currentAction&&currentId){document.getElementById('loading').style.display='block';window.location.href=`?${currentAction}=${currentId}`}}
        function openFeedbackModal(proposalId, title, existingFeedback){document.getElementById('feedbackProposalId').value=proposalId;document.querySelector('#feedbackModal h3').innerHTML=`<i class="fas fa-comment"></i> Feedback for "${title}"`;document.getElementById('feedbackText').value=existingFeedback||'';document.getElementById('feedbackModal').style.display='block'}
        function closeFeedbackModal(){document.getElementById('feedbackModal').style.display='none'}
        window.onclick=e=>{if(e.target===document.getElementById('confirmModal'))closeModal();if(e.target===document.getElementById('feedbackModal'))closeFeedbackModal()}
        if(window.location.search.includes('approve=')||window.location.search.includes('reject=')){const a=window.location.search.includes('approve=')?'approved':'rejected',m=document.createElement('div');m.className='alert alert-success';m.innerHTML=`<i class="fas fa-check-circle"></i> Proposal has been ${a} successfully!`;m.style.cssText='position:fixed;top:20px;right:20px;background:linear-gradient(135deg,#1a4a00,#2a6a00);color:#32cd32;padding:15px 20px;border-radius:8px;border:1px solid #32cd32;box-shadow:0 0 20px rgba(50,205,50,0.3);z-index:1001;animation:slideIn 0.5s';document.body.appendChild(m);setTimeout(()=>{m.style.opacity='0';m.style.transform='translateX(100%)';setTimeout(()=>m.remove(),300)},3000);history.replaceState({},'',window.location.pathname)}
        document.querySelectorAll('.proposal-row').forEach(r=>{r.addEventListener('mouseenter',function(){this.style.transform='scale(1.01)';this.style.boxShadow='0 5px 15px rgba(255,215,0,0.2)'});r.addEventListener('mouseleave',function(){this.style.transform='scale(1)';this.style.boxShadow='none'})})
    </script>
</body>
</html>