<?php // about.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us – MDC Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gold: #ffd700;
            --dark-gold: #b8860b;
            --deep-blue: #0a1428;
            --gradient-purple: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-sunset: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%);
            --text-light: #f8f9fa;
            --glass-bg: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: var(--deep-blue);
            color: var(--text-light);
            overflow-x: hidden;
            line-height: 1.6;
            padding-top: 80px;
        }

        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(45deg, #0a1428, #1a2942, #2d3561);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating particles */
        .particle {
            position: absolute;
            background: var(--primary-gold);
            border-radius: 50%;
            opacity: 0.1;
            animation: float 8s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(10, 20, 40, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary-gold), #fff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        nav {
            display: flex;
            gap: 30px;
        }

        nav a {
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
            position: relative;
            transition: all 0.3s ease;
            padding: 10px 0;
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-gold);
            transition: width 0.3s ease;
        }

        nav a:hover::after {
            width: 100%;
        }

        nav a:hover {
            color: var(--primary-gold);
            transform: translateY(-2px);
        }

        /* Container */
        .container {
            padding: 40px 5%;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }

        .page-header h1 {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(45deg, var(--primary-gold), #fff, var(--primary-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.5);
            animation: textGlow 3s ease-in-out infinite alternate;
        }

        @keyframes textGlow {
            from { filter: drop-shadow(0 0 20px rgba(255, 215, 0, 0.5)); }
            to { filter: drop-shadow(0 0 40px rgba(255, 215, 0, 0.8)); }
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Section Styling */
        .section {
            margin-bottom: 60px;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .section.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .section h2 {
            font-size: 2rem;
            color: var(--primary-gold);
            margin-bottom: 30px;
            position: relative;
            padding-left: 20px;
            font-weight: 700;
        }

        .section h2::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(45deg, var(--primary-gold), var(--dark-gold));
            border-radius: 3px;
            box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        .section h2 i {
            margin-right: 10px;
            font-size: 1.5rem;
        }

        /* Mission Section */
        .mission-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .mission-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.05), transparent);
            pointer-events: none;
        }

        .mission-content p {
            font-size: 1.1rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        /* Team Cards */
        .team, .faculty, .achievements {
            display: grid;
            gap: 30px;
        }

        .faculty {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            justify-items: center;
        }

        .team {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            max-width: 350px;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.1), rgba(255, 215, 0, 0.05));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .card:hover::before {
            opacity: 1;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.2);
        }

        .card img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 20px;
            border: 4px solid var(--primary-gold);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
            transition: all 0.3s ease;
        }

        .card:hover img {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.5);
        }

        .card strong {
            display: block;
            font-size: 1.3rem;
            color: var(--primary-gold);
            margin-bottom: 10px;
            font-weight: 600;
        }

        .card p {
            color: rgba(248, 249, 250, 0.8);
            font-size: 1rem;
        }

        /* Achievements */
        .achievements {
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .achievement {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-left: 5px solid var(--primary-gold);
            border-radius: 15px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .achievement::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255, 215, 0, 0.1), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .achievement:hover::before {
            opacity: 1;
        }

        .achievement:hover {
            transform: translateX(10px);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
        }

        .achievement strong {
            display: block;
            font-size: 1.2rem;
            color: var(--primary-gold);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .achievement p {
            font-size: 1rem;
            line-height: 1.6;
            color: rgba(248, 249, 250, 0.9);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 30px;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid rgba(255, 215, 0, 0.2);
            margin-top: 50px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            nav {
                display: none;
            }
            
            .container {
                padding: 20px;
            }
            
            .card {
                max-width: 100%;
            }
            
            .section h2 {
                font-size: 1.5rem;
            }
        }

        /* Animation on scroll */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="bg-animation"></div>

    <!-- Floating Particles -->
    <div class="particle" style="top: 10%; left: 10%; width: 4px; height: 4px; animation-delay: 0s;"></div>
    <div class="particle" style="top: 20%; left: 80%; width: 6px; height: 6px; animation-delay: 2s;"></div>
    <div class="particle" style="top: 60%; left: 20%; width: 3px; height: 3px; animation-delay: 4s;"></div>
    <div class="particle" style="top: 80%; left: 70%; width: 5px; height: 5px; animation-delay: 6s;"></div>

    <header>
        <h1><i class="fas fa-users"></i> MDC Club – About Us</h1>
        <nav>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="about .php"><i class="fas fa-info-circle"></i> About</a>
            <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
        </nav>
    </header>

    <div class="container">
        <div class="page-header">
            <h1>About Our Dance Family</h1>
            <p>Discover the passion, dedication, and artistry that drives the Marian Dance Club</p>
        </div>

        <div class="section fade-in">
            <h2><i class="fas fa-bullseye"></i> Our Mission</h2>
            <div class="mission-content">
                <p>
                    The Marian Dance Club (MDC) is a vibrant platform that fosters creative expression, cultural appreciation,
                    and confidence through dance. Our mission is to unite passionate students, organize impactful performances,
                    and promote the art of movement across campus life.
                </p>
            </div>
        </div>

        <div class="section fade-in">
            <h2><i class="fas fa-chalkboard-teacher"></i> Faculty In-Charge</h2>
            <div class="faculty">
                <div class="card">
                    <img src="images/faculty1.jpg" alt="Ms. Divya Lekshmi">
                    <strong>Ms. Divya Lekshmi</strong>
                    <p>Faculty Guide, Department of Computer Science</p>
                </div>
            </div>
        </div>

        <div class="section fade-in">
            <h2><i class="fas fa-user-friends"></i> Student Coordinators</h2>
            <div class="team">
                <div class="card">
                    <img src="images/adithyan.jpg" alt="Adithyan H">
                    <strong>Adithyan H</strong>
                    <p>Lead Developer & Club Coordinator</p>
                </div>
                <div class="card">
                    <img src="images/melbin.jpg" alt="Melbin Antony">
                    <strong>Melbin Antony</strong>
                    <p>Backend Developer & Event Manager</p>
                </div>
            </div>
        </div>

        <div class="section fade-in">
            <h2><i class="fas fa-trophy"></i> Our Achievements 🏆</h2>
            <div class="achievements">
                <div class="achievement">
                    <strong>🥇 First Place – Intercollegiate Dance Fest 2024</strong>
                    <p>
                        MDC Club secured the top position among 20 colleges in the annual cultural dance event held at XYZ University.
                    </p>
                </div>
                <div class="achievement">
                    <strong>🎭 Flashmob at Marian Arts Week 2023</strong>
                    <p>
                        Organized a surprise flashmob that went viral across social media with over 10K+ views in 48 hours.
                    </p>
                </div>
                <div class="achievement">
                    <strong>💃 Hosted National-Level Choreography Workshop</strong>
                    <p>
                        Invited celebrity choreographers to train over 100 participants from various institutions.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Fade in animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-in, .section').forEach(el => {
            observer.observe(el);
        });

        // Add more floating particles dynamically
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.width = particle.style.height = (Math.random() * 5 + 2) + 'px';
            particle.style.animationDelay = Math.random() * 8 + 's';
            document.body.appendChild(particle);

            setTimeout(() => {
                particle.remove();
            }, 8000);
        }

        // Create particles periodically
        setInterval(createParticle, 3000);

        // Add some initial particles
        for (let i = 0; i < 5; i++) {
            setTimeout(createParticle, i * 1000);
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>