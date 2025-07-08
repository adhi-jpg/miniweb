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
    <title>Upcoming Events – MDC Club</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f4f4;
            padding: 30px;
        }

        h2 {
            text-align: center;
            color: #003366;
            margin-bottom: 30px;
        }

        .msg {
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            color: green;
        }

        .msg.warning {
            color: #cc0000;
        }

        .event-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px;
        }

        .event-card {
            background: #fff;
            width: 330px;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .event-card h3 {
            color: #0055aa;
            margin-bottom: 10px;
        }

        .event-card p {
            font-size: 15px;
            color: #444;
            margin: 6px 0;
        }

        .event-card span {
            font-weight: bold;
            color: #222;
        }

        .event-card form {
            margin-top: 12px;
        }

        .register-btn {
            background: #0066cc;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .register-btn:hover {
            background: #004a99;
        }
    </style>
</head>
<body>

<h2>📅 Upcoming MDC Events</h2>

<?php if ($msg): ?>
    <div class="msg <?= str_contains($msg, '⚠️') ? 'warning' : '' ?>"><?= $msg ?></div>
<?php endif; ?>

<div class="event-container">
    <?php if ($events->num_rows > 0): ?>
        <?php while ($row = $events->fetch_assoc()): ?>
            <div class="event-card">
                <h3><?= htmlspecialchars($row['title']) ?></h3>
                <p><span>🗓 Date:</span> <?= $row['date'] ?></p>
                <p><span>📍 Venue:</span> <?= htmlspecialchars($row['venue']) ?></p>
                <p><span>👥 Max Participants:</span> <?= $row['max_participants'] ?></p>
                <p><span>📝 Description:</span><br><?= nl2br(htmlspecialchars($row['description'])) ?></p>

                <form method="POST">
                    <input type="hidden" name="event_id" value="<?= $row['event_id'] ?>">
                    <button type="submit" class="register-btn">🎟 Register</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="text-align:center; width:100%;">No upcoming events right now.</p>
    <?php endif; ?>
</div>

</body>
</html>

