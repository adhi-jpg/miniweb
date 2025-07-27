<?php
include "config.php";
session_start();
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please log in.");
}
$submitted_by = $_SESSION['user_id'];
$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = mysqli_real_escape_string($conn, $_POST["title"]);
    $desc = mysqli_real_escape_string($conn, $_POST["description"]);
    $file_path = "";
    $file_data = null;
    $target_dir = "uploads/reports/";
    
    // Create upload directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0775, true);
    }
    
    if (!empty($_FILES["report_file"]["name"])) {
        $filename = basename($_FILES["report_file"]["name"]);
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowed = ["doc", "docx", "pdf", "xls", "xlsx"];
        $new_name = time() . "_" . $filename;
        $target_file = $target_dir . $new_name;
        
        if (in_array($file_ext, $allowed)) {
            // Check file size (10MB limit)
            if ($_FILES["report_file"]["size"] > 10 * 1024 * 1024) {
                $msg = "❌ File size must be less than 10MB.";
            } else {
                // Store file both as path and binary data
                if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
                    $file_path = $target_file;
                    // Read file content to store in database
                    $file_data = file_get_contents($target_file);
                } else {
                    $msg = "❌ File upload failed.";
                }
            }
        } else {
            $msg = "❌ Invalid file type. Only doc, docx, pdf, xls, xlsx allowed.";
        }
    }
    
    if (!$msg) {
        // Use prepared statement for security
        $sql = "INSERT INTO reports (submitted_by, title, description, file_path, file_data) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // Bind parameters - 's' for string, 'b' for blob
            $stmt->bind_param("sssss", $submitted_by, $title, $desc, $file_path, $file_data);
            
            if ($stmt->execute()) {
                $msg = "✅ Report submitted successfully.";
                // Clear form data after successful submission
                $_POST = array();
            } else {
                $msg = "❌ Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $msg = "❌ Database preparation error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .form-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 600px;
        }

        .form-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .form-header svg {
            margin-right: 12px;
            color: #4f46e5;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .message.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: flex;
            align-items: center;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-label svg {
            margin-right: 8px;
            color: #6b7280;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            background-color: #f9fafb;
        }

        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: white;
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            background-color: #f9fafb;
            cursor: pointer;
            transition: border-color 0.2s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: white;
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 32px;
            text-align: center;
            background-color: #f9fafb;
            transition: all 0.2s ease;
            cursor: pointer;
            position: relative;
        }

        .file-upload-area:hover {
            border-color: #4f46e5;
            background-color: #f0f9ff;
        }

        .file-upload-area.dragover {
            border-color: #4f46e5;
            background-color: #eff6ff;
            transform: scale(1.02);
        }

        .file-upload-icon {
            width: 48px;
            height: 48px;
            color: #9ca3af;
            margin: 0 auto 16px;
        }

        .file-upload-text {
            color: #374151;
            font-size: 16px;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .file-upload-subtext {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .file-input {
            display: none;
        }

        .browse-button {
            background-color: #4f46e5;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .browse-button:hover {
            background-color: #4338ca;
        }

        .file-preview {
            margin-top: 16px;
            padding: 12px 16px;
            background-color: #f3f4f6;
            border-radius: 6px;
            display: none;
        }

        .file-preview.show {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .file-info {
            display: flex;
            align-items: center;
        }

        .file-icon {
            width: 20px;
            height: 20px;
            color: #6b7280;
            margin-right: 12px;
        }

        .file-name {
            font-size: 14px;
            color: #374151;
            margin-right: 8px;
        }

        .file-size {
            font-size: 12px;
            color: #6b7280;
        }

        .remove-file {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
        }

        .remove-file:hover {
            background-color: #fee2e2;
        }

        .submit-button {
            width: 100%;
            background-color: #4f46e5;
            color: white;
            padding: 14px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 8px;
        }

        .submit-button:hover {
            background-color: #4338ca;
        }

        .submit-button:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
            </svg>
            <h1 class="form-title">Submit Report</h1>
        </div>

        <?php if ($msg): ?>
            <div class="message <?php echo strpos($msg, '✅') !== false ? 'success' : 'error'; ?>">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3,3H21V5H3V3M3,7H21V9H3V7M3,11H21V13H3V11M3,15H21V17H3V15M3,19H21V21H3V19Z"/>
                    </svg>
                    Report Title:
                </label>
                <input type="text" name="title" class="form-input" placeholder="Enter report title..." value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3,3H21V5H3V3M3,7H15V9H3V7M3,11H21V13H3V11M3,15H15V17H3V15M3,19H21V21H3V19Z"/>
                    </svg>
                    Description:
                </label>
                <textarea name="description" class="form-input form-textarea" placeholder="Describe your report in detail..." required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                    Report File:
                </label>
                <div class="file-upload-area" id="fileUploadArea" onclick="document.getElementById('fileInput').click()">
                    <svg class="file-upload-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                    </svg>
                    <div class="file-upload-text">Drop file here or browse</div>
                    <div class="file-upload-subtext">Supports PDF, DOC, DOCX, XLS, XLSX (Max 10MB)</div>
                    <button type="button" class="browse-button">Browse Files</button>
                    <input type="file" name="report_file" id="fileInput" class="file-input" accept=".pdf,.doc,.docx,.xls,.xlsx">
                </div>
                <div class="file-preview" id="filePreview">
                    <div class="file-info">
                        <svg class="file-icon" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                        </svg>
                        <span class="file-name" id="fileName"></span>
                        <span class="file-size" id="fileSize"></span>
                    </div>
                    <button type="button" class="remove-file" onclick="removeFile()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="submit-button">Submit Report</button>
        </form>
    </div>

    <script>
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });

        // Highlight drop area when item is dragged over it
        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });

        // Handle dropped files
        fileUploadArea.addEventListener('drop', handleDrop, false);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function highlight(e) {
            fileUploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            fileUploadArea.classList.remove('dragover');
        }

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length > 0) {
                const file = files[0];
                // Check file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    return;
                }
                // Check file type
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
                const fileExtensions = ['.pdf', '.doc', '.docx', '.xls', '.xlsx'];
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                
                if (!fileExtensions.includes(fileExtension)) {
                    alert('Only PDF, DOC, DOCX, XLS, XLSX files are allowed');
                    return;
                }
                
                // Create a new file input and assign the file
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                
                displayFile(file);
            }
        }

        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                displayFile(file);
            }
        });

        function displayFile(file) {
            fileName.textContent = file.name;
            fileSize.textContent = `(${(file.size / 1024 / 1024).toFixed(2)} MB)`;
            filePreview.classList.add('show');
        }

        function removeFile() {
            fileInput.value = '';
            filePreview.classList.remove('show');
            fileName.textContent = '';
            fileSize.textContent = '';
        }
    </script>
</body>
<<<<<<< HEAD
</html>
=======
</html>
>>>>>>> 76fb34456558ac3c49663dc9a215ef3b13a9ec65
