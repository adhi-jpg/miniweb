<?php
session_start();
include "config.php";

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

// Handle form submissions (Update event)
if (isset($_POST['update'])) {
    $id = (int) $_POST['event_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $description = $conn->real_escape_string($_POST['description']);
    $date = $conn->real_escape_string($_POST['date']);
    $venue = $conn->real_escape_string($_POST['venue']);
    $max_participants = (int) $_POST['max_participants'];

    $update = "UPDATE events 
               SET title='$title', description='$description', date='$date', 
                   venue='$venue', max_participants=$max_participants 
               WHERE event_id=$id";
    $msg = $conn->query($update) ? "✅ Event updated successfully." : "❌ Update failed: " . $conn->error;
}

// Handle event deletion (delete participants first)
if (isset($_GET['delete'])) {
    $event_id = (int) $_GET['delete'];

    // Delete all participations for this event
    $conn->query("DELETE FROM event_participation WHERE event_id = $event_id");

    // Now delete the event
    if ($conn->query("DELETE FROM events WHERE event_id = $event_id")) {
        $msg = "✅ Event deleted successfully.";
    } else {
        $msg = "❌ Failed to delete event: " . $conn->error;
    }

    header("Location: manage_events.php?msg=" . urlencode($msg));
    exit();
}

// Fetch events
$events = $conn->query("SELECT * FROM events ORDER BY date DESC");

// If redirected after delete, get message
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events – MDC Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0c1445 0%, #1e3a8a 25%, #1e40af 50%, #1d4ed8 75%, #2563eb 100%);
            min-height: 100vh; padding: 20px; color: #ffffff; overflow-x: hidden; position: relative;
        }

        body::before {
            content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 25% 25%, rgba(59, 130, 246, 0.15) 0%, transparent 60%),
                        radial-gradient(circle at 75% 75%, rgba(147, 197, 253, 0.12) 0%, transparent 60%),
                        radial-gradient(circle at 50% 50%, rgba(96, 165, 250, 0.08) 0%, transparent 70%);
            animation: backgroundPulse 8s ease-in-out infinite alternate;
            pointer-events: none; z-index: -1;
        }

        @keyframes backgroundPulse {
            0% { opacity: 0.4; transform: scale(1); }
            100% { opacity: 0.7; transform: scale(1.1); }
        }

        .container {
            max-width: 1400px; margin: 0 auto;
            background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(20px);
            border-radius: 24px; padding: 50px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), 0 0 100px rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(147, 197, 253, 0.2); position: relative;
            animation: containerFadeIn 1s ease-out; overflow: hidden;
        }

        .container::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(59, 130, 246, 0.12), transparent, rgba(147, 197, 253, 0.08), transparent);
            animation: rotate 20s linear infinite; opacity: 0.4;
        }

        .container::after {
            content: ''; position: absolute; inset: 1px;
            background: rgba(15, 23, 42, 0.95); border-radius: 23px; backdrop-filter: blur(20px);
        }

        @keyframes rotate { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        @keyframes containerFadeIn {
            from { opacity: 0; transform: translateY(50px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        h2 {
            text-align: center; margin-bottom: 50px; font-size: 3rem; font-weight: 800;
            background: linear-gradient(45deg, #3b82f6, #60a5fa, #93c5fd, #1d4ed8);
            background-size: 400% 400%; -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text; position: relative; z-index: 10;
            animation: gradientShift 4s ease-in-out infinite alternate, titleFloat 3s ease-in-out infinite alternate;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        @keyframes titleFloat {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-10px); }
        }

        h2::after {
            content: ''; position: absolute; bottom: -15px; left: 50%; transform: translateX(-50%);
            width: 120px; height: 4px; background: linear-gradient(45deg, #3b82f6, #60a5fa);
            border-radius: 2px; animation: underlineGlow 2s ease-in-out infinite alternate;
        }

        @keyframes underlineGlow {
            0% { box-shadow: 0 0 10px rgba(59, 130, 246, 0.6); }
            100% { box-shadow: 0 0 20px rgba(96, 165, 250, 0.8); }
        }

        .msg {
            text-align: center; margin-bottom: 30px; padding: 18px 30px; border-radius: 16px;
            font-weight: 600; font-size: 1.1rem; position: relative; z-index: 10;
            animation: messageSlide 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            backdrop-filter: blur(10px);
        }

        @keyframes messageSlide {
            0% { opacity: 0; transform: translateX(-100px) rotateY(90deg); }
            100% { opacity: 1; transform: translateX(0) rotateY(0deg); }
        }

        .msg:not(.error) {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.08));
            border: 2px solid rgba(34, 197, 94, 0.4); color: #22c55e;
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.2);
            animation: messageSlide 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55), successPulse 2s ease-in-out infinite alternate;
        }

        @keyframes successPulse {
            0% { border-color: rgba(34, 197, 94, 0.4); }
            100% { border-color: rgba(34, 197, 94, 0.8); }
        }

        .msg.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.08));
            border: 2px solid rgba(239, 68, 68, 0.4); color: #ef4444;
            box-shadow: 0 10px 30px rgba(239, 68, 68, 0.2);
            animation: messageSlide 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55), errorShake 0.5s ease-in-out;
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .table-container {
            overflow-x: auto; border-radius: 20px; background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(148, 163, 184, 0.2); box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            position: relative; z-index: 10; animation: tableSlideUp 1s ease-out 0.3s both;
        }

        @keyframes tableSlideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        table { width: 100%; border-collapse: collapse; background: transparent; }

        th, td {
            padding: 25px 20px; text-align: left; border-bottom: 1px solid rgba(148, 163, 184, 0.15);
            vertical-align: middle;
        }

        th {
            background: linear-gradient(135deg, #334155, #475569); color: #f1f5f9;
            font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;
            position: sticky; top: 0; z-index: 100;
            animation: headerGlow 3s ease-in-out infinite alternate;
        }

        @keyframes headerGlow {
            0% { box-shadow: inset 0 0 0 rgba(59, 130, 246, 0.2); }
            100% { box-shadow: inset 0 -2px 10px rgba(59, 130, 246, 0.3); }
        }

        tr {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); position: relative;
            animation: rowFadeIn 0.6s ease-out var(--delay, 0s) both;
        }

        tr:nth-child(1) { --delay: 0.1s; }
        tr:nth-child(2) { --delay: 0.2s; }
        tr:nth-child(3) { --delay: 0.3s; }
        tr:nth-child(4) { --delay: 0.4s; }
        tr:nth-child(5) { --delay: 0.5s; }

        @keyframes rowFadeIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        tr:hover {
            background: rgba(59, 130, 246, 0.08); transform: translateY(-3px) scale(1.01);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 20px rgba(59, 130, 246, 0.25);
        }

        tr:hover::before {
            content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px;
            background: linear-gradient(45deg, #3b82f6, #60a5fa); border-radius: 0 3px 3px 0;
            animation: borderGlow 1s ease-in-out infinite alternate;
        }

        @keyframes borderGlow {
            0% { box-shadow: 0 0 5px rgba(59, 130, 246, 0.6); }
            100% { box-shadow: 0 0 15px rgba(96, 165, 250, 0.8); }
        }

        input, textarea {
            width: 100%; padding: 15px 20px; border-radius: 12px;
            border: 2px solid rgba(148, 163, 184, 0.2); font-size: 14px;
            background: rgba(51, 65, 85, 0.8); color: #f8fafc;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); font-family: inherit;
        }

        input:focus, textarea:focus {
            outline: none; border-color: #3b82f6;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4), inset 0 0 10px rgba(59, 130, 246, 0.1);
            background: rgba(71, 85, 105, 0.9); transform: scale(1.02);
        }

        input::placeholder, textarea::placeholder { color: rgba(203, 213, 225, 0.6); }

        textarea { resize: vertical; min-height: 70px; max-height: 140px; }

        .action-btn {
            padding: 12px 24px; font-size: 13px; text-decoration: none; color: white;
            border-radius: 10px; margin-right: 10px; border: none; cursor: pointer;
            font-weight: 600; transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex; align-items: center; gap: 8px;
            text-transform: uppercase; letter-spacing: 0.5px; position: relative;
            overflow: hidden; animation: buttonFloat 2s ease-in-out infinite alternate;
        }

        @keyframes buttonFloat {
            0% { transform: translateY(0px); }
            100% { transform: translateY(-2px); }
        }

        .action-btn::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .action-btn:hover::before { left: 100%; }

        .action-btn:not(.delete-btn) {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .action-btn:not(.delete-btn):hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 35px rgba(59, 130, 246, 0.6);
            background: linear-gradient(135deg, #60a5fa, #2563eb);
        }

        .delete-btn {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
        }

        .delete-btn:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 35px rgba(220, 38, 38, 0.6);
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .actions-cell { white-space: nowrap; }

        ::-webkit-scrollbar { width: 12px; height: 12px; }
        ::-webkit-scrollbar-track { background: rgba(148, 163, 184, 0.15); border-radius: 6px; }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(45deg, #3b82f6, #60a5fa); border-radius: 6px;
            border: 2px solid rgba(15, 23, 42, 0.5);
        }
        ::-webkit-scrollbar-thumb:hover { background: linear-gradient(45deg, #2563eb, #93c5fd); }

        .particle {
            position: fixed; pointer-events: none; width: 4px; height: 4px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.8), transparent);
            border-radius: 50%; animation: particleFloat 6s linear infinite;
        }

        @keyframes particleFloat {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) rotate(360deg); opacity: 0; }
        }

        .loading::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.25), transparent);
            animation: loadingShimmer 2s infinite;
        }

        @keyframes loadingShimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        @media (max-width: 768px) {
            .container { padding: 30px 20px; margin: 10px; }
            h2 { font-size: 2.2rem; }
            th, td { padding: 15px 10px; font-size: 0.9rem; }
            .action-btn { padding: 10px 16px; font-size: 12px; margin-right: 6px; }
            input, textarea { padding: 12px 16px; font-size: 13px; }
        }

        .action-btn:focus, input:focus, textarea:focus {
            outline: 2px solid #3b82f6; outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>📋 Edit / Delete Events</h2>
        
        <?php if ($msg): ?>
            <div class="msg <?= str_contains($msg, '❌') ? 'error' : '' ?>"><?= $msg ?></div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>📝 Title</th>
                        <th>📄 Description</th>
                        <th>📅 Date</th>
                        <th>📍 Venue</th>
                        <th>👥 Max Participants</th>
                        <th>⚡ Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $events->fetch_assoc()): ?>
                    <tr>
                        <form method="POST">
                            <td>
                                <input type="text" name="title" value="<?= htmlspecialchars($row['title']) ?>" placeholder="Event title" required>
                            </td>
                            <td>
                                <textarea name="description" placeholder="Event description" required><?= htmlspecialchars($row['description']) ?></textarea>
                            </td>
                            <td>
                                <input type="date" name="date" value="<?= $row['date'] ?>" required>
                            </td>
                            <td>
                                <input type="text" name="venue" value="<?= htmlspecialchars($row['venue']) ?>" placeholder="Event venue" required>
                            </td>
                            <td>
                                <input type="number" name="max_participants" value="<?= $row['max_participants'] ?>" min="1" max="10000" placeholder="Max participants" required>
                            </td>
                            <td class="actions-cell">
                                <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                                <button name="update" class="action-btn" type="submit">
                                    💾 Update
                                </button>
                                <a class="action-btn delete-btn" 
                                   href="?delete=<?= $row['event_id'] ?>" 
                                   onclick="return confirm('⚠️ Are you absolutely sure you want to delete this event?\n\nThis action cannot be undone!')">
                                    🗑️ Delete
                                </a>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function createParticles() {
            for (let i = 0; i < 15; i++) {
                setTimeout(() => {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.animationDelay = Math.random() * 6 + 's';
                    particle.style.animationDuration = (Math.random() * 3 + 6) + 's';
                    document.body.appendChild(particle);
                    setTimeout(() => particle.remove(), 9000);
                }, i * 400);
            }
        }

        document.querySelectorAll('input, textarea').forEach(element => {
            element.addEventListener('focus', function() {
                this.closest('tr').style.transform = 'translateY(-2px) scale(1.005)';
                this.closest('tr').style.boxShadow = '0 10px 30px rgba(59, 130, 246, 0.25)';
            });
            
            element.addEventListener('blur', function() {
                this.closest('tr').style.transform = '';
                this.closest('tr').style.boxShadow = '';
            });

            element.addEventListener('input', function() {
                this.style.borderColor = 'rgba(96, 165, 250, 0.8)';
                setTimeout(() => this.style.borderColor = 'rgba(148, 163, 184, 0.2)', 2000);
            });
        });

        document.querySelectorAll('.action-btn').forEach(button => {
            button.addEventListener('mouseenter', function() {
                if (this.classList.contains('delete-btn')) {
                    this.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                } else {
                    this.style.background = 'linear-gradient(135deg, #60a5fa, #2563eb)';
                }
            });
            
            button.addEventListener('mouseleave', function() {
                if (this.classList.contains('delete-btn')) {
                    this.style.background = 'linear-gradient(135deg, #dc2626, #b91c1c)';
                } else {
                    this.style.background = 'linear-gradient(135deg, #3b82f6, #1d4ed8)';
                }
            });
        });

        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.zIndex = '50';
                this.style.position = 'relative';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.zIndex = '';
                this.style.position = '';
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const eventTitle = this.closest('tr').querySelector('input[name="title"]').value;
                
                if (confirm(`🚨 DANGER ZONE 🚨\n\nYou are about to permanently delete:\n"${eventTitle}"\n\nThis action cannot be undone!\n\nAre you absolutely sure?`)) {
                    this.innerHTML = '⏳ Deleting...';
                    this.style.pointerEvents = 'none';
                    window.location.href = this.href;
                }
            });
        });

        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = this.querySelector('button[name="update"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '⏳ Saving...';
                    submitBtn.style.pointerEvents = 'none';
                    submitBtn.classList.add('loading');
                }
            });
        });

        createParticles();
        setInterval(createParticles, 12000);
    </script>
</body>
</html>