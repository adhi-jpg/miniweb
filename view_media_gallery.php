<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

$filter_event = isset($_GET['filter_event']) ? intval($_GET['filter_event']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = [];
if ($filter_event > 0) $where[] = "mg.event_id=$filter_event";
if (!empty($search)) $where[] = "mg.file_url LIKE '%$search%'";
$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$media_result = $conn->query("SELECT mg.*, e.title AS event_name FROM media_gallery mg 
LEFT JOIN events e ON mg.event_id=e.event_id $whereSQL ORDER BY mg.uploaded_on DESC");
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a0a0a 0%, #2d0a0f 50%, #1a0a0a 100%);
            min-height: 100vh; color: #e5e5e5;
        }

        header { 
            background: linear-gradient(135deg, #8b0000 0%, #dc143c 50%, #8b0000 100%);
            padding: 40px 20px; text-align: center;
            box-shadow: 0 10px 40px rgba(220, 20, 60, 0.4);
            position: relative; overflow: hidden;
        }

        header::before {
            content: ''; position: absolute; top: -50%; left: -50%;
            width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(220, 20, 60, 0.1) 1px, transparent 1px);
            background-size: 50px 50px; animation: float 20s linear infinite;
        }

        @keyframes float { 0% { transform: translate(0); } 100% { transform: translate(-50px, -50px); } }

        h1 { font-size: 2.5em; font-weight: 300; color: white; 
             text-shadow: 0 0 20px rgba(220, 20, 60, 0.8); position: relative; z-index: 1; }

        .subtitle { margin-top: 10px; opacity: 0.9; position: relative; z-index: 1; }

        .filter-section { 
            background: rgba(20, 20, 20, 0.95); backdrop-filter: blur(15px);
            padding: 25px; margin: -20px 20px 20px; border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); border: 1px solid rgba(220, 20, 60, 0.3);
        }

        .filter-container { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }

        .filter-group { display: flex; flex-direction: column; gap: 8px; }
        .filter-group label { font-weight: 600; color: #dc143c; font-size: 14px; }

        .filter-section select, .filter-section input { 
            padding: 12px 16px; border: 1px solid rgba(220, 20, 60, 0.5); border-radius: 8px;
            background: rgba(30, 30, 30, 0.9); color: #e5e5e5; width: 200px;
            transition: all 0.3s ease;
        }

        .filter-section select:focus, .filter-section input:focus {
            outline: none; border-color: #dc143c; box-shadow: 0 0 10px rgba(220, 20, 60, 0.5);
        }

        .filter-section button { 
            padding: 12px 25px; background: linear-gradient(135deg, #8b0000, #dc143c);
            border: none; color: white; font-weight: 600; cursor: pointer;
            border-radius: 8px; transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(220, 20, 60, 0.3);
        }

        .filter-section button:hover { 
            transform: translateY(-2px); box-shadow: 0 8px 20px rgba(220, 20, 60, 0.5);
        }

        .gallery-stats { 
            text-align: center; margin: 20px; padding: 15px;
            background: rgba(30, 30, 30, 0.8); border-radius: 10px; color: #dc143c;
        }

        .gallery { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px; padding: 30px; max-width: 1400px; margin: 0 auto;
        }

        .media-card { 
            background: rgba(25, 25, 25, 0.9); border-radius: 15px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6); cursor: pointer;
            transition: all 0.4s ease; border: 1px solid rgba(220, 20, 60, 0.2);
        }

        .media-card:hover { 
            transform: translateY(-10px); box-shadow: 0 20px 40px rgba(220, 20, 60, 0.3);
            border-color: rgba(220, 20, 60, 0.6);
        }

        .media-container { position: relative; height: 220px; overflow: hidden; }

        .media-card img, .media-card video { 
            width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;
        }

        .media-card:hover img, .media-card:hover video { transform: scale(1.1); }

        .media-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(45deg, rgba(139, 0, 0, 0.8), rgba(220, 20, 60, 0.8));
            opacity: 0; transition: opacity 0.3s ease; display: flex;
            align-items: center; justify-content: center; color: white; font-size: 24px;
        }

        .media-card:hover .media-overlay { opacity: 1; }

        .media-info { padding: 20px; }
        .event-name { font-weight: 700; color: #e5e5e5; margin-bottom: 8px; }
        .uploaded-on { color: #999; font-size: 14px; display: flex; align-items: center; gap: 6px; }

        .media-type-badge {
            position: absolute; top: 15px; right: 15px;
            background: rgba(220, 20, 60, 0.9); color: white; padding: 6px 12px;
            border-radius: 15px; font-size: 12px; font-weight: 600;
        }

        .no-media { 
            text-align: center; padding: 60px 20px; color: #666; grid-column: 1 / -1;
        }
        .no-media i { font-size: 4em; margin-bottom: 20px; opacity: 0.3; }

        /* Lightbox */
        #lightbox { 
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.95); z-index: 9999; align-items: center;
            justify-content: center; flex-direction: column;
        }

        #lightbox-content { max-width: 90vw; max-height: 80vh; position: relative; }
        #lightbox img, #lightbox video { max-width: 100%; max-height: 100%; border-radius: 10px; }

        .close-btn, .nav-btn {
            position: absolute; color: white; cursor: pointer; transition: all 0.3s ease;
            display: flex; align-items: center; justify-content: center; border-radius: 50%;
        }

        .close-btn { 
            top: 20px; right: 30px; font-size: 30px; width: 50px; height: 50px;
        }

        .nav-btn { 
            top: 50%; transform: translateY(-50%); font-size: 40px; width: 60px; height: 60px;
            background: rgba(220, 20, 60, 0.8);
        }

        .prev-btn { left: 30px; } .next-btn { right: 30px; }

        .close-btn:hover, .nav-btn:hover { background: rgba(220, 20, 60, 0.9); }

        .lightbox-controls {
            position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 10px; background: rgba(0, 0, 0, 0.8); 
            padding: 10px 20px; border-radius: 25px;
        }

        .lightbox-btn {
            background: none; border: none; color: white; font-size: 18px;
            cursor: pointer; padding: 8px; border-radius: 50%; transition: all 0.3s ease;
            width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;
        }

        .lightbox-btn:hover { background: rgba(220, 20, 60, 0.6); }

        .lightbox-counter {
            position: absolute; top: 20px; left: 50%; transform: translateX(-50%);
            background: rgba(220, 20, 60, 0.9); color: white; padding: 8px 15px;
            border-radius: 15px; font-weight: 600;
        }

        .lightbox-info {
            position: absolute; top: 20px; left: 30px; color: white;
            background: rgba(0, 0, 0, 0.8); padding: 10px 15px; border-radius: 10px;
        }

        @media (max-width: 768px) {
            .gallery { grid-template-columns: 1fr; padding: 20px 15px; }
            .filter-container { flex-direction: column; }
            .filter-section select, .filter-section input { width: 100%; }
            h1 { font-size: 2em; }
        }

        ::-webkit-scrollbar { width: 10px; }
        ::-webkit-scrollbar-track { background: #1a1a1a; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(135deg, #8b0000, #dc143c); border-radius: 5px; }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-images"></i> MDC Club Media Gallery</h1>
        <div class="subtitle">Premium Collection of Moments</div>
    </header>

    <div class="filter-section">
        <form method="GET">
            <div class="filter-container">
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> Event</label>
                    <select name="filter_event" onchange="this.form.submit()">
                        <option value="0">All Events</option>
                        <?php while ($ev = $events->fetch_assoc()) { ?>
                            <option value="<?= $ev['event_id'] ?>" <?= $filter_event==$ev['event_id']?'selected':'' ?>>
                                <?= htmlspecialchars($ev['title']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search files...">
                </div>
                <button type="submit"><i class="fas fa-filter"></i> Filter</button>
            </div>
        </form>
    </div>

    <?php 
    $total = $media_result->num_rows;
    if ($total > 0) echo "<div class='gallery-stats'><i class='fas fa-photo-video'></i> $total media file" . ($total > 1 ? 's' : '') . "</div>";
    ?>

    <div class="gallery">
        <?php 
        if ($media_result->num_rows > 0) {
            while ($row = $media_result->fetch_assoc()) { 
                $file = $row['file_url']; 
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); 
                $isVideo = in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm']);
                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        ?>
                <div class="media-card" data-src="<?= $file ?>" data-ext="<?= $ext ?>" 
                     data-event="<?= htmlspecialchars($row['event_name']) ?>" data-date="<?= $row['uploaded_on'] ?>">
                    <div class="media-container">
                        <?php if ($isImage) { ?>
                            <img src="<?= $file ?>" alt="Event Media" loading="lazy">
                            <div class="media-type-badge"><i class="fas fa-image"></i> Image</div>
                        <?php } elseif ($isVideo) { ?>
                            <video preload="metadata"><source src="<?= $file ?>" type="video/<?= $ext ?>"></video>
                            <div class="media-type-badge"><i class="fas fa-video"></i> Video</div>
                        <?php } else { ?>
                            <div style="height: 220px; display: flex; align-items: center; justify-content: center; color: #666;">
                                <i class="fas fa-file fa-3x"></i>
                            </div>
                            <div class="media-type-badge"><i class="fas fa-file"></i> File</div>
                        <?php } ?>
                        <div class="media-overlay"><i class="fas fa-search-plus"></i></div>
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
                <i class="fas fa-images"></i>
                <h3>No media found</h3>
                <p>Try adjusting filters or check back later.</p>
            </div>
        <?php } ?>
    </div>

    <div id="lightbox">
        <div class="lightbox-counter" id="counter"></div>
        <div class="lightbox-info" id="info"></div>
        <span class="close-btn" onclick="closeLightbox()"><i class="fas fa-times"></i></span>
        <span class="nav-btn prev-btn" onclick="navigate(-1)"><i class="fas fa-chevron-left"></i></span>
        <span class="nav-btn next-btn" onclick="navigate(1)"><i class="fas fa-chevron-right"></i></span>
        <div id="lightbox-content"></div>
        <div class="lightbox-controls">
            <button class="lightbox-btn" onclick="navigate(-1)"><i class="fas fa-chevron-left"></i></button>
            <a id="downloadLink" class="lightbox-btn" download><i class="fas fa-download"></i></a>
            <button class="lightbox-btn" onclick="toggleFullscreen()"><i class="fas fa-expand"></i></button>
            <button class="lightbox-btn" onclick="navigate(1)"><i class="fas fa-chevron-right"></i></button>
        </div>
    </div>

    <script>
        let currentIndex = 0;
        const items = document.querySelectorAll('.media-card');
        const lightbox = document.getElementById('lightbox');
        const content = document.getElementById('lightbox-content');

        items.forEach((item, index) => {
            item.addEventListener('click', () => openLightbox(index));
        });

        function openLightbox(index) {
            currentIndex = index;
            const item = items[index];
            const src = item.dataset.src, ext = item.dataset.ext;
            
            document.getElementById('counter').textContent = `${index + 1} / ${items.length}`;
            document.getElementById('info').innerHTML = `
                <div style="font-weight: 600; margin-bottom: 5px;">${item.dataset.event}</div>
                <div style="font-size: 14px; opacity: 0.8;">
                    <i class="fas fa-clock"></i> ${new Date(item.dataset.date).toLocaleDateString()}
                </div>
            `;
            
            if(['jpg','jpeg','png','gif','webp'].includes(ext)){
                content.innerHTML = `<img src="${src}" alt="Media">`;
            } else if(['mp4','mov','avi','mkv','webm'].includes(ext)) {
                content.innerHTML = `<video controls autoplay><source src="${src}" type="video/${ext}"></video>`;
            } else {
                content.innerHTML = `<div style="color: white; text-align: center; padding: 40px;">
                    <i class="fas fa-file fa-4x"></i><div>Preview not available</div></div>`;
            }
            
            document.getElementById('downloadLink').href = src;
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightbox.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function navigate(dir) {
            currentIndex = (currentIndex + dir + items.length) % items.length;
            openLightbox(currentIndex);
        }

        function toggleFullscreen() {
            !document.fullscreenElement ? lightbox.requestFullscreen() : document.exitFullscreen();
        }

        window.addEventListener('keydown', (e) => {
            if (lightbox.style.display === 'flex') {
                if (e.key === 'ArrowLeft') navigate(-1);
                else if (e.key === 'ArrowRight') navigate(1);
                else if (e.key === 'Escape') closeLightbox();
            }
        });

        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) closeLightbox();
        });
    </script>
</body>
</html>