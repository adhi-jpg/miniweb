<?php
session_start();
include "config.php";

// ✅ Access control
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "faculty") {
    header("Location: login.php");
    exit();
}

// Optionally, fetch faculty details if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty Dashboard – MDC Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #1a1a1a 0%, #000000 100%);
            --secondary-gradient: linear-gradient(135deg, #7c2d92 0%, #be185d 100%);
            --accent-gradient: linear-gradient(135deg, #0369a1 0%, #0284c7 100%);
            --gold-gradient: linear-gradient(135deg, #f59e0b, #eab308);
            --emerald-gradient: linear-gradient(135deg, #047857, #065f46);
            --orange-gradient: linear-gradient(135deg, #ea580c, #c2410c);
            --glass-bg: rgba(0, 0, 0, 0.8);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --text-muted: #888888;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.5);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.6);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.7);
            --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.8);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --bg-primary: #000000;
            --bg-secondary: #1a1a1a;
            --bg-tertiary: #2a2a2a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #000000;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            line-height: 1.6;
            color: var(--text-primary);
        }

        /* Enhanced dark background with multiple layers */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(26, 26, 26, 0.3) 0%, transparent 60%),
                radial-gradient(circle at 80% 20%, rgba(42, 42, 42, 0.3) 0%, transparent 60%),
                radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 60% 70%, rgba(255, 255, 255, 0.02) 0%, transparent 45%);
            z-index: -2;
        }

        /* Animated mesh gradient overlay */
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                conic-gradient(from 0deg at 50% 50%, 
                    transparent 0deg,
                    rgba(26, 26, 26, 0.1) 60deg,
                    transparent 120deg,
                    rgba(42, 42, 42, 0.1) 180deg,
                    transparent 240deg,
                    rgba(26, 26, 26, 0.1) 300deg,
                    transparent 360deg);
            z-index: -1;
        }

        @keyframes meshRotate {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }

        /* Premium header with enhanced dark glass effect */
        header {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(30px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            padding: 24px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }

        header:hover {
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(35px) saturate(200%);
        }

        header h1 {
            font-size: 26px;
            font-weight: 600;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        header h1::before {
            content: '👨‍🏫';
            font-size: 32px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
        }

        .logout {
            position: relative;
        }

        .logout a {
            background: var(--gold-gradient);
            color: #000000;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.4);
            position: relative;
            overflow: hidden;
        }

        .logout a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .logout a:hover::before {
            left: 100%;
        }

        .logout a:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 8px 30px rgba(245, 158, 11, 0.5);
        }

        .logout a::after {
            content: '\f2f5';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
        }

        .container {
            padding: 48px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
            align-items: start;
        }

        /* Specialized card styles for different functions */
        .card {
            background: rgba(26, 26, 26, 0.8);
            backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-align: center;
            min-height: 240px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Card type specific styling */
        .card:nth-child(1) {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(6, 95, 70, 0.05));
            border-top: 4px solid var(--emerald-gradient);
        }

        .card:nth-child(2) {
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.1), rgba(2, 132, 199, 0.05));
            border-top: 4px solid var(--accent-gradient);
        }

        .card:nth-child(3) {
            background: linear-gradient(135deg, rgba(236, 72, 153, 0.1), rgba(190, 24, 93, 0.05));
            border-top: 4px solid var(--secondary-gradient);
        }

        .card:nth-child(4) {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.1), rgba(194, 65, 12, 0.05));
            border-top: 4px solid var(--orange-gradient);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.03) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover {
            transform: translateY(-20px) scale(1.04);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8);
            background: rgba(26, 26, 26, 0.9);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card:hover::after {
            opacity: 1;
        }

        .card h3 {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 20px 0 16px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        .card h3::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 3px;
            background: linear-gradient(135deg, #ffffff, #cccccc);
            border-radius: 2px;
            transition: width 0.4s ease;
        }

        .card:hover h3::after {
            width: 60px;
        }

        .card p {
            color: var(--text-secondary);
            line-height: 1.7;
            font-size: 16px;
            position: relative;
            z-index: 1;
            margin-top: auto;
        }

        /* Icon styling for each card */
        .card:nth-child(1) h3::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #10b981;
            font-size: 28px;
            margin-right: 8px;
        }

        .card:nth-child(2) h3::before {
            content: '\f0a1';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #0ea5e9;
            font-size: 28px;
            margin-right: 8px;
        }

        .card:nth-child(3) h3::before {
            content: '\f086';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #ec4899;
            font-size: 28px;
            margin-right: 8px;
        }

        .card:nth-child(4) h3::before {
            content: '\f201';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: #f97316;
            font-size: 28px;
            margin-right: 8px;
        }

        /* Floating shapes for visual interest */
        .floating-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .shape {
            position: absolute;
            opacity: 0.08;
        }

        .shape:nth-child(1) { left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { left: 20%; animation-delay: 3s; }
        .shape:nth-child(3) { left: 35%; animation-delay: 6s; }
        .shape:nth-child(4) { left: 50%; animation-delay: 9s; }
        .shape:nth-child(5) { left: 65%; animation-delay: 12s; }
        .shape:nth-child(6) { left: 80%; animation-delay: 15s; }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.15;
            }
            90% {
                opacity: 0.15;
            }
            100% {
                transform: translateY(-10vh) rotate(360deg);
                opacity: 0;
            }
        }

        /* Refined animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Individual card entrance animations */
        .card {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes cardSlideIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive design improvements */
        @media (max-width: 768px) {
            .container {
                padding: 24px;
            }
            
            header {
                padding: 20px 24px;
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            header h1 {
                font-size: 22px;
            }
            
            .section {
                grid-template-columns: 1fr;
                gap: 24px;
            }
            
            .card {
                padding: 32px;
                min-height: 200px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 16px;
            }
            
            .card {
                padding: 28px;
                min-height: 180px;
            }
            
            .card h3 {
                font-size: 20px;
            }
            
            .card p {
                font-size: 14px;
            }
        }

        /* Enhanced hover effects for professional feel */
        .card:hover h3 {
            color: #ffffff;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
        }

        /* Pulse animation for icons */
        .card:hover h3::before {
            animation: iconPulse 0.6s ease-in-out;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* Subtle particle system */
        .particle {
            position: fixed;
            width: 3px;
            height: 3px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            pointer-events: none;
            z-index: -1;
        }

        @keyframes particleFloat {
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

        /* Enhanced magnetic effect */
        .card:hover {
            animation: magneticFloat 0.8s ease-in-out;
        }

        @keyframes magneticFloat {
            0%, 100% { transform: translateY(-20px) scale(1.04); }
            50% { transform: translateY(-25px) scale(1.05); }
        }

        /* Glowing border effect */
        .card:hover {
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.8),
                0 0 0 1px rgba(255, 255, 255, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <!-- Floating shapes for visual interest -->
    <div class="floating-shapes">
        <div class="shape" style="width: 8px; height: 8px; background: rgba(255, 255, 255, 0.1); border-radius: 50%;"></div>
        <div class="shape" style="width: 6px; height: 6px; background: rgba(255, 255, 255, 0.08); border-radius: 50%;"></div>
        <div class="shape" style="width: 10px; height: 10px; background: rgba(255, 255, 255, 0.06); border-radius: 50%;"></div>
        <div class="shape" style="width: 4px; height: 4px; background: rgba(255, 255, 255, 0.1); border-radius: 50%;"></div>
        <div class="shape" style="width: 12px; height: 12px; background: rgba(255, 255, 255, 0.05); border-radius: 50%;"></div>
        <div class="shape" style="width: 8px; height: 8px; background: rgba(255, 255, 255, 0.08); border-radius: 50%;"></div>
    </div>

    <header>
        <h1>MDC Club – Faculty Dashboard</h1>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="section">
            <div class="card">
                <h3>Approve Event Participation</h3>
                <p>Review and approve student participation requests with detailed evaluation criteria and instant notifications.</p>
            </div>
            <div class="card">
             <a href="view_reports.php" style="text-decoration: none; color: inherit;">
                <h3>View and approve Reports</h3>
                <p>To view and approve reports from the executives.</p>
            </div>

            <div class="card">
                <h3>Post Announcements</h3>
                <p>Publish important updates, event notifications, and institutional communications for all students.</p>
            </div>

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
        // Enhanced interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // Enhanced card interactions
            document.querySelectorAll('.card').forEach((card, index) => {
                card.addEventListener('mouseenter', function() {
                    // Add subtle scale effect
                    this.style.setProperty('--hover-scale', '1.04');
                    
                    // Create enhanced ripple effect
                    const ripple = document.createElement('div');
                    ripple.style.position = 'absolute';
                    ripple.style.borderRadius = '50%';
                    ripple.style.background = 'radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%)';
                    ripple.style.transform = 'scale(0)';
                    ripple.style.animation = 'ripple 0.8s ease-out';
                    ripple.style.left = '50%';
                    ripple.style.top = '50%';
                    ripple.style.width = '120px';
                    ripple.style.height = '120px';
                    ripple.style.marginLeft = '-60px';
                    ripple.style.marginTop = '-60px';
                    ripple.style.pointerEvents = 'none';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        if (ripple.parentNode) {
                            ripple.remove();
                        }
                    }, 800);50%';
                    ripple.style.top = '50%';
                    ripple.style.width = '120px';
                    ripple.style.height = '120px';
                    ripple.style.marginLeft = '-60px';
                    ripple.style.marginTop = '-60px';
                    ripple.style.pointerEvents = 'none';
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        if (ripple.parentNode) {
                            ripple.remove();
                        }
                    }, 800);
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.setProperty('--hover-scale', '1');
                });

                // Add click functionality for future navigation
                card.addEventListener('click', function() {
                    // Add pulse animation
                    this.style.animation = 'cardPulse 0.4s ease-in-out';
                    setTimeout(() => {
                        this.style.animation = '';
                    }, 400);
                });

                // Add magnetic mouse tracking
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

            // Enhanced particle system
            function createParticle() {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + 'vw';
                particle.style.animationDuration = (Math.random() * 4 + 8) + 's';
                particle.style.animationDelay = Math.random() * 2 + 's';
                particle.style.background = `rgba(${Math.random() * 100 + 100}, ${Math.random() * 100 + 100}, 255, 0.4)`;
                document.body.appendChild(particle);
                
                setTimeout(() => {
                    if (particle.parentNode) {
                        particle.remove();
                    }
                }, 10000);
            }

            // Create particles periodically
            setInterval(createParticle, 800);

            // Performance optimization
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            if (prefersReducedMotion.matches) {
                document.documentElement.style.setProperty('--animation-duration', '0s');
            }

            // Add tilt effect on scroll
            let ticking = false;
            function updateTilt() {
                const scrolled = window.pageYOffset;
                const parallax = scrolled * 0.5;
                
                document.querySelectorAll('.card').forEach((card, index) => {
                    card.style.transform = `translateY(${parallax * (index + 1) * 0.1}px)`;
                });
                
                ticking = false;
            }

            window.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(updateTilt);
                    ticking = true;
                }
            });
        });

        // Add enhanced ripple animation styles
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            @keyframes cardPulse {
                0%, 100% { transform: translateY(-20px) scale(1.04); }
                50% { transform: translateY(-20px) scale(1.06); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>