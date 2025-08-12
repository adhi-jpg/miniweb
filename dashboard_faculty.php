<?php
session_start();
include "config.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard – MDC Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #000000; --secondary: #0d1117; --accent: #10b981; --gold: linear-gradient(135deg, #22c55e, #16a34a);
            --emerald: #22c55e; --orange: #059669; --pink: #34d399; --blue: #10b981;
            --glass: rgba(13, 17, 23, 0.9); --border: rgba(34, 197, 94, 0.2); --text: #ffffff; --text-muted: #a3a3a3;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.8); --shadow-lg: 0 25px 50px rgba(0, 0, 0, 0.9);
            --radius: 24px; --green-glow: 0 0 20px rgba(34, 197, 94, 0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #000000 0%, #0a0a0a 25%, #111111 50%, #0d1117 75%, #000000 100%); 
            min-height: 100vh; color: var(--text);
            position: relative; overflow-x: hidden;
        }

        body::before {
            content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background: 
                radial-gradient(circle at 20% 80%, rgba(34, 197, 94, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.06) 0%, transparent 60%),
                radial-gradient(circle at 40% 40%, rgba(34, 197, 94, 0.04) 0%, transparent 50%);
            animation: backgroundPulse 8s ease-in-out infinite alternate;
        }

        @keyframes backgroundPulse {
            0% { opacity: 0.3; transform: scale(1); }
            100% { opacity: 0.6; transform: scale(1.05); }
        }

        header {
            background: rgba(0, 0, 0, 0.95); backdrop-filter: blur(30px); border-bottom: 1px solid var(--border);
            padding: 24px 48px; display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1000; box-shadow: var(--shadow);
            border-bottom: 2px solid rgba(34, 197, 94, 0.3);
        }

        header h1 { 
            font-size: 26px; font-weight: 600; display: flex; align-items: center; gap: 12px;
            background: linear-gradient(45deg, #22c55e, #10b981, #34d399); 
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; 
            background-clip: text; text-shadow: var(--green-glow);
        }
        header h1::before { content: '👨‍🏫'; font-size: 32px; }

        .logout a {
            background: var(--gold); color: #000; text-decoration: none; padding: 14px 28px; border-radius: 50px;
            font-weight: 600; transition: all 0.4s ease; box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4);
            position: relative; overflow: hidden; border: 1px solid rgba(34, 197, 94, 0.3);
        }
        .logout a::before {
            content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        .logout a:hover::before { left: 100%; }
        .logout a:hover { 
            transform: translateY(-3px) scale(1.05); 
            box-shadow: 0 8px 30px rgba(34, 197, 94, 0.6); 
            background: linear-gradient(135deg, #16a34a, #15803d);
        }

        .container { padding: 48px; max-width: 1400px; margin: 0 auto; }

        .section { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; }

        .card {
            background: var(--glass); backdrop-filter: blur(25px); border: 1px solid var(--border);
            padding: 40px; border-radius: var(--radius); box-shadow: var(--shadow); position: relative; overflow: hidden;
            cursor: pointer; text-align: center; min-height: 240px; display: flex; flex-direction: column;
            justify-content: center; align-items: center; text-decoration: none; color: inherit;
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .card:nth-child(1) { 
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.12), rgba(16, 185, 129, 0.08)); 
            border-top: 4px solid var(--emerald); 
            box-shadow: var(--shadow), inset 0 1px 0 rgba(34, 197, 94, 0.1);
        }
        .card:nth-child(2) { 
            background: linear-gradient(135deg, rgba(52, 211, 153, 0.12), rgba(34, 197, 94, 0.08)); 
            border-top: 4px solid #34d399; 
            box-shadow: var(--shadow), inset 0 1px 0 rgba(52, 211, 153, 0.1);
        }
        .card:nth-child(3) { 
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.08)); 
            border-top: 4px solid var(--blue); 
            box-shadow: var(--shadow), inset 0 1px 0 rgba(16, 185, 129, 0.1);
        }
        .card:nth-child(4) { 
            background: linear-gradient(135deg, rgba(6, 95, 70, 0.15), rgba(4, 120, 87, 0.08)); 
            border-top: 4px solid var(--orange); 
            box-shadow: var(--shadow), inset 0 1px 0 rgba(6, 95, 70, 0.1);
        }
        .card:nth-child(5) { 
            background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(13, 148, 136, 0.08)); 
            border-top: 4px solid #14b8a6; 
            box-shadow: var(--shadow), inset 0 1px 0 rgba(20, 184, 166, 0.1);
        }

        .card::after {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; opacity: 0;
            background: radial-gradient(circle, rgba(34, 197, 94, 0.08) 0%, transparent 70%);
            transition: opacity 0.3s ease;
        }

        .card:hover {
            transform: translateY(-20px) scale(1.04); box-shadow: var(--shadow-lg), var(--green-glow);
            background: rgba(13, 17, 23, 0.95); border-color: rgba(34, 197, 94, 0.4);
        }
        .card:hover::after { opacity: 1; }

        .card h3 {
            font-size: 24px; font-weight: 600; margin: 20px 0 16px 0; display: flex; align-items: center;
            justify-content: center; gap: 12px; position: relative; z-index: 1;
            color: var(--emerald);
        }
        .card h3::after {
            content: ''; position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%);
            width: 0; height: 3px; background: linear-gradient(135deg, #22c55e, #10b981); border-radius: 2px;
            transition: width 0.4s ease;
        }
        .card:hover h3::after { width: 60px; }

        .card p { color: var(--text-muted); line-height: 1.7; font-size: 16px; position: relative; z-index: 1; }

        .card:nth-child(1) h3::before { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--emerald); font-size: 28px; }
        .card:nth-child(2) h3::before { content: '\f0a1'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: #34d399; font-size: 28px; }
        .card:nth-child(3) h3::before { content: '\f086'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--blue); font-size: 28px; }
        .card:nth-child(4) h3::before { content: '\f201'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--orange); font-size: 28px; }
        .card:nth-child(5) h3::before { content: '\f201'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: #14b8a6; font-size: 28px; }

        .particle {
            position: fixed; width: 3px; height: 3px; background: rgba(34, 197, 94, 0.4);
            border-radius: 50%; pointer-events: none; z-index: -1;
        }

        @keyframes float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10%, 90% { opacity: 1; }
            100% { transform: translateY(-10vh) rotate(360deg); opacity: 0; }
        }

        .card:hover h3 { 
            color: #34d399; 
            text-shadow: 0 0 20px rgba(34, 197, 94, 0.5); 
            filter: brightness(1.2);
        }
        .card:hover h3::before { 
            animation: iconPulse 0.6s ease-in-out; 
            filter: drop-shadow(0 0 10px rgba(34, 197, 94, 0.6));
        }

        @keyframes iconPulse { 
            0%, 100% { transform: scale(1); } 
            50% { transform: scale(1.2); } 
        }

        @keyframes ripple { to { transform: scale(4); opacity: 0; } }

        /* Green glow effects on hover */
        .card:hover {
            box-shadow: var(--shadow-lg), 
                        0 0 30px rgba(34, 197, 94, 0.3), 
                        inset 0 0 20px rgba(34, 197, 94, 0.05);
        }

        .card:hover p { color: #d1d5db; }

        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.3); }
        ::-webkit-scrollbar-thumb { 
            background: linear-gradient(45deg, #22c55e, #10b981); 
            border-radius: 4px; 
        }
        ::-webkit-scrollbar-thumb:hover { background: linear-gradient(45deg, #16a34a, #059669); }

        @media (max-width: 768px) {
            .container { padding: 24px; }
            header { padding: 20px 24px; flex-direction: column; gap: 16px; text-align: center; }
            header h1 { font-size: 22px; }
            .section { grid-template-columns: 1fr; gap: 24px; }
            .card { padding: 32px; min-height: 200px; }
        }

        @media (max-width: 480px) {
            .container { padding: 16px; }
            .card { padding: 28px; min-height: 180px; }
            .card h3 { font-size: 20px; }
            .card p { font-size: 14px; }
        }
    </style>
</head>
<body>
    <header>
        <h1>MDC Club – Faculty Dashboard</h1>
        <div class="logout"><a href="logout.php">Logout</a></div>
    </header>

    <div class="container">
        <div class="section">
            <a href="view_registration_faculty.php" class="card">
                <h3>View Event Participation</h3>
                <p>View student participation requests.</p>
            </a>
            
            <a href="view_reports.php" class="card">
                <h3>View and approve Reports</h3>
                <p>To view and approve reports from the executives.</p>
            </a>

            <a href="chat_faculty.php" class="card">
                <h3>Chat</h3>
                <p>Communicate with admin.</p>
            </a>

            <a href="faculty_review_proposal.php" class="card">
                <h3>Proposals</h3>
                <p>proposals for events.</p>
            </a>

            <div class="card">
                <h3>View Feedback</h3>
                <p>Access comprehensive feedback submitted by students for events, courses, and overall club experience.</p>
            </div>

            <div class="card">
                <h3>View Student Engagement</h3>
                <p>Monitor and analyze student involvement metrics, participation trends, and engagement analytics.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const ripple = document.createElement('div');
                    Object.assign(ripple.style, {
                        position: 'absolute', borderRadius: '50%', transform: 'scale(0)',
                        animation: 'ripple 0.8s ease-out', left: '50%', top: '50%',
                        width: '120px', height: '120px', marginLeft: '-60px', marginTop: '-60px',
                        background: 'radial-gradient(circle, rgba(34, 197, 94, 0.15) 0%, transparent 70%)',
                        pointerEvents: 'none'
                    });
                    
                    this.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 800);
                });

                card.addEventListener('mousemove', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;
                    const deltaX = (x - centerX) / centerX;
                    const deltaY = (y - centerY) / centerY;
                    
                    this.style.transform = `translateY(-20px) scale(1.04) rotateX(${deltaY * 5}deg) rotateY(${deltaX * 5}deg)`;
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                });
            });

            function createParticle() {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + 'vw';
                particle.style.animationDuration = (Math.random() * 4 + 8) + 's';
                particle.style.animation = `float ${particle.style.animationDuration} linear infinite`;
                document.body.appendChild(particle);
                
                setTimeout(() => particle.remove(), 12000);
            }

            setInterval(createParticle, 800);
        });
    </script>
</body>
</html>