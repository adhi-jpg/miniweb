<?php
session_start();
include "config.php";

// ✅ Check admin login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

// --- BULK DELETE ---
if (isset($_POST['bulk_delete']) && !empty($_POST['delete_ids'])) {
    foreach ($_POST['delete_ids'] as $delete_id) {
        $delete_id = intval($delete_id);
        $res = $conn->query("SELECT file_url FROM media_gallery WHERE id=$delete_id");
        if ($res->num_rows > 0) {
            $file = $res->fetch_assoc()['file_url'];
            if (file_exists($file)) unlink($file);
        }
        $conn->query("DELETE FROM media_gallery WHERE id=$delete_id");
    }
    $msg = "🗑️ Selected media deleted successfully!";
}

// --- DELETE SINGLE ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $res = $conn->query("SELECT file_url FROM media_gallery WHERE id=$delete_id");
    if ($res->num_rows > 0) {
        $file = $res->fetch_assoc()['file_url'];
        if (file_exists($file)) unlink($file);
    }
    $conn->query("DELETE FROM media_gallery WHERE id=$delete_id");
    $msg = "🗑️ Media deleted successfully!";
}

// --- UPDATE MEDIA ---
if (isset($_POST['update_media'])) {
    $media_id = intval($_POST['media_id']);
    $event_id = intval($_POST['event_id']);
    $new_file = $_FILES['new_media_file']['name'];

    if (!empty($new_file)) {
        $target_dir = "uploads/media/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . time() . "_" . basename($new_file);

        if (move_uploaded_file($_FILES['new_media_file']['tmp_name'], $target_file)) {
            // Delete old file
            $old = $conn->query("SELECT file_url FROM media_gallery WHERE id=$media_id")->fetch_assoc()['file_url'];
            if (file_exists($old)) unlink($old);
            $conn->query("UPDATE media_gallery SET event_id=$event_id, file_url='$target_file' WHERE id=$media_id");
            $msg = "✅ Media updated with new file!";
        } else {
            $msg = "❌ Failed to upload new file!";
        }
    } else {
        $conn->query("UPDATE media_gallery SET event_id=$event_id WHERE id=$media_id");
        $msg = "✅ Event updated for media!";
    }
}

// --- ADD MEDIA ---
if (isset($_POST['add_media'])) {
    $event_id = intval($_POST['event_id']);
    $file_name = basename($_FILES['media_file']['name']);
    $target_dir = "uploads/media/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    $target_file = $target_dir . time() . "_" . $file_name;

    if (move_uploaded_file($_FILES['media_file']['tmp_name'], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO media_gallery (event_id, file_url, uploaded_on) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $event_id, $target_file);
        $stmt->execute();
        $stmt->close();
        $msg = "✅ Media uploaded successfully!";
    } else {
        $msg = "❌ File upload failed!";
    }
}

// --- Filters ---
$filter_event = isset($_GET['filter_event']) ? intval($_GET['filter_event']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = [];
if ($filter_event > 0) $where[] = "mg.event_id=$filter_event";
if (!empty($search)) $where[] = "mg.file_url LIKE '%$search%'";
$whereSQL = count($where) ? "WHERE " . implode(" AND ", $where) : "";

// --- Fetch data ---
$media_result = $conn->query("SELECT mg.*, e.title AS event_name FROM media_gallery mg 
LEFT JOIN events e ON mg.event_id=e.event_id $whereSQL ORDER BY mg.uploaded_on DESC");
$events = $conn->query("SELECT event_id, title FROM events ORDER BY date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Media Upload</title>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <meta name="viewport" content="width=device-width,initial-scale=1"/>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 60%, #f093fb 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            margin: 0;
            min-height: 100vh;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed; top:0; left:0;
            width:100vw;height:100vh;
            background:url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><circle fill="white" fill-opacity="0.04" cx="50" cy="50" r="1"/></svg>');
            pointer-events: none;
            z-index:0;
        }
        h1 {
            color: #fff;
            text-align: center;
            font-size:2.1rem;
            font-weight:900;
            letter-spacing: 1.5px;
            text-shadow: 0 2px 14px #3333cc22;
        }
        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 18px;
            padding: 11px 16px;
            border-radius: 10px;
            max-width: 520px;
            margin-left: auto; margin-right: auto;
            font-size: 1.15rem;
            background: #e4ffe3d6;
            color: #147543;
        }
        .message:empty { display: none; }
        .form-section {
            background: rgba(255,255,255, 0.11);
            box-shadow: 0 7px 24px #514e9940;
            border-radius: 19px;
            margin-bottom: 36px;
            max-width: 515px;
            margin: 32px auto 26px auto;
            padding: 33px 29px 22px 29px;
            border: 1.4px solid rgba(255,255,255,0.22);
            animation: fadeInUp 0.6s;
        }
        @keyframes fadeInUp { from{opacity: 0; transform:translateY(45px);} to{opacity: 1;transform: translateY(0);} }
        label {
            font-weight: bold;
            color: #4a00e0;
            margin-bottom: 7px;
            display:block;
        }
        input[type="file"], input[type="text"], select, input[type="number"] {
            width: 100%; font-size:1rem; padding: 13px 12px;
            border: 1.2px solid #dad7f4;
            margin-bottom: 16px;
            background: rgba(255,255,255,0.13);
            border-radius: 9px;
            color: #372852;
        }
        input[type="file"]:focus, input[type="text"]:focus, select:focus { border-color:#764ba2; background:rgba(255,255,255,0.18);}
        button {
            width: 100%; font-weight: 700; border: none; outline: none;
            padding: 15px 10px; border-radius: 13px;
            background: linear-gradient(135deg, #5f87e7 0%, #764ba2 100%);
            color: #fff;
            font-size: 1.09rem;
            margin-top: 8px;
            cursor: pointer;
            box-shadow: 0 4px 19px #0001;
            transition: background 0.17s, transform 0.11s;
        }
        button:hover { background: linear-gradient(135deg, #4faac8 0%, #764ba2 100%); transform: translateY(-2px) scale(1.01);}
        .filter-section {
            background: rgba(255,255,255,0.10);
            box-shadow: 0 1px 11px #a5a7fa1e;
            border-radius: 15px;
            max-width: 520px;
            margin: 0 auto 24px auto;
            padding: 21px 18px 11px 18px;
        }
        .filter-section form {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }
        .filter-section select, .filter-section input[type="text"] {
            width: auto;min-width: 130px;margin-top: 0;padding: 9px 10px;
        }
        .filter-section button {
            width: auto;min-width:50px;font-size:1rem;padding: 9px 27px;
        }
        /* Gallery Grid */
        .gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            justify-content: center;
        }
        .media-card {
            width: 320px;
            background:rgba(255,255,255,0.13);
            box-shadow: 0 7px 27px #583b5c25;
            border-radius: 18px;
            padding: 19px 13px 14px 13px;
            border: 1.4px solid rgba(255,255,255,0.19);
            text-align: center;
            position: relative;
            display: flex; flex-direction:column;align-items:center;
            transition: transform 0.16s, box-shadow 0.18s, background 0.15s;
            animation: fadeInCard 0.7s cubic-bezier(.68,-0.55,.27,1.55);
        }
        .media-card:hover { 
            transform: translateY(-5px) scale(1.01);
            background:rgba(255,255,255,0.17); 
            box-shadow: 0 13px 42px #764ba264;
        }
        @keyframes fadeInCard { from{opacity:0;transform:translateY(26px);} to{opacity:1;transform:translateY(0);} }
        .media-card img, .media-card video {
            max-width: 100%;
            border-radius: 10px;
            min-height: 153px;
            max-height: 203px;
            object-fit: cover;
            margin-top: 4px;
            box-shadow: 0 3px 12px #2f21572b;
            background: #f6f6fa;
            cursor: zoom-in;
            margin-bottom: 8px;
        }
        .media-card input[type="checkbox"] {
            position: absolute;top:17px;left:17px;z-index:1;transform:scale(1.34);
            accent-color:#764ba2;
        }
        .event-name {
            color: #764ba2;
            text-align: center;
            font-weight: 700;
            margin: 7px 0 2px 0;
            letter-spacing: 0.11px;
            font-size: 1.1rem;
        }
        .uploaded-on {
            color: #55576b;
            font-size: 12px;
            margin-bottom: 2px;
            text-align: center;
        }
        .action-buttons {
            display: flex;
            gap: 7px;
            justify-content: center;
            margin-top: 7px;
        }
        .delete-btn, .action-buttons button, .deleteAll-btn {
            background: linear-gradient(135deg, #ff5d7a 20%, #a71d2a 96%);
            color: #fff; padding: 8.5px 15px;
            border-radius: 8px; font-size: 14px; border:none; cursor:pointer;
            font-weight: bold; box-shadow:0 2px 7px #e0476180;
            transition: background 0.08s, transform 0.1s;
        }
        .delete-btn:hover,.action-buttons button:hover, .deleteAll-btn:hover {
            background: linear-gradient(135deg, #d6112f, #764ba2 90%);
            transform:scale(1.04) translateY(-1px);
        }
        .action-buttons button { background:linear-gradient(135deg,#684adf 0,#47add6 100%);}
        .action-buttons button:hover {background:linear-gradient(135deg,#1a70e4 0,#7e5cb8 100%);}
        .deleteAll-btn { width:auto;min-width:145px;margin:0 auto 19px auto;display:block;}
        /* Update Slide form */
        .update-form {
            display: none;
            background: rgba(255,255,255,0.18);
            padding: 14px 13px 11px 13px;
            margin-top: 13px;
            border-radius: 8px;
            box-shadow: 0 1px 8px #4a00e026;
            animation: slideDown 0.22s;
        }
        @keyframes slideDown { from{opacity:0;transform:translateY(-18px);} to{opacity:1;transform:translateY(0);} }
        .update-form label { color:#420a8d; }
        .update-form input, .update-form select {margin-bottom:11px;}
        .update-form button {margin-top:4px;}
        /* Lightbox overlay */
        .lightbox {
            position: fixed;top:0;left:0;width:100vw;height:100vh;
            background: rgba(44,62,80,.87);
            display: flex;align-items:center;justify-content:center;
            z-index: 10000;
            cursor: zoom-out;
            animation: fadeInLightbox .21s;
        }
        @keyframes fadeInLightbox { from{opacity:0;} to{opacity:1;} }
        .lightbox-content {
            max-width: 90vw; max-height: 80vh;
            box-shadow: 0 8px 38px #2c3150ad;
            border-radius: 18px;
            background: #222;
            padding: 14px 15px 7px 15px;
            display: flex; flex-direction: column; align-items: center;
            position: relative;
        }
        .lightbox img, .lightbox video { max-width:78vw; max-height:68vh; border-radius: 12px;}
        .close-lightbox {
            position: absolute; top:12px;right:21px;font-size:2rem;
            color:#fff;background:rgba(44,62,80,.09);border-radius:10px;
            cursor:pointer;transition:background 0.1s;}
        .close-lightbox:hover { background:#764ba2dd; color:#fff;}
        .select-all-box {margin: 0 0 0 18px; font-weight:600;}
        input[type="checkbox"].select-all {transform: scale(1.19);}
        /* Mobile */
        @media (max-width: 800px) {
            .gallery { gap:17px;}
            .media-card { width:96vw;max-width:350px; }
            .form-section, .filter-section {max-width:95vw; padding:18px 4vw 13px 4vw}
        }
    </style>
    <script>
        function toggleUpdateForm(id) {
            var form = document.getElementById('updateForm_' + id);
            form.style.display = (form.style.display === 'block') ? 'none' : 'block';
        }
        function toggleAll(source) {
            let checkboxes = document.getElementsByName('delete_ids[]');
            for (let i=0; i<checkboxes.length; i++) checkboxes[i].checked = source.checked;
        }
        // LIGHTBOX JS
        document.addEventListener('DOMContentLoaded',()=>{
            // Lightbox for img/video
            function openLightbox(url, type) {
                const lb = document.createElement('div');
                lb.className = 'lightbox';
                lb.onclick = e=>{if(e.target===lb)lb.remove();}
                lb.innerHTML = `
                    <div class="lightbox-content">
                        <span class="close-lightbox" onclick="this.closest('.lightbox').remove()">&times;</span>
                        ${
                            type==='video'
                                ? `<video src="${url}" controls autoplay style="background:#222"></video>`
                                : `<img src="${url}" alt="Event Media" />`
                        }
                    </div>
                `;
                document.body.appendChild(lb);
                document.body.style.overflow='hidden';
                lb.querySelector('.close-lightbox').onclick=function(){ lb.remove(); document.body.style.overflow=''; }
                lb.tabIndex=0; lb.focus();
                lb.onkeydown = function(e){ if(e.key==='Escape') lb.remove(); }
            }
            // Attach to all img/video
            document.querySelectorAll('.gallery .media-card img, .gallery .media-card video').forEach(media=>{
                media.addEventListener('click',function(e){
                    openLightbox(media.getAttribute('src') || media.querySelector('source')?.getAttribute('src'), 
                        media.tagName.toLowerCase());
                });
            });
            // Select all bulk
            document.getElementById('selectAll')?.addEventListener('change', function(){
                toggleAll(this);
            });
        });
    </script>
</head>
<body>
    <h1><i class="fas fa-photo-video"></i> Admin – Manage Media Gallery</h1>
    <?php if ($msg): ?><div class="message"><?= $msg ?></div><?php endif; ?>

    <!-- Upload new media -->
    <div class="form-section">
        <form method="POST" enctype="multipart/form-data">
            <label for="event_sel"><i class="fa fa-calendar-plus"></i> Select Event</label>
            <select name="event_id" id="event_sel" required>
                <option value="">-- Select Event --</option>
                <?php while ($e = $events->fetch_assoc()) { ?>
                    <option value="<?= $e['event_id'] ?>"><?= htmlspecialchars($e['title']) ?></option>
                <?php } ?>
            </select>
            <label><i class="fas fa-photo-film"></i> Upload Media (Image/Video)</label>
            <input type="file" name="media_file" required accept="image/*,video/*">
            <button type="submit" name="add_media"><i class="fas fa-upload"></i> Upload</button>
        </form>
    </div>

    <!-- Filter & Search -->
    <div class="filter-section">
        <form method="GET" autocomplete="off">
            <select name="filter_event" onchange="this.form.submit()">
                <option value="0">All Events</option>
                <?php
                $events2 = $conn->query("SELECT event_id, title FROM events ORDER BY date DESC");
                while ($ev = $events2->fetch_assoc()) { ?>
                    <option value="<?= $ev['event_id'] ?>" <?= $filter_event==$ev['event_id']?'selected':'' ?>>
                        <?= htmlspecialchars($ev['title']) ?>
                    </option>
                <?php } ?>
            </select>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search file...">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>

    <!-- Media List -->
    <form method="POST">
        <button type="submit" name="bulk_delete" class="deleteAll-btn" onclick="return confirm('Delete selected media?')"><i class="fas fa-trash"></i> Bulk Delete</button>
        <label for="selectAll" class="select-all-box">
            <input type="checkbox" id="selectAll" class="select-all" title="Select All"/>&nbsp;Select All on Page
        </label>
        <div class="gallery">
            <?php while ($row = $media_result->fetch_assoc()) {
                $file = $row['file_url'];
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); ?>
                <div class="media-card">
                    <input type="checkbox" name="delete_ids[]" value="<?= $row['id'] ?>" style="cursor:pointer;">
                    <?php if (in_array($ext, ['jpg','jpeg','png','gif'])) { ?>
                        <img src="<?= $file ?>" alt="Event Media" loading="lazy">
                    <?php } else { ?>
                        <video controls>
                            <source src="<?= $file ?>" type="video/<?= $ext ?>">
                        </video>
                    <?php } ?>
                    <div class="event-name"><?= htmlspecialchars($row['event_name']) ?></div>
                    <div class="uploaded-on">Uploaded on <?= $row['uploaded_on'] ?></div>
                    <div class="action-buttons">
                        <a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Delete this media?');">
                            <button type="button" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
                        </a>
                        <button type="button" onclick="toggleUpdateForm(<?= $row['id'] ?>)"><i class="fas fa-edit"></i> Update</button>
                    </div>
                    <!-- Hidden Update Form -->
                    <div class="update-form" id="updateForm_<?= $row['id'] ?>">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="media_id" value="<?= $row['id'] ?>">
                            <label>Change Event</label>
                            <select name="event_id">
                                <?php
                                $events3 = $conn->query("SELECT event_id,title FROM events ORDER BY date DESC");
                                while ($ev = $events3->fetch_assoc()) { ?>
                                    <option value="<?= $ev['event_id'] ?>" <?= ($ev['event_id']==$row['event_id'])?'selected':'' ?>>
                                        <?= htmlspecialchars($ev['title']) ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <label>Change File (Optional)</label>
                            <input type="file" name="new_media_file" accept="image/*,video/*">
                            <button type="submit" name="update_media"><i class="fas fa-save"></i> Save Changes</button>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>
    </form>
</body>
</html>
