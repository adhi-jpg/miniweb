<?php
session_start();
include "config.php";

// ✅ Ensure student login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

// --- Filters ---
$filter_event = isset($_GET['filter_event']) ? intval($_GET['filter_event']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = [];
if ($filter_event > 0) $where[] = "mg.event_id=$filter_event";
if (!empty($search)) $where[] = "mg.file_url LIKE '%$search%'";
$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// --- Fetch media ---
$media_result = $conn->query("SELECT mg.*, e.title AS event_name FROM media_gallery mg 
LEFT JOIN events e ON mg.event_id=e.event_id $whereSQL ORDER BY mg.uploaded_on DESC");

// --- Fetch events for filter dropdown ---
$events = $conn->query("SELECT event_id, title FROM events ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Gallery – MDC Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        header { 
            background: linear-gradient(135deg, #4a00e0 0%, #8e2de2 100%);
            color: white; 
            padding: 40px 20px; 
            text-align: center;
            box-shadow: 0 10px 30px rgba(74, 0, 224, 0.3);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-50px) translateY(-50px); }
        }

        h1 { 
            margin: 0; 
            font-size: 2.5em; 
            font-weight: 300;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: relative;
            z-index: 1;
        }

        .subtitle {
            font-size: 1.1em;
            margin-top: 10px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .filter-section { 
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            padding: 30px;
            margin: -20px 20px 20px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .filter-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            font-weight: 600;
            color: #555;
            font-size: 14px;
        }

        .filter-section select, .filter-section input { 
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            font-size: 16px;
            width: 220px;
            transition: all 0.3s ease;
            background: white;
        }

        .filter-section select:focus, .filter-section input:focus {
            outline: none;
            border-color: #4a00e0;
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
        }

        .filter-section button { 
            padding: 12px 30px;
            background: linear-gradient(135deg, #4a00e0 0%, #8e2de2 100%);
            border: none;
            color: white;
            font-weight: 600;
            cursor: pointer;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(74, 0, 224, 0.3);
        }

        .filter-section button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(74, 0, 224, 0.4);
        }

        .gallery-stats {
            text-align: center;
            margin: 20px;
            padding: 15px;
            background: rgba(255,255,255,0.9);
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .gallery { 
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .media-card { 
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
        }

        .media-card:hover { 
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }

        .media-container {
            position: relative;
            overflow: hidden;
            height: 220px;
            background: #f8f9fa;
        }

        .media-card img, .media-card video { 
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .media-card:hover img, .media-card:hover video {
            transform: scale(1.1);
        }

        .media-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(74, 0, 224, 0.8), rgba(142, 45, 226, 0.8));
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .media-card:hover .media-overlay {
            opacity: 1;
        }

        .media-info {
            padding: 20px;
        }

        .event-name { 
            font-weight: 700;
            font-size: 1.1em;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .uploaded-on { 
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .media-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .no-media {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 1.2em;
            grid-column: 1 / -1;
        }

        .no-media i {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        /* Enhanced Lightbox */
        #lightbox { 
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }

        #lightbox-content {
            position: relative;
            max-width: 90vw;
            max-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #lightbox img, #lightbox video { 
            max-width: 100%;
            max-height: 100%;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        .lightbox-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 15px;
            background: rgba(0,0,0,0.8);
            padding: 15px 25px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
        }

        .lightbox-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            transition: all 0.3s ease;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        .close-btn {
            position: absolute;
            top: 30px;
            right: 40px;
            font-size: 40px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }

        .nav-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 50px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
        }

        .nav-btn:hover {
            background: rgba(74, 0, 224, 0.8);
            transform: translateY(-50%) scale(1.1);
        }

        .prev-btn { left: 40px; }
        .next-btn { right: 40px; }

        .lightbox-info {
            position: absolute;
            top: 30px;
            left: 40px;
            color: white;
            background: rgba(0,0,0,0.8);
            padding: 15px 20px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .lightbox-counter {
            position: absolute;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            background: rgba(0,0,0,0.8);
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(74, 0, 224, 0.3);
            border-radius: 50%;
            border-top-color: #4a00e0;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            header {
                padding: 30px 15px;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .filter-section {
                margin: -15px 15px 15px;
                padding: 20px;
            }
            
            .filter-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .filter-section select,
            .filter-section input {
                width: 100%;
                max-width: 300px;
            }
            
            .gallery {
                grid-template-columns: 1fr;
                padding: 20px 15px;
                gap: 20px;
            }
            
            .close-btn {
                top: 20px;
                right: 20px;
                font-size: 30px;
                width: 50px;
                height: 50px;
            }
            
            .nav-btn {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
            
            .prev-btn { left: 20px; }
            .next-btn { right: 20px; }
            
            .lightbox-controls {
                bottom: 20px;
                padding: 12px 20px;
            }
            
            .lightbox-info {
                top: 20px;
                left: 20px;
                right: 70px;
                padding: 12px 15px;
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #4a00e0, #8e2de2);
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #3700b3, #7b1fa2);
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-images"></i> MDC Club Media Gallery</h1>
        <div class="subtitle">Explore and discover amazing moments</div>
    </header>

    <!-- Filter & Search -->
    <div class="filter-section">
        <form method="GET">
            <div class="filter-container">
                <div class="filter-group">
                    <label for="filter_event"><i class="fas fa-calendar-alt"></i> Filter by Event</label>
                    <select name="filter_event" id="filter_event" onchange="this.form.submit()">
                        <option value="0">All Events</option>
                        <?php while ($ev = $events->fetch_assoc()) { ?>
                            <option value="<?= $ev['event_id'] ?>" <?= $filter_event==$ev['event_id']?'selected':'' ?>>
                                <?= htmlspecialchars($ev['title']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="search"><i class="fas fa-search"></i> Search Files</label>
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search media files...">
                </div>
                
                <button type="submit">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <?php 
    $total_media = $media_result->num_rows;
    if ($total_media > 0) {
        echo "<div class='gallery-stats'><i class='fas fa-photo-video'></i> Found $total_media media file" . ($total_media > 1 ? 's' : '') . "</div>";
    }
    ?>

    <!-- Media Display -->
    <div class="gallery" id="gallery">
        <?php 
        $mediaData = [];
        if ($media_result->num_rows > 0) {
            while ($row = $media_result->fetch_assoc()) { 
                $file = $row['file_url']; 
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); 
                $mediaData[] = $row; 
                $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm']);
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                ?>
                <div class="media-card" data-src="<?= $file ?>" data-ext="<?= $ext ?>" data-event="<?= htmlspecialchars($row['event_name']) ?>" data-date="<?= $row['uploaded_on'] ?>">
                    <div class="media-container">
                        <?php if ($isImage) { ?>
                            <img src="<?= $file ?>" alt="Event Media" loading="lazy">
                            <div class="media-type-badge"><i class="fas fa-image"></i> Image</div>
                        <?php } elseif ($isVideo) { ?>
                            <video preload="metadata">
                                <source src="<?= $file ?>" type="video/<?= $ext ?>">
                            </video>
                            <div class="media-type-badge"><i class="fas fa-video"></i> Video</div>
                        <?php } else { ?>
                            <div style="height: 220px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; color: #666;">
                                <i class="fas fa-file fa-3x"></i>
                            </div>
                            <div class="media-type-badge"><i class="fas fa-file"></i> File</div>
                        <?php } ?>
                        <div class="media-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                    <div class="media-info">
                        <div class="event-name"><?= htmlspecialchars($row['event_name']) ?></div>
                        <div class="uploaded-on">
                            <i class="fas fa-clock"></i>
                            <?= date('M j, Y - g:i A', strtotime($row['uploaded_on'])) ?>
                        </div>
                    </div>
                </div>
        <?php }} else { ?>
            <div class="no-media">
                <div>
                    <i class="fas fa-images"></i>
                    <h3>No media found</h3>
                    <p>Try adjusting your search filters or check back later for new content.</p>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Enhanced Lightbox -->
    <div id="lightbox">
        <div class="lightbox-counter" id="lightbox-counter"></div>
        <div class="lightbox-info" id="lightbox-info"></div>
        <span class="close-btn" onclick="closeLightbox()">
            <i class="fas fa-times"></i>
        </span>
        <span class="nav-btn prev-btn" onclick="navigate(-1)">
            <i class="fas fa-chevron-left"></i>
        </span>
        <span class="nav-btn next-btn" onclick="navigate(1)">
            <i class="fas fa-chevron-right"></i>
        </span>
        <div id="lightbox-content"></div>
        <div class="lightbox-controls">
            <button class="lightbox-btn" onclick="navigate(-1)" title="Previous">
                <i class="fas fa-chevron-left"></i>
            </button>
            <a id="downloadLink" class="lightbox-btn" download title="Download">
                <i class="fas fa-download"></i>
            </a>
            <button class="lightbox-btn" onclick="toggleFullscreen()" title="Fullscreen">
                <i class="fas fa-expand"></i>
            </button>
            <button class="lightbox-btn" onclick="navigate(1)" title="Next">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>

    <script>
        let currentIndex = 0;
        const items = document.querySelectorAll('.media-card');
        const lightbox = document.getElementById('lightbox');
        const content = document.getElementById('lightbox-content');
        const downloadLink = document.getElementById('downloadLink');
        const counter = document.getElementById('lightbox-counter');
        const info = document.getElementById('lightbox-info');

        // Add click event to media cards
        items.forEach((item, index) => {
            item.addEventListener('click', () => openLightbox(index));
        });

        function openLightbox(index) {
            currentIndex = index;
            const item = items[index];
            const src = item.dataset.src;
            const ext = item.dataset.ext;
            const eventName = item.dataset.event;
            const uploadDate = item.dataset.date;
            
            // Update counter
            counter.textContent = `${index + 1} / ${items.length}`;
            
            // Update info
            info.innerHTML = `
                <div style="font-weight: 600; margin-bottom: 5px;">${eventName}</div>
                <div style="font-size: 14px; opacity: 0.8;">
                    <i class="fas fa-clock"></i> ${new Date(uploadDate).toLocaleDateString()}
                </div>
            `;
            
            // Show loading
            content.innerHTML = '<div class="loading"></div>';
            
            // Load media
            if(['jpg','jpeg','png','gif','webp'].includes(ext)){
                const img = new Image();
                img.onload = () => {
                    content.innerHTML = `<img src="${src}" alt="Media">`;
                };
                img.src = src;
            } else if(['mp4','mov','avi','mkv','webm'].includes(ext)) {
                content.innerHTML = `<video controls autoplay><source src="${src}" type="video/${ext}"></video>`;
            } else {
                content.innerHTML = `<div style="color: white; text-align: center; padding: 40px;">
                    <i class="fas fa-file fa-4x" style="margin-bottom: 20px;"></i>
                    <div>File format not supported for preview</div>
                </div>`;
            }
            
            downloadLink.href = src;
            downloadLink.download = src.split('/').pop();
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function navigate(direction) {
            if (items.length === 0) return;
            currentIndex = (currentIndex + direction + items.length) % items.length;
            openLightbox(currentIndex);
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                lightbox.requestFullscreen().catch(err => {
                    console.log(`Error attempting to enable fullscreen: ${err.message}`);
                });
            } else {
                document.exitFullscreen();
            }
        }

        // Keyboard navigation
        window.addEventListener('keydown', (e) => {
            if (lightbox.style.display === 'flex') {
                switch(e.key) {
                    case 'ArrowLeft':
                        navigate(-1);
                        break;
                    case 'ArrowRight':
                        navigate(1);
                        break;
                    case 'Escape':
                        closeLightbox();
                        break;
                    case ' ':
                        e.preventDefault();
                        const video = content.querySelector('video');
                        if (video) {
                            video.paused ? video.play() : video.pause();
                        }
                        break;
                    case 'f':
                    case 'F':
                        toggleFullscreen();
                        break;
                }
            }
        });

        // Close lightbox when clicking outside content
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });

        // Smooth filter form submission
        document.getElementById('search').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.target.closest('form').submit();
            }
        });

        // Add loading animation to filter button
        document.querySelector('.filter-section button').addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
        });

        // Lazy loading for images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }

        // Add entrance animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        });

        document.querySelectorAll('.media-card').forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
            observer.observe(card);
        });
    </script>
</body>
</html>