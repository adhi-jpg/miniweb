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
    $check = $conn->query("SELECT * FROM event_participation WHERE user_id = $user_id AND event_id = $event_id");

    if ($check->num_rows === 0) {
        $insert = $conn->query("INSERT INTO event_participation (event_id, user_id, status)
                                VALUES ($event_id, $user_id, 'pending')");
        $msg = $insert ? "✅ Registration submitted! Waiting for approval." : "❌ Error registering.";
    } else {
        $msg = "⚠️ You’ve already registered for this event.";
    }
}

// ✅ Fetch upcoming events
$today = date('Y-m-d');
$events = $conn->query("SELECT * FROM events WHERE date >= '$today' ORDER BY date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upcoming Events – MDC Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.03)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.03)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.03)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            z-index: -1;
            pointer-events: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeInUp 0.8s ease-out;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
            color: white;
        }

        .header h1 {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            letter-spacing: -0.02em;
        }

        .header .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 300;
            margin-bottom: 30px;
        }

        .header .date-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .msg {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px 25px;
            border-radius: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            animation: slideInDown 0.6s ease-out;
        }

        .msg.success {
            background: rgba(34, 197, 94, 0.2);
            color: #dcfce7;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .msg.warning {
            background: rgba(239, 68, 68, 0.2);
            color: #fecaca;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .event-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }

        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .event-card:hover::before {
            opacity: 1;
        }

        .event-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        .event-details {
            margin-bottom: 25px;
        }

        .event-detail {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            font-size: 0.95rem;
            color: #475569;
        }

        .event-detail i {
            width: 20px;
            margin-right: 12px;
            color: #667eea;
            font-size: 1rem;
        }

        .event-detail .label {
            font-weight: 600;
            margin-right: 8px;
        }

        .event-description {
            background: rgba(102, 126, 234, 0.05);
            padding: 15px;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
            font-size: 0.9rem;
            line-height: 1.6;
            color: #475569;
        }

        .participants-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(34, 197, 94, 0.05);
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .spots-available {
            font-size: 0.85rem;
            color: #059669;
            font-weight: 600;
        }

        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .register-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .register-btn:hover::before {
            left: 100%;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .register-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .register-btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .register-btn:disabled::before {
            display: none;
        }

        .no-events {
            text-align: center;
            color: white;
            font-size: 1.2rem;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .no-events i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.7;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .pulse-animation {
            animation: pulse 2s infinite;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .event-card {
                padding: 25px;
            }
            
            .header h1 {
                font-size: 2.5rem;
            }
        }

        /* Loading animation for cards */
        .event-card {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

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
            <div class="msg <?= str_contains($msg, '⚠️') ? 'warning' : 'success' ?>">
                <i class="fas <?= str_contains($msg, '⚠️') ? 'fa-exclamation-triangle' : 'fa-check-circle' ?>"></i>
                <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="events-grid">
            <?php if ($events->num_rows > 0): ?>
                <?php 
                $card_index = 0;
                while ($row = $events->fetch_assoc()): 
                    $card_index++;
                    
                    // Calculate remaining spots
                    $registered_query = $conn->query("SELECT COUNT(*) as registered FROM event_participation WHERE event_id = {$row['event_id']} AND status = 'approved'");
                    $registered_count = $registered_query->fetch_assoc()['registered'];
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
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to event cards
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add click effect to register buttons
            const registerButtons = document.querySelectorAll('.register-btn');
            
            registerButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Add a ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'rgba(255, 255, 255, 0.4)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.6s linear';
                    ripple.style.pointerEvents = 'none';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });
        });

        // Add CSS for ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>