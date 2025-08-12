<?php
session_start();
include "config.php";

// ✅ Only allow logged-in students
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$msg = "";

// ✅ Handle event registration
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['event_id'])) {
    $event_id = (int) $_POST['event_id'];

    // Check if already registered
    $check_stmt = $conn->prepare("SELECT * FROM event_participation WHERE user_id = ? AND event_id = ?");
    $check_stmt->bind_param("ii", $user_id, $event_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        // Check if event has available spots (including pending registrations)
        $capacity_stmt = $conn->prepare("
            SELECT e.max_participants, 
                   COUNT(ep.participation_id) as total_registrations
            FROM events e 
            LEFT JOIN event_participation ep ON e.event_id = ep.event_id 
                AND ep.status IN ('approved', 'pending')
            WHERE e.event_id = ?
            GROUP BY e.event_id, e.max_participants
        ");
        $capacity_stmt->bind_param("i", $event_id);
        $capacity_stmt->execute();
        $capacity_result = $capacity_stmt->get_result();
        $capacity_data = $capacity_result->fetch_assoc();
        
        $remaining_spots = $capacity_data['max_participants'] - $capacity_data['total_registrations'];
        
        if ($remaining_spots > 0) {
            $insert_stmt = $conn->prepare("INSERT INTO event_participation (event_id, user_id, status) VALUES (?, ?, 'pending')");
            $insert_stmt->bind_param("ii", $event_id, $user_id);
            
            if ($insert_stmt->execute()) {
                $msg = "✅ Registration submitted! Waiting for approval.";
            } else {
                $msg = "❌ Error registering.";
            }
        } else {
            $msg = "❌ Sorry, this event is fully booked.";
        }
    } else {
        $msg = "⚠️ You've already registered for this event.";
    }
}

// ✅ Fetch upcoming events
$today = date('Y-m-d');
$events_stmt = $conn->prepare("SELECT * FROM events WHERE date >= ? ORDER BY date ASC");
$events_stmt->bind_param("s", $today);
$events_stmt->execute();
$events = $events_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Events – MDC Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #fca5a5 100%);
            min-height: 100vh; padding: 20px;
        }

        .container { max-width: 1200px; margin: 0 auto; animation: fadeInUp 0.8s ease-out; }

        .header {
            text-align: center; margin-bottom: 50px; color: white;
        }
        .header h1 { font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 700; margin-bottom: 15px; text-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .header .subtitle { font-size: 1.2rem; opacity: 0.9; margin-bottom: 30px; }
        .header .date-badge {
            display: inline-block; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
            padding: 8px 20px; border-radius: 25px; border: 1px solid rgba(255,255,255,0.3);
        }

        .msg {
            text-align: center; margin-bottom: 30px; padding: 15px 25px; border-radius: 12px; font-weight: 600;
            backdrop-filter: blur(10px); animation: slideInDown 0.6s ease-out;
        }
        .msg.success { background: rgba(34,197,94,0.2); color: #dcfce7; border: 1px solid rgba(34,197,94,0.3); }
        .msg.warning { background: rgba(239,68,68,0.2); color: #fecaca; border: 1px solid rgba(239,68,68,0.3); }

        .events-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 30px; margin-top: 40px; }

        .event-card {
            background: rgba(255,255,255,0.95); backdrop-filter: blur(20px); border-radius: 20px; padding: 30px;
            position: relative; overflow: hidden; border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.4s ease; box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        .event-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
            background: linear-gradient(90deg, #dc2626, #991b1b); opacity: 0; transition: opacity 0.3s ease;
        }
        .event-card:hover { transform: translateY(-8px); box-shadow: 0 20px 60px rgba(0,0,0,0.2); }
        .event-card:hover::before { opacity: 1; }

        .event-title { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 20px; }

        .event-detail {
            display: flex; align-items: center; margin-bottom: 12px; font-size: 0.95rem; color: #475569;
        }
        .event-detail i { width: 20px; margin-right: 12px; color: #dc2626; }
        .event-detail .label { font-weight: 600; margin-right: 8px; }

        .event-description {
            background: rgba(220,38,38,0.05); padding: 15px; border-radius: 12px; border-left: 4px solid #dc2626;
            margin: 20px 0; font-size: 0.9rem; line-height: 1.6; color: #475569;
        }

        .participants-info {
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(34,197,94,0.05); padding: 10px 15px; border-radius: 8px; margin-bottom: 20px;
            border: 1px solid rgba(34,197,94,0.2);
        }
        .spots-available { font-size: 0.85rem; color: #059669; font-weight: 600; }

        .register-btn {
            width: 100%; background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: white;
            border: none; padding: 15px 25px; border-radius: 12px; font-weight: 600; font-size: 1rem;
            cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center;
            gap: 8px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .register-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(220,38,38,0.4); }
        .register-btn:disabled { background: #9ca3af; cursor: not-allowed; transform: none; }

        .no-events {
            text-align: center; color: white; font-size: 1.2rem; padding: 60px 20px;
            background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .no-events i { font-size: 3rem; margin-bottom: 20px; opacity: 0.7; }

        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            body { padding: 15px; }
            .events-grid { grid-template-columns: 1fr; gap: 20px; }
            .event-card { padding: 25px; }
            .header h1 { font-size: 2.5rem; }
        }

        .event-card { animation: fadeInUp 0.6s ease-out; animation-fill-mode: both; }
        .event-card:nth-child(1) { animation-delay: 0.1s; }
        .event-card:nth-child(2) { animation-delay: 0.2s; }
        .event-card:nth-child(3) { animation-delay: 0.3s; }
        .event-card:nth-child(4) { animation-delay: 0.4s; }
        .event-card:nth-child(5) { animation-delay: 0.5s; }
        .event-card:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-calendar-alt"></i> MDC Club Events</h1>
            <p class="subtitle">Discover amazing experiences and connect with fellow members</p>
            <div class="date-badge">
                <i class="fas fa-clock"></i> Updated Today
            </div>
        </div>

        <?php if ($msg): ?>
            <div class="msg <?= (str_contains($msg, '⚠️') || str_contains($msg, '❌')) ? 'warning' : 'success' ?>">
                <i class="fas <?= (str_contains($msg, '⚠️') || str_contains($msg, '❌')) ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="events-grid">
            <?php if ($events->num_rows > 0): ?>
                <?php 
                $card_index = 0;
                while ($row = $events->fetch_assoc()): 
                    $card_index++;
                    
                    // Calculate remaining spots (including pending registrations)
                    $registered_stmt = $conn->prepare("
                        SELECT COUNT(*) as total_registered 
                        FROM event_participation 
                        WHERE event_id = ? AND status IN ('approved', 'pending')
                    ");
                    $registered_stmt->bind_param("i", $row['event_id']);
                    $registered_stmt->execute();
                    $registered_result = $registered_stmt->get_result();
                    $registered_count = $registered_result->fetch_assoc()['total_registered'];
                    $remaining_spots = $row['max_participants'] - $registered_count;
                    
                    // Format date
                    $event_date = date('F j, Y', strtotime($row['date']));
                ?>
                    <div class="event-card" style="animation-delay: <?= $card_index * 0.1 ?>s;">
                        <h3 class="event-title"><?= htmlspecialchars($row['title']) ?></h3>
                        <div class="event-details">
                            <div class="event-detail">
                                <i class="fas fa-calendar"></i>
                                <span class="label">Date:</span>
                                <span><?= $event_date ?></span>
                            </div>
                            <div class="event-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span class="label">Venue:</span>
                                <span><?= htmlspecialchars($row['venue']) ?></span>
                            </div>
                            <?php if (!empty($row['time'])): ?>
                            <div class="event-detail">
                                <i class="fas fa-clock"></i>
                                <span class="label">Time:</span>
                                <span><?= htmlspecialchars($row['time']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="participants-info">
                            <div>
                                <i class="fas fa-users"></i>
                                <span class="label">Max Participants:</span> <?= $row['max_participants'] ?>
                            </div>
                            <div class="spots-available">
                                <i class="fas <?= $remaining_spots > 0 ? 'fa-fire' : 'fa-ban' ?>"></i>
                                <?= $remaining_spots > 0 ? $remaining_spots . ' spots left!' : 'Fully booked!' ?>
                            </div>
                        </div>
                        <div class="event-description">
                            <?= nl2br(htmlspecialchars($row['description'])) ?>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                            <button type="submit" class="register-btn" <?= $remaining_spots <= 0 ? 'disabled' : '' ?>>
                                <i class="fas fa-ticket-alt"></i>
                                <?= $remaining_spots > 0 ? 'Register Now' : 'Fully Booked' ?>
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-events">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No upcoming events right now</h3>
                    <p>Check back soon for exciting new events!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const eventCards = document.querySelectorAll('.event-card');
            const registerButtons = document.querySelectorAll('.register-btn');
            
            eventCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            registerButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        width: ${size}px; height: ${size}px; left: ${x}px; top: ${y}px;
                        position: absolute; border-radius: 50%; background: rgba(255,255,255,0.4);
                        transform: scale(0); animation: ripple 0.6s linear; pointer-events: none;
                    `;
                    
                    this.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 600);
                });
            });
        });

        const style = document.createElement('style');
        style.textContent = '@keyframes ripple { to { transform: scale(4); opacity: 0; } }';
        document.head.appendChild(style);
    </script>
</body>
</html>