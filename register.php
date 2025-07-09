<?php
session_start();
include "config.php";

$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST["name"]);
    $roll_number = mysqli_real_escape_string($conn, $_POST["roll_number"]);
    $department = mysqli_real_escape_string($conn, $_POST["department"]);
    $phone = mysqli_real_escape_string($conn, $_POST["phone"]);
    $email = mysqli_real_escape_string($conn, $_POST["email"]);
    $password_plain = $_POST["password"];
    $password = password_hash($password_plain, PASSWORD_DEFAULT);

    // ✅ Duplicate checks
    if ($conn->query("SELECT * FROM users WHERE email = '$email'")->num_rows > 0) {
        $msg = "❌ Email already registered.";
    } elseif ($conn->query("SELECT * FROM student_profiles WHERE roll_number = '$roll_number'")->num_rows > 0) {
        $msg = "❌ Roll number already registered.";
    } elseif ($conn->query("SELECT * FROM student_profiles WHERE phone = '$phone'")->num_rows > 0) {
        $msg = "❌ Phone number already registered.";
    } else {
        $insert_user = "INSERT INTO users (email, password, role) VALUES ('$email', '$password', 'student')";
        if ($conn->query($insert_user)) {
            $user_id = $conn->insert_id;

            $insert_profile = "INSERT INTO student_profiles (user_id, name, roll_number, department, phone)
                               VALUES ($user_id, '$name', '$roll_number', '$department', '$phone')";

            if ($conn->query($insert_profile)) {
                $msg = "✅ Registration successful! You can now login.";
            } else {
                $msg = "❌ Error saving profile: " . $conn->error;
            }
        } else {
            $msg = "❌ Failed to register: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MDC Club – Registration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body, html {
            height: 100%;
            font-family: 'Segoe UI', sans-serif;
            overflow: hidden;
        }

        video.bg-video {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            z-index: -1;
        }

        .container {
            background-color: rgba(0, 0, 0, 0.65);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            backdrop-filter: blur(10px);
            margin: auto;
            position: relative;
            top: 50%;
            transform: translateY(-50%);
            color: #fff;
        }

        .logo {
            display: block;
            margin: 0 auto 20px;
            width: 100px;
            height: auto;
        }

        .container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #fdd835;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0 5px;
            border: none;
            border-radius: 8px;
            background: #ffffffee;
            font-size: 16px;
            color: #141e30;
        }

        input:focus {
            outline: none;
            box-shadow: 0 0 8px #fdd835aa;
        }

        input.error {
            border: 2px solid red;
        }

        .validation-msg {
            font-size: 13px;
            color: red;
            margin-bottom: 10px;
        }

        button {
            width: 100%;
            padding: 14px;
            border: none;
            background: rgb(23, 70, 173);
            color: rgb(223, 229, 240);
            font-size: 16px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }

        button:hover {
            background: rgb(210, 230, 34);
            color: #000;
        }

        .note {
            text-align: center;
            color: #ccc;
            font-size: 14px;
            margin-top: 15px;
        }

        .note a {
            color: rgb(30, 36, 224);
            text-decoration: none;
        }

        .note a:hover {
            text-decoration: underline;
        }

        .status {
            text-align: center;
            font-weight: bold;
            margin-top: 15px;
            color: lightgreen;
        }

        .status.error {
            color: #ff6b6b;
        }
    </style>
</head>
<body>

<!-- 🎬 Background Video -->
<video class="bg-video" autoplay muted loop playsinline>
    <source src="videoplayback.mp4" type="video/mp4">
</video>

<!-- 📝 Registration Form -->
<div class="container">
    <img src="logggo.png" alt="MDC Logo" class="logo">
    <h2>Student Registration</h2>

    <?php if (!empty($msg)): ?>
        <div class="status <?= str_contains($msg, '❌') ? 'error' : '' ?>">
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm();">
        <input type="text" name="name" placeholder="Full Name" required pattern="[A-Za-z\s]+" title="Name must contain only letters and spaces">
        <input type="text" name="roll_number" placeholder="Roll Number" required>
        <input type="text" name="department" placeholder="Department" required>
        <input type="text" name="phone" id="phone" placeholder="Phone Number" required pattern="\d{10}" title="Phone number must be 10 digits">
        <input type="email" name="email" placeholder="Email Address" required>

        <input type="password" id="password" name="password" placeholder="Create Password" required>
        <div id="passwordError" class="validation-msg"></div>

        <input type="password" id="confirm_password" placeholder="Confirm Password" required>
        <div id="confirmError" class="validation-msg"></div>

        <button type="submit">Register</button>
    </form>

    <div class="note">
        Already have an account? <a href="login.php">Login here</a>
    </div>
</div>

<script>
function validateForm() {
    const pwd = document.getElementById("password");
    const confirmPwd = document.getElementById("confirm_password");
    const phone = document.getElementById("phone");

    const pwdErr = document.getElementById("passwordError");
    const confirmErr = document.getElementById("confirmError");

    pwdErr.textContent = "";
    confirmErr.textContent = "";
    pwd.classList.remove("error");
    confirmPwd.classList.remove("error");
    phone.classList.remove("error");

    const strong = /^(?=.*[A-Z])(?=.*\d).{8,}$/;
    if (!strong.test(pwd.value)) {
        pwdErr.textContent = "Password must be at least 8 characters, include a number and an uppercase letter.";
        pwd.classList.add("error");
        return false;
    }

    if (pwd.value !== confirmPwd.value) {
        confirmErr.textContent = "Passwords do not match.";
        confirmPwd.classList.add("error");
        return false;
    }

    const phonePattern = /^\d{10}$/;
    if (!phonePattern.test(phone.value)) {
        phone.classList.add("error");
        alert("📱 Phone number must be exactly 10 digits.");
        return false;
    }

    return true;
}
</script>

</body>
</html>
