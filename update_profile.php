<?php
session_start();
include "config.php";

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $roll_number = trim($_POST['roll_number']);
    $department = trim($_POST['department']);
    
    // Validate inputs
    if (empty($name) || empty($phone) || empty($roll_number) || empty($department)) {
        $error_message = "Please fill in all required fields (Name, Phone, Roll Number, Department).";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error_message = "Please enter a valid 10-digit phone number.";
    } else {
        // Check if roll number is already used by another student
        $roll_check = $conn->prepare("SELECT user_id FROM student_profiles WHERE roll_number = ? AND user_id != ?");
        $roll_check->bind_param("si", $roll_number, $user_id);
        $roll_check->execute();
        $roll_result = $roll_check->get_result();
        
        if ($roll_result->num_rows > 0) {
            $error_message = "This roll number is already registered with another student.";
            $roll_check->close();
        } else {
            $roll_check->close();
            $conn->begin_transaction();
            
            try {
                // Check if profile exists
                $check_profile = $conn->prepare("SELECT user_id FROM student_profiles WHERE user_id = ?");
                $check_profile->bind_param("i", $user_id);
                $check_profile->execute();
                $profile_exists = $check_profile->get_result()->num_rows > 0;
                $check_profile->close();
                
                if ($profile_exists) {
                    // Update existing profile
                    $update_profile = $conn->prepare("
                        UPDATE student_profiles SET 
                        name = ?, 
                        roll_number = ?, 
                        department = ?, 
                        phone = ?
                        WHERE user_id = ?
                    ");
                    $update_profile->bind_param("ssssi", $name, $roll_number, $department, $phone, $user_id);
                    $update_profile->execute();
                    $update_profile->close();
                } else {
                    // Insert new profile
                    $insert_profile = $conn->prepare("
                        INSERT INTO student_profiles 
                        (user_id, name, roll_number, department, phone) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $insert_profile->bind_param("issss", $user_id, $name, $roll_number, $department, $phone);
                    $insert_profile->execute();
                    $insert_profile->close();
                }
                
                $conn->commit();
                $success_message = "Profile updated successfully!";
                
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Error updating profile: " . $e->getMessage();
            }
        }
    }
}

// Fetch current user data
$query = "SELECT sp.name, sp.roll_number, sp.department, sp.phone
          FROM student_profiles sp
          WHERE sp.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// If no profile exists, create empty array
if (!$user_data) {
    $user_data = [
        'name' => '',
        'roll_number' => '',
        'department' => '',
        'phone' => ''
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - MDC Club</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #dc2626; --secondary: #ef4444; --success: #10b981; --warning: #f59e0b; --danger: #ef4444;
            --glass: rgba(255, 255, 255, 0.1); --glass-strong: rgba(255, 255, 255, 0.95); --border: rgba(255, 255, 255, 0.2);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1); --shadow-strong: 0 20px 40px rgba(0, 0, 0, 0.15); --border-radius: 20px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #fca5a5 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh; overflow-x: hidden;
        }

        @keyframes gradientShift { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }

        body::before {
            content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: 
                radial-gradient(circle at 20% 80%, rgba(220, 38, 38, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(239, 68, 68, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(252, 165, 165, 0.2) 0%, transparent 50%);
        }

        .back-btn {
            position: fixed; top: 30px; left: 30px; width: 60px; height: 60px; z-index: 1001; cursor: pointer;
            background: var(--glass); backdrop-filter: blur(20px); border: 1px solid var(--border); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;
            transition: all 0.3s ease; box-shadow: var(--shadow); text-decoration: none;
        }
        .back-btn:hover { transform: scale(1.1); background: rgba(255, 255, 255, 0.2); color: white; }

        header {
            background: var(--glass); backdrop-filter: blur(20px); border-bottom: 1px solid var(--border); color: white; padding: 25px 40px;
            display: flex; justify-content: center; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: var(--shadow);
        }
        header h1 { font-size: 28px; font-weight: 700; text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3); }

        .container { padding: 50px 40px; max-width: 800px; margin: 0 auto; }

        .profile-card {
            background: var(--glass-strong); backdrop-filter: blur(20px); border: 1px solid var(--border);
            border-radius: var(--border-radius); box-shadow: var(--shadow-strong); overflow: hidden;
            animation: slideInUp 0.8s ease-out;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 30px;
            text-align: center; border-bottom: 1px solid var(--border);
        }
        .card-header h2 { font-size: 24px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 15px; }

        .card-body { padding: 40px; }

        .alert {
            padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;
            animation: slideInDown 0.5s ease-out; font-weight: 500;
        }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: var(--success); border-left: 4px solid var(--success); }
        .alert-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); border-left: 4px solid var(--danger); }

        .info-section {
            background: rgba(220, 38, 38, 0.05); padding: 25px; border-radius: 15px; margin-bottom: 30px;
            border-left: 4px solid var(--primary);
        }
        .info-title { font-weight: 700; color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 18px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: 1 / -1; }

        .form-label {
            display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .form-label.required::after { content: " *"; color: var(--danger); }

        .form-control {
            width: 100%; padding: 15px 18px; border: 2px solid #e9ecef; border-radius: 12px; font-size: 16px;
            font-family: inherit; transition: all 0.3s ease; background: white;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1); transform: translateY(-2px); }
        .form-control:hover { border-color: rgba(220, 38, 38, 0.5); }

        select.form-control { cursor: pointer; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 15px 30px;
            border: none; border-radius: 12px; font-weight: 600; font-size: 16px; cursor: pointer;
            text-decoration: none; transition: all 0.3s ease; min-width: 150px;
        }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { transform: translateY(-3px); box-shadow: 0 15px 35px rgba(0,0,0,0.2); }

        .btn-group { display: flex; gap: 20px; justify-content: center; margin-top: 30px; }

        .loading { display: none; text-align: center; padding: 30px; color: #666; }
        .spinner { width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px; }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes slideInDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 768px) {
            .container { padding: 30px 20px; }
            .back-btn { top: 20px; left: 20px; width: 50px; height: 50px; font-size: 18px; }
            header { padding: 20px; }
            header h1 { font-size: 24px; }
            .form-row { grid-template-columns: 1fr; }
            .btn-group { flex-direction: column; align-items: center; }
            .btn { width: 100%; max-width: 300px; }
            .card-body { padding: 25px; }
        }
    </style>
</head>
<body>
    <a href="dashboard_student.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
    </a>

    <header>
        <h1><i class="fas fa-user-edit"></i> Update Profile</h1>
    </header>

    <div class="container">
        <div class="profile-card">
            <div class="card-header">
                <h2><i class="fas fa-id-card"></i> Profile Information</h2>
            </div>
            
            <div class="card-body">
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?= htmlspecialchars($success_message) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?= htmlspecialchars($error_message) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="profileForm">
                    <div class="info-section">
                        <div class="info-title">
                            <i class="fas fa-user"></i>
                            Personal Information
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="name">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($user_data['name'] ?? '') ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required" for="roll_number">Roll Number</label>
                                <input type="text" class="form-control" id="roll_number" name="roll_number" 
                                       value="<?= htmlspecialchars($user_data['roll_number'] ?? '') ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label required" for="phone">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" 
                                       pattern="[0-9]{10}" maxlength="10" placeholder="1234567890" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label required" for="department">Department</label>
                            <select class="form-control" id="department" name="department" required>
                                <option value="">Select Department</option>
                                <option value="BCA" <?= ($user_data['department'] ?? '') == 'BCA' ? 'selected' : '' ?>>BCA - Bachelor of Computer Applications</option>
                                <option value="MCA" <?= ($user_data['department'] ?? '') == 'MCA' ? 'selected' : '' ?>>MCA - Master of Computer Applications</option>
                                <option value="Computer Science" <?= ($user_data['department'] ?? '') == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                <option value="Information Technology" <?= ($user_data['department'] ?? '') == 'Information Technology' ? 'selected' : '' ?>>Information Technology</option>
                                <option value="Electronics" <?= ($user_data['department'] ?? '') == 'Electronics' ? 'selected' : '' ?>>Electronics</option>
                                <option value="Mechanical" <?= ($user_data['department'] ?? '') == 'Mechanical' ? 'selected' : '' ?>>Mechanical</option>
                                <option value="Civil" <?= ($user_data['department'] ?? '') == 'Civil' ? 'selected' : '' ?>>Civil</option>
                                <option value="Electrical" <?= ($user_data['department'] ?? '') == 'Electrical' ? 'selected' : '' ?>>Electrical</option>
                                <option value="Other" <?= ($user_data['department'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">
                            <i class="fas fa-undo"></i>
                            Reset Changes
                        </button>
                    </div>
                </form>

                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <p>Updating profile...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function resetForm() {
            if (confirm('Are you sure you want to reset all changes?')) {
                document.getElementById('profileForm').reset();
            }
        }

        // Form submission with loading state
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const loading = document.getElementById('loading');
            
            submitBtn.style.display = 'none';
            loading.style.display = 'block';
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Phone number validation
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').substring(0, 10);
        });

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            const rollNumber = document.getElementById('roll_number').value;
            
            if (phone && !/^[0-9]{10}$/.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid 10-digit phone number.');
                return;
            }

            if (!rollNumber.trim()) {
                e.preventDefault();
                alert('Please enter your roll number.');
                return;
            }
        });
    </script>
</body>
</html>