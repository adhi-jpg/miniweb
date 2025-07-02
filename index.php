<?php
include "config.php";
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST["name"]);
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $message = mysqli_real_escape_string($conn, $_POST["message"]);

    $sql = "INSERT INTO contact_messages (name, email, message) VALUES ('$name', '$email', '$message')";
    if ($conn->query($sql)) {
        $msg = "✅ Message sent successfully!";
    } else {
        $msg = "❌ Failed to send message: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDC Club – Where Passion Meets Performance</title>
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

        .header-scrolled {
            background: rgba(10, 20, 40, 0.98);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-container img {
            height: 45px;
            border-radius: 50%;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }

        .logo-text {
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

        /* Hero Section with Slideshow */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .slideshow-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1.5s ease-in-out;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .slide.active {
            opacity: 1;
        }

        .slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                135deg,
                rgba(0, 0, 0, 0.7) 0%,
                rgba(0, 0, 0, 0.4) 50%,
                rgba(0, 0, 0, 0.8) 100%
            );
            z-index: 1;
        }

        /* Slideshow Navigation */
        .slide-nav {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            z-index: 10;
        }

        .slide-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .slide-dot.active {
            background: var(--primary-gold);
            border-color: rgba(255, 215, 0, 0.5);
            transform: scale(1.2);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.6);
        }

        .slide-dot:hover {
            background: rgba(255, 215, 0, 0.7);
            transform: scale(1.1);
        }

        /* Arrow Navigation */
        .slide-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 215, 0, 0.2);
            color: var(--primary-gold);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .slide-arrow:hover {
            background: rgba(255, 215, 0, 0.3);
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        .slide-arrow.prev {
            left: 30px;
        }

        .slide-arrow.next {
            right: 30px;
        }

        /* Slide Progress Bar */
        .slide-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            background: var(--primary-gold);
            transition: width 5s linear;
            z-index: 10;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            padding: 0 20px;
            animation: fadeInUp 1s ease;
            position: relative;
            z-index: 5;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero h1 {
            font-size: clamp(3rem, 8vw, 6rem);
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

        .hero-subtitle {
            font-size: clamp(1.2rem, 3vw, 1.8rem);
            margin-bottom: 30px;
            opacity: 0.9;
            animation: fadeInUp 1s ease 0.3s both;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 1s ease 0.6s both;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-gold), var(--dark-gold));
            color: var(--deep-blue);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.3);
        }

        .btn-secondary {
            background: var(--glass-bg);
            color: var(--text-light);
            border: 2px solid var(--primary-gold);
            backdrop-filter: blur(10px);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.4);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        /* Features Section */
        .features {
            padding: 100px 5%;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
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

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(255, 215, 0, 0.2);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary-gold);
            margin-bottom: 20px;
            display: block;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-gold);
        }

        /* About Section */
        .about {
            padding: 100px 5%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .about-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .about-text h2 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 30px;
            color: var(--primary-gold);
        }

        .about-text p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 20px;
            opacity: 0.9;
        }

        .about-visual {
            position: relative;
            height: 400px;
            border-radius: 20px;
            overflow: hidden;
            background: var(--gradient-purple);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dance-icon {
            font-size: 8rem;
            color: rgba(255, 255, 255, 0.3);
            animation: dance 4s ease-in-out infinite;
        }

        @keyframes dance {
            0%, 100% { transform: rotate(-5deg) scale(1); }
            25% { transform: rotate(5deg) scale(1.1); }
            50% { transform: rotate(-3deg) scale(1); }
            75% { transform: rotate(3deg) scale(1.1); }
        }

        /* Contact Section */
        .contact {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            margin: 50px 5%;
            border-radius: 30px;
            padding: 60px 40px;
            border: 1px solid rgba(255, 215, 0, 0.2);
        }

        .contact h2 {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 40px;
            color: var(--primary-gold);
        }

        .contact-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 15px 20px;
            background: var(--glass-bg);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            color: var(--text-light);
            font-size: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .contact-form input::placeholder,
        .contact-form textarea::placeholder {
            color: rgba(248, 249, 250, 0.7);
        }

        .contact-form input:focus,
        .contact-form textarea:focus {
            outline: none;
            border-color: var(--primary-gold);
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }

        .status {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
        }

        .status.success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
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
            
            .about-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Scroll animations */
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

    <header id="header">
        <div class="logo-container">
            <img src="logggo.png" alt="MDC Logo">
            <div class="logo-text">MDC CLUB</div>
        </div>
        <nav>
            <a href="#home"><i class="fas fa-home"></i> Home</a>
            <a href="about .php"><i class="fas fa-info-circle"></i> About</a>
            <a href="register.php"><i class="fas fa-user-plus"></i> Register</a>
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
            <a href="#contact"><i class="fas fa-envelope"></i> Contact</a>
        </nav>
    </header>

    <main>
        <section class="hero" id="home">
            <div class="slideshow-container">
                <div class="slide active" style="background-image: url('logos.jpg')"></div>
                <div class="slide" style="background-image: url('reg.jpg')"></div>
                <div class="slide" style="background-image: url('slide1.jpg')"></div>
                <div class="slide" style="background-image: url('adhii.jpg')"></div>
                <div class="slide" style="background-image: url('kk.jpg')"></div>
                <div class="slide-progress"></div>
            </div>
            
            <button class="slide-arrow prev" onclick="changeSlide(-1)">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slide-arrow next" onclick="changeSlide(1)">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div class="slide-nav">
                <span class="slide-dot active" onclick="currentSlide(1)"></span>
                <span class="slide-dot" onclick="currentSlide(2)"></span>
                <span class="slide-dot" onclick="currentSlide(3)"></span>
                <span class="slide-dot" onclick="currentSlide(4)"></span>
                <span class="slide-dot" onclick="currentSlide(5)"></span>
            </div>

            <div class="hero-content">
                <h1>Marian Dance Club</h1>
                <p class="hero-subtitle">Where Passion Meets Performance ✨</p>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-star"></i> Join Our Family
                    </a>
                    <a href="login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> Member Login
                    </a>
                </div>
            </div>
        </section>

        <section class="features fade-in">
            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-music feature-icon"></i>
                    <h3 class="feature-title">Diverse Styles</h3>
                    <p>From classical to hip-hop, contemporary to bollywood - explore every dance form that moves your soul.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-trophy feature-icon"></i>
                    <h3 class="feature-title">Competitions</h3>
                    <p>Showcase your talent in inter-collegiate competitions and win hearts with your performances.</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users feature-icon"></i>
                    <h3 class="feature-title">Community</h3>
                    <p>Join a family of passionate dancers who support, inspire, and grow together.</p>
                </div>
            </div>
        </section>

        <section class="about fade-in" id="about">
            <div class="about-content">
                <div class="about-text">
                    <h2>About MDC</h2>
                    <p>The Marian Dance Club is where dreams take flight and rhythms come alive. We're more than just a dance club - we're a vibrant community that celebrates the art of movement and expression.</p>
                    <p>From energetic flashmobs that light up the campus to mesmerizing performances at college events, MDC has been the heartbeat of Marian College's cultural scene.</p>
                    <p>Whether you're a seasoned dancer or someone who's never stepped foot on a dance floor, MDC welcomes everyone with open arms. Come, be part of our journey!</p>
                </div>
                <div class="about-visual">
                    <i class="fas fa-child dance-icon"></i>
                </div>
            </div>
        </section>

        <section class="contact fade-in" id="contact">
            <h2><i class="fas fa-envelope"></i> Get In Touch</h2>
            <div class="contact-form">
                <form method="POST">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Your Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Your Email" required>
                    </div>
                    <div class="form-group">
                        <textarea name="message" rows="5" placeholder="Your Message" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </section>
    </main>

    <script>
        // Slideshow functionality
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slide-dot');
        const progressBar = document.querySelector('.slide-progress');
        let slideInterval;
        let progressInterval;

        function showSlide(index) {
            // Remove active class from all slides and dots
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Add active class to current slide and dots
            slides[index].classList.add('active');
            dots[index].classList.add('active');
            
            // Update current slide index
            currentSlideIndex = index;
            
            // Reset and start progress bar
            resetProgressBar();
        }

        function nextSlide() {
            currentSlideIndex = (currentSlideIndex + 1) % slides.length;
            showSlide(currentSlideIndex);
        }

        function prevSlide() {
            currentSlideIndex = (currentSlideIndex - 1 + slides.length) % slides.length;
            showSlide(currentSlideIndex);
        }

        function currentSlide(index) {
            showSlide(index - 1);
            restartSlideShow();
        }

        function changeSlide(direction) {
            if (direction === 1) {
                nextSlide();
            } else {
                prevSlide();
            }
            restartSlideShow();
        }

        function resetProgressBar() {
            if (progressBar) {
                progressBar.style.width = '0%';
                clearInterval(progressInterval);
                
                let progress = 0;
                progressInterval = setInterval(() => {
                    progress += 1;
                    progressBar.style.width = progress + '%';
                    if (progress >= 100) {
                        clearInterval(progressInterval);
                    }
                }, 50); // 5 seconds total (100 * 50ms)
            }
        }

        function startSlideShow() {
            slideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
            resetProgressBar();
        }

        function restartSlideShow() {
            clearInterval(slideInterval);
            clearInterval(progressInterval);
            startSlideShow();
        }

        // Start slideshow when page loads
        document.addEventListener('DOMContentLoaded', () => {
            startSlideShow();
        });

        // Pause slideshow on hover
        const heroSection = document.querySelector('.hero');
        if (heroSection) {
            heroSection.addEventListener('mouseenter', () => {
                clearInterval(slideInterval);
                clearInterval(progressInterval);
            });
            
            heroSection.addEventListener('mouseleave', () => {
                startSlideShow();
            });
        }

        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('header-scrolled');
            } else {
                header.classList.remove('header-scrolled');
            }
        });

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

        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });

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
    </script>
</body>
</html>