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
    $submitted_to = (int) $_POST["submitted_to"];

    $file_path = "";
    $target_dir = "uploads/reports/";

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
            if (move_uploaded_file($_FILES["report_file"]["tmp_name"], $target_file)) {
                $file_path = $target_file;
            } else {
                $msg = "❌ File upload failed.";
            }
        } else {
            $msg = "❌ Invalid file type. Only doc, docx, pdf, xls, xlsx allowed.";
        }
    }

    if (!$msg) {
        $sql = "INSERT INTO reports (submitted_by, submitted_to, title, description, file_path)
                VALUES ('$submitted_by', '$submitted_to', '$title', '$desc', '$file_path')";
        if ($conn->query($sql)) {
            $msg = "✅ Report submitted successfully.";
        } else {
            $msg = "❌ Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7f8;
            padding: 40px;
        }
        .form-container {
            max-width: 600px;
            background: white;
            padding: 25px 30px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #003366;
        }
        label {
            display: block;
            margin-top: 15px;
            color: #333;
        }
        input[type="text"],
        textarea,
        select,
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }
        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background-color: #003366;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0051a3;
        }
        .message {
            text-align: center;
            margin-top: 20px;
            color: #006600;
            font-weight: bold;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Submit Report</h2>

    <?php if (!empty($msg)): ?>
        <div class="message <?= strpos($msg, '❌') !== false ? 'error' : '' ?>">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label for="title">Report Title:</label>
        <input type="text" name="title" id="title" required>

        <label for="description">Description:</label>
        <textarea name="description" id="description" rows="5" required></textarea>

        <label for="submitted_to">Submit To (Faculty/Admin):</label>
        <select name="submitted_to" id="submitted_to" required>
            <option value="">-- Select --</option>
            <?php
            $result = $conn->query("SELECT id, name FROM users WHERE role IN ('faculty', 'admin') AND id != $submitted_by");
            while ($row = $result->fetch_assoc()) {
                echo "<option value='{$row['id']}'>{$row['name']}</option>";
            }
            ?>
        </select>

        <label for="report_file">Attach File (optional):</label>
        <input type="file" name="report_file" id="report_file" accept=".doc,.docx,.pdf,.xls,.xlsx">

        <button type="submit">Submit Report</button>
    </form>
</div>

</body>
</html>
