<?php
session_start();

// Cache prevention & session validation
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student" || empty($_SESSION["user_id"])) {
    session_unset();
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
    header("Location: login.php");
    exit();
}

include "config.php";
$user_id = $_SESSION["user_id"];

// Get student profile
$stmt = $conn->prepare("SELECT name, roll_number, department, phone FROM student_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc() ?: [
    'name' => 'Profile Not Found', 'roll_number' => 'N/A', 'department' => 'N/A', 'phone' => 'N/A'
];
$stmt->close();

// Get statistics
$queries = [
    'events_joined' => "SELECT COUNT(*) as count FROM event_participation WHERE user_id = ?",
    'certificates' => "SELECT COUNT(*) as count FROM event_participation WHERE user_id = ? AND status = 'confirmed'",
    'attendance' => "SELECT COUNT(*) as total_events, SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count FROM attendance WHERE user_id = ?",
    'notifications' => "SELECT COUNT(*) as count FROM announcements WHERE posted_on >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
];

$stats = [];
foreach ($queries as $key => $query) {
    $stmt = $conn->prepare($query);
    if ($key !== 'notifications') $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats[$key] = $key === 'attendance' ? 
        ($result['total_events'] > 0 ? round(($result['present_count'] / $result['total_events']) * 100) : 0) :
        $result['count'];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard – MDC Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #dc2626; --secondary: #ef4444; --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --glass: rgba(255, 255, 255, 0.1); --glass-strong: rgba(255, 255, 255, 0.9); --border: rgba(255, 255, 255, 0.2);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1); --shadow-strong: 0 20px 40px rgba(0, 0, 0, 0.15); --border-radius: 20px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #fca5a5 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh; overflow-x: hidden;
        }

        @keyframes gradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

        body::before {
            content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: 
                radial-gradient(circle at 20% 80%, rgba(220, 38, 38, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(239, 68, 68, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(252, 165, 165, 0.2) 0%, transparent 50%);
        }

        .profile-sidebar {
            position: fixed; top: 0; right: 0; width: 350px; height: 100vh; z-index: 1000; padding: 30px; overflow-y: auto;
            background: var(--glass); backdrop-filter: blur(20px); border-left: 1px solid var(--border);
            transform: translateX(100%); transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .profile-sidebar.open { transform: translateX(0); }

        .profile-toggle {
            position: fixed; top: 30px; right: 30px; width: 60px; height: 60px; z-index: 1001; cursor: pointer;
            background: var(--glass); backdrop-filter: blur(20px); border: 1px solid var(--border); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;
            transition: all 0.3s ease; box-shadow: var(--shadow);
        }
        .profile-toggle:hover { transform: scale(1.1); background: rgba(255, 255, 255, 0.2); }

        .profile-header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
        .profile-avatar {
            width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; font-size: 32px; color: white; box-shadow: var(--shadow);
            background: linear-gradient(135deg, var(--primary), var(--secondary)); display: flex; align-items: center; justify-content: center;
        }
        .profile-info h3 { color: white; font-size: 20px; font-weight: 700; margin-bottom: 8px; }
        .profile-info p { color: rgba(255, 255, 255, 0.7); font-size: 14px; margin-bottom: 4px; }

        .profile-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
        .stat-item {
            background: rgba(255, 255, 255, 0.1); padding: 20px 15px; border-radius: 15px; text-align: center;
            transition: all 0.3s ease;
        }
        .stat-item:hover { background: rgba(255, 255, 255, 0.15); transform: translateY(-5px); }
        .stat-number { font-size: 24px; font-weight: 800; color: white; margin-bottom: 5px; }
        .stat-label { font-size: 12px; color: rgba(255, 255, 255, 0.7); text-transform: uppercase; letter-spacing: 0.5px; }

        .profile-actions { display: flex; flex-direction: column; gap: 15px; }
        .profile-btn {
            display: flex; align-items: center; gap: 12px; padding: 15px 20px; border-radius: 12px; color: white; text-decoration: none; font-weight: 500;
            background: rgba(255, 255, 255, 0.1); border: 1px solid var(--border); transition: all 0.3s ease;
        }
        .profile-btn:hover { background: rgba(255, 255, 255, 0.2); transform: translateX(5px); }
        .profile-btn i { font-size: 18px; }

        header {
            background: var(--glass); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border); color: white; padding: 25px 40px;
            display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow);
        }
        header h1 { font-size: 28px; font-weight: 700; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3); animation: slideInLeft 0.8s ease-out; }
        .logout a {
            background: linear-gradient(135deg, #ffd700, #ffed4a); color: #333; text-decoration: none; padding: 12px 24px; border-radius: 50px;
            font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3); animation: slideInRight 0.8s ease-out;
        }
        .logout a:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4); }

        .announcement-banner {
            background: var(--glass-strong); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border); overflow: hidden;
            height: 70px; display: flex; align-items: center; box-shadow: var(--shadow); position: relative;
        }
        .announcement-banner::before {
            content: '📢'; position: absolute; left: 25px; font-size: 28px; z-index: 2;
            background: var(--glass-strong); padding: 0 15px; border-radius: 50%;
        }
        .banner-content { display: flex; align-items: center; white-space: nowrap; animation: scroll 35s linear infinite; padding-left: 100px; }
        .banner-item { margin-right: 120px; color: #333; font-weight: 500; font-size: 16px; }
        .banner-item strong { color: var(--primary); margin-right: 15px; font-weight: 700; }
        @keyframes scroll { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }

        .main-content { transition: padding-right 0.4s ease; }
        .main-content.with-sidebar { padding-right: 350px; }
        .container { padding: 50px 40px; max-width: 1400px; margin: 0 auto; }

        .section { margin-bottom: 50px; animation: fadeInUp 0.8s ease-out forwards; opacity: 0; transform: translateY(30px); }
        .section:nth-child(1) { animation-delay: 0.3s; }
        .section h2 {
            margin-bottom: 30px; color: white; font-size: 32px; font-weight: 800; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            position: relative; padding-left: 25px;
        }
        .section h2::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 6px; height: 45px; border-radius: 3px;
            background: linear-gradient(135deg, #ffd700, #ffed4a); box-shadow: 0 2px 15px rgba(255, 215, 0, 0.4);
        }

        .card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        .card {
            background: var(--glass-strong); backdrop-filter: blur(20px); border: 1px solid var(--border); padding: 35px;
            border-radius: var(--border-radius); box-shadow: var(--shadow); position: relative; overflow: hidden; text-decoration: none; color: inherit;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .card::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; transform: scaleX(0); transition: transform 0.3s ease;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }
        .card::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0; transition: opacity 0.3s ease;
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), transparent);
        }
        .card:hover {
            transform: translateY(-15px) scale(1.02); box-shadow: var(--shadow-strong); background: rgba(255, 255, 255, 0.95);
        }
        .card:hover::before { transform: scaleX(1); }
        .card:hover::after { opacity: 1; }
        .card h3 {
            font-size: 22px; font-weight: 700; color: #333; margin-bottom: 15px; position: relative; z-index: 1;
            display: flex; align-items: center; gap: 12px;
        }
        .card p { color: #666; line-height: 1.6; font-size: 15px; position: relative; z-index: 1; }

        .particle {
            position: fixed; width: 6px; height: 6px; background: rgba(255, 255, 255, 0.6); border-radius: 50%;
            pointer-events: none; animation: float 8s infinite linear; z-index: -1;
        }
        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10%, 90% { opacity: 1; }
            100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; }
        }

        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInLeft { from { opacity: 0; transform: translateX(-50px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(50px); } to { opacity: 1; transform: translateX(0); } }

        @media (max-width: 768px) {
            .profile-sidebar { width: 100%; padding: 20px; }
            .profile-toggle { top: 20px; right: 20px; width: 50px; height: 50px; font-size: 20px; }
            .main-content.with-sidebar { padding-right: 0; }
            .container { padding: 30px 20px; }
            header { padding: 20px; flex-direction: column; gap: 15px; text-align: center; }
            header h1 { font-size: 24px; }
            .section h2 { font-size: 28px; }
            .card-grid { grid-template-columns: 1fr; }
            .announcement-banner { height: 60px; }
            .banner-content { padding-left: 80px; }
            .banner-item { font-size: 14px; margin-right: 80px; }
            .card { padding: 25px; }
        }
    </style>
</head>
<body>

<div class="profile-toggle" onclick="toggleProfile()"><i class="fas fa-user"></i></div>

<div class="profile-sidebar" id="profileSidebar">
    <div class="profile-header">
        <div class="profile-avatar"><i class="fas fa-user-graduate"></i></div>
        <div class="profile-info">
            <h3><?= htmlspecialchars($student['name']) ?></h3>
            <p><i class="fas fa-id-badge"></i> <?= htmlspecialchars($student['roll_number']) ?></p>
            <p><i class="fas fa-building"></i> <?= htmlspecialchars($student['department']) ?></p>
            <?php if (!empty($student['phone'])): ?>
            <p><i class="fas fa-phone"></i> <?= htmlspecialchars($student['phone']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-stats">
        <div class="stat-item"><div class="stat-number"><?= $stats['events_joined'] ?></div><div class="stat-label">Events Joined</div></div>
        <div class="stat-item"><div class="stat-number"><?= $stats['certificates'] ?></div><div class="stat-label">Certificates</div></div>
        <div class="stat-item"><div class="stat-number"><?= $stats['attendance'] ?>%</div><div class="stat-label">Attendance</div></div>
        <div class="stat-item"><div class="stat-number"><?= $stats['notifications'] ?></div><div class="stat-label">New Updates</div></div>
    </div>
</div>

<div class="main-content" id="mainContent">

<header>
    <h1>Welcome, <?= htmlspecialchars($student['name']) ?></h1>
    <div class="logout"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
</header>

<div class="announcement-banner">
    <div class="banner-content">
        <?php
        $announcements = $conn->query("SELECT * FROM announcements ORDER BY posted_on DESC LIMIT 5");
        if ($announcements->num_rows > 0) {
            while ($a = $announcements->fetch_assoc()) {
                echo "<div class='banner-item'><strong>" . date("d M Y", strtotime($a["posted_on"])) . "</strong> " . htmlspecialchars($a["message"]) . "</div>";
            }
        } else {
            echo "<div class='banner-item'>No announcements available.</div>";
        }
        ?>
    </div>
</div>

<div class="container">
    <div class="section">
        <h2>🎯 Quick Access</h2>
        <div class="card-grid">
            <a href="events_student.php" class="card">
                <h3><i class="fas fa-calendar-alt"></i> My Events</h3>
                <p>View upcoming and registered events.</p>
            </a>
            <a href="student_participation_history.php" class="card">
                <h3><i class="fas fa-history"></i> My Participation</h3>
                <p>Track your event history and approvals.</p>
            </a>
            <a href="student_attendance.php" class="card">
                <h3><i class="fas fa-user-check"></i> Attendance</h3>
                <p>View the attendance.</p>
            </a>
            <a href="view_media_gallery.php" class="card">
                <h3><i class="fas fa-images"></i> Gallery</h3>
                <p>View the gallery.</p>
            </a>
            <a href="student_buy_merchandise.php" class="card">
                <h3><i class="fas fa-shopping-cart"></i> MDC Products</h3>
                <p>To buy MDC products for further programs.</p>
            </a>
        </div>
    </div>
</div>

</div>

<script>
function toggleProfile() {
    const sidebar = document.getElementById('profileSidebar');
    const mainContent = document.getElementById('mainContent');
    const isOpen = sidebar.classList.contains('open');
    
    if (isOpen) {
        sidebar.classList.remove('open');
        mainContent.classList.remove('with-sidebar');
    } else {
        sidebar.classList.add('open');
        mainContent.classList.add('with-sidebar');
    }
}

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('profileSidebar');
    const toggle = document.querySelector('.profile-toggle');
    const mainContent = document.getElementById('mainContent');
    
    if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
        sidebar.classList.remove('open');
        mainContent.classList.remove('with-sidebar');
    }
});

function createParticle() {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = Math.random() * 100 + 'vw';
    particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
    particle.style.animationDelay = Math.random() * 2 + 's';
    document.body.appendChild(particle);
    
    setTimeout(() => particle.remove(), 10000);
}

setInterval(createParticle, 400);

if (performance.navigation.type === 2) {
    location.href = "logout.php";
}
</script>

</body>
</html>