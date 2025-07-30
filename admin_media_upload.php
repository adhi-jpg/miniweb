<?php
session_start();
include "config.php";

// Only admin can upload
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $event_id = intval($_POST['event_id']);
    
    // File upload path
    $target_dir = "uploads/media/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_name = time() . "_" . basename($_FILES["media_file"]["name"]);
    $target_file = $target_dir . $file_name;

    // Validate file type (images & videos)
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mov', 'avi'];
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    if (!in_array($file_type, $allowed_types)) {
        $msg = "❌ Only images and videos are allowed!";
    } else {
        if (move_uploaded_file($_FILES["media_file"]["tmp_name"], $target_file)) {
            $stmt = $conn->prepare("INSERT INTO media_gallery (event_id, file_url, uploaded_on) VALUES (?, ?, NOW())");
            $stmt->bind_param("is", $event_id, $target_file);
            if ($stmt->execute()) {
                $msg = "✅ Media uploaded successfully!";
            } else {
                $msg = "❌ Database error!";
            }
            $stmt->close();
        } else {
            $msg = "❌ Failed to upload file!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Upload Event Media</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            /* subtle dots overlay */
            position: relative;
            overflow-x: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><circle fill="white" fill-opacity="0.04" cx="50" cy="50" r="1"/></svg>');
            pointer-events: none;
            z-index: 0;
        }
        .container {
            background: rgba(255,255,255, 0.12);
            box-shadow: 0 12px 45px rgba(44, 62, 80, .18);
            border-radius: 20px;
            max-width: 420px;
            margin: 60px auto;
            padding: 46px 36px 35px 36px;
            backdrop-filter: blur(15px);
            position: relative;
            z-index: 1;
            border: 1.5px solid rgba(255,255,255,0.24);
            animation: fadeInUp 0.7s cubic-bezier(.68,-0.55,.27,1.55);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(65px);}
            to { opacity: 1; transform: translateY(0);}
        }
        h2 {
            text-align: center;
            color: #fff;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: 1.5px;
            text-shadow: 0 2px 14px #3333cc11;
        }
        label {
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            letter-spacing: 0.2px;
            margin-bottom: 5px;
            display: block;
        }
        input[type="number"], input[type="file"] {
            padding: 15px 14px;
            border-radius: 12px;
            border: 1.2px solid rgba(255,255,255,0.17);
            background: rgba(255,255,255,0.09);
            color: #262535;
            font-size: 1.05rem;
            font-weight: 500;
            outline: none;
            margin-bottom: 22px;
            width: 100%;
        }
        input[type="number"]:focus, input[type="file"]:focus {
            border-color: #667eea;
            background: rgba(255,255,255,0.18);
        }
        .drop-zone {
            padding: 32px 10px;
            background: linear-gradient(133deg, rgba(244, 233, 251,0.21) 55%, rgba(102,126,234,0.07) 100%);
            border: 2.2px dashed #c3bff6bb;
            border-radius: 15px;
            text-align: center;
            color: #4D3A83;
            font-weight: 600;
            cursor: pointer;
            transition: border-color 0.18s, background 0.15s;
            margin-bottom: 20px;
            position: relative;
        }
        .drop-zone.is-dragover {
            border-color: #764ba2;
            background: rgba(118,75,162,0.19);
        }
        .drop-zone i {
            font-size: 2.6rem;
            margin-bottom: 7px;
            color: #764ba2;
            display: block;
        }
        .preview-area {
            margin: 0 auto 18px auto;
            display: none;
            place-items: center;
            flex-direction: column;
            gap: 7px;
            padding: 8px 0;
        }
        .preview-area img, .preview-area video {
            border-radius: 9px;
            box-shadow: 0 3px 16px #3d32501a;
            max-width: 94%;
            max-height: 170px;
            display: block;
            margin: auto;
        }
        button[type="submit"] {
            width: 100%;
            padding: 16px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 13px;
            font-size: 1.14rem;
            font-weight: 700;
            letter-spacing: 1px;
            box-shadow: 0 4px 19px #0002;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.19s, transform 0.12s;
            position: relative;
            overflow: hidden;
        }
        button[type="submit"]:hover {
            background: linear-gradient(135deg, #4f9ef3 0%, #764ba2 100%);
            transform: translateY(-2px) scale(1.01);
        }
        .msg {
            text-align: center;
            font-weight: 700;
            font-size: 1.12rem;
            padding: 13px;
            margin: 28px 0 10px 0;
            border-radius: 9px;
            min-height: 36px;
        }
        .msg:empty { display: none;}
        .msg:before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            margin-right: 8px;
        }
        .msg:contains('successfully') { color: #0c8c59; background: #e0faee;}
        .msg:contains('❌'), .msg:contains('Failed') { color: #d13b3b; background: #ffeaea;}
        /* Custom msg icons */
        .msg:contains('✅'):before { content: "\f058"; color: #0c8c59;}
        .msg:contains('❌'):before { content: "\f056"; color: #b93535;}
        
        /* Mobile responsiveness */
        @media (max-width:600px) {
            .container {
                max-width: 98vw;
                padding: 24px 6vw 22px 6vw;
            }
            h2 { font-size: 1.3rem; }
            .preview-area img, .preview-area video { max-width: 99%; max-height: 110px;}
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>
            <i class="fas fa-upload"></i> Upload Event Media
        </h2>
        <?php if($msg) echo "<div class='msg'>$msg</div>"; ?>
        <form method="POST" enctype="multipart/form-data" id="mediaForm" autocomplete="off">
            <label for="event_id"><i class="fas fa-hashtag"></i> Event ID</label>
            <input type="number" name="event_id" id="event_id" min="1" placeholder="Enter Event ID" required>
            
            <label>
                <i class="fas fa-photo-film"></i> Select Media File (Image/Video)
            </label>
            <div class="drop-zone" id="dropZone">
                <i class="fas fa-cloud-upload-alt"></i>
                <span id="dropMsg">Drag &amp; Drop or click to select file</span>
                <input type="file" name="media_file" id="media_file" required hidden accept="image/*,video/*"/>
            </div>
            
            <div class="preview-area" id="previewArea">
                <!-- JS will display image/video preview here -->
            </div>
            <button type="submit"><i class="fas fa-arrow-up"></i> Upload</button>
        </form>
    </div>
<script>
    // --- Drag & Drop and Preview Logic ---
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('media_file');
    const previewArea = document.getElementById('previewArea');
    const dropMsg = document.getElementById('dropMsg');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e=>{
        e.preventDefault();
        dropZone.classList.add('is-dragover');
    });
    dropZone.addEventListener('dragleave', e=>{
        dropZone.classList.remove('is-dragover');
    });
    dropZone.addEventListener('drop', e=>{
        e.preventDefault();
        dropZone.classList.remove('is-dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files; // triggers change event
            showPreview(fileInput.files[0]);
        }
    });
    fileInput.addEventListener('change', function(){
        if (fileInput.files && fileInput.files[0]) {
            showPreview(fileInput.files[0]);
        } else {
            previewArea.style.display = "none";
            previewArea.innerHTML = '';
        }
    });
    function showPreview(file) {
        previewArea.style.display = "block";
        previewArea.innerHTML = '';
        let type = file.type;
        if (type.startsWith('image/')) {
            let img = document.createElement('img');
            img.alt = 'Image Preview';
            img.src = URL.createObjectURL(file);
            previewArea.appendChild(img);
        } else if (type.startsWith('video/')) {
            let vid = document.createElement('video');
            vid.src = URL.createObjectURL(file);
            vid.controls = true;
            previewArea.appendChild(vid);
        } else {
            previewArea.innerHTML = "<em style='color:#ad2458;'>No preview available</em>";
        }
    }

    // Optional: Prevent accidental double submit
    document.getElementById('mediaForm').addEventListener('submit', function(e) {
        const btn = this.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    });
</script>
</body>
</html>


