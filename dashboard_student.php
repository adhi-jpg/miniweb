<?php
session_start();

// Strong cache prevention
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if session doesn't exist or wrong role
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

include "config.php";

$user_id = $_SESSION["user_id"];
$student = $conn->query("SELECT name, roll_number, department FROM student_profiles WHERE user_id = $user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard – MDC Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            z-index: -1;
        }

        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 24px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            animation: slideInLeft 0.8s ease-out;
        }

        .logout a {
            background: linear-gradient(135deg, #ffd700, #ffed4a);
            color: #333;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            animation: slideInRight 0.8s ease-out;
        }

        .logout a:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 215, 0, 0.4);
            background: linear-gradient(135deg, #ffed4a, #ffd700);
        }

        .container {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section {
            margin-bottom: 40px;
            animation: fadeInUp 0.8s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }

        .section:nth-child(1) { animation-delay: 0.2s; }
        .section:nth-child(2) { animation-delay: 0.4s; }
        .section:nth-child(3) { animation-delay: 0.6s; }

        .section h2 {
            margin-top: 0;
            margin-bottom: 24px;
            color: white;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            padding-left: 20px;
        }

        .section h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 40px;
            background: linear-gradient(135deg, #ffd700, #ffed4a);
            border-radius: 3px;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.95);
        }

        .card h3 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card p {
            color: #666;
            line-height: 1.6;
            font-size: 14px;
        }

        .announcement {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            padding: 24px;
            margin-bottom: 20px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .announcement::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .announcement:hover {
            transform: translateX(4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .announcement strong {
            display: block;
            color: #333;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .announcement p {
            margin: 0;
            color: #555;
            line-height: 1.6;
        }

        .feedback textarea {
            width: 100%;
            border-radius: 12px;
            padding: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            resize: vertical;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .feedback textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .feedback button {
            margin-top: 12px;
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            font-family: 'Inter', sans-serif;
        }

        .feedback button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #764ba2, #667eea);
        }

        .msg {
            color: #10b981;
            font-weight: 600;
            margin-top: 12px;
            padding: 12px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        table, th, td {
            border: none;
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 16px;
            font-weight: 600;
            text-align: left;
        }

        td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        tr:nth-child(even) {
            background: rgba(102, 126, 234, 0.05);
        }

        tr:hover {
            background: rgba(102, 126, 234, 0.1);
            transition: background 0.3s ease;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            header {
                padding: 15px 20px;
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .section h2 {
                font-size: 24px;
            }
            
            .card-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Floating particles animation */
        .particle {
            position: fixed;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            pointer-events: none;
            animation: float 6s infinite linear;
            z-index: -1;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-10vh) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
    <script>
        // Add floating particles
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + 'vw';
            particle.style.animationDuration = (Math.random() * 3 + 2) + 's';
            particle.style.animationDelay = Math.random() * 2 + 's';
            document.body.appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
            }, 8000);
        }

        // Create particles periodically
        setInterval(createParticle, 300);
    </script>
</head>
<script>
<script>
if (performance.navigation.type === 2) {
    // Force redirect if the user is navigating with the back button
    location.href = "logout.php";
}
</script>

</script>

<body>

<header>
    <h1>Welcome, <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['roll_number']) ?>)</h1>
    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="container">

    <!-- 📢 Announcements -->
    <div class="section">
        <h2>📢 Announcements</h2>
        <?php
        $announcements = $conn->query("SELECT * FROM announcements ORDER BY posted_on DESC LIMIT 5");
        if ($announcements->num_rows > 0) {
            while ($a = $announcements->fetch_assoc()) {
                echo "<div class='announcement'>
                        <strong>" . date("d M Y", strtotime($a["posted_on"])) . "</strong>
                        <p>" . htmlspecialchars($a["message"]) . "</p>
                      </div>";
            }
        } else {
            echo "<p>No announcements available.</p>";
        }
        ?>
    </div>

    <!-- 📦 Original Dashboard Cards -->
    <div class="section">
        <h2>🎯 Quick Access</h2>
        <div class="card-grid">
            <div class="card">
                <h3>🎉 My Events</h3>
                <p>View upcoming and registered events.</p>
            </div>

            <div class="card">
                <h3>✅ My Participation</h3>
                <p>Track your event history and approvals.</p>
            </div>

            <div class="card">
                <h3>📝 Submit Feedback</h3>
                <p>Share suggestions or report issues.</p>
            </div>

            <div class="card">
                <h3>📚 Resources</h3>
                <p>Access club materials and past content.</p>
            </div>
        </div>
    </div>

</div>

</body>
</html>