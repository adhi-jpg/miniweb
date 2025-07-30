<?php
include "config.php";
$result = $conn->query("SELECT * FROM media_gallery ORDER BY uploaded_on DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Media Gallery</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 40px; }
        .gallery { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
        .gallery-item { width: 300px; background: #fff; padding: 10px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .gallery-item img, .gallery-item video { width: 100%; border-radius: 8px; }
        .date { text-align: center; margin-top: 10px; font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <h1 style="text-align:center;">Event Media Gallery</h1>
    <div class="gallery">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="gallery-item">
                <?php $ext = pathinfo($row['file_url'], PATHINFO_EXTENSION); ?>
                <?php if(in_array(strtolower($ext), ['mp4','mov','avi'])): ?>
                    <video controls>
                        <source src="<?= $row['file_url'] ?>" type="video/<?= $ext ?>">
                    </video>
                <?php else: ?>
                    <img src="<?= $row['file_url'] ?>" alt="Event Media">
                <?php endif; ?>
                <div class="date">Uploaded On: <?= $row['uploaded_on'] ?></div>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
