<?php
session_start();
include "config.php";

// 🔐 Only allow access for admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$msg = "";

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate and sanitize input data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $date = $_POST['date'];
    $venue = trim($_POST['venue']);
    $max_participants = (int)$_POST['max_participants'];

    // Server-side validation
    if (empty($title) || empty($description) || empty($date) || empty($venue) || $max_participants < 1) {
        $msg = "❌ All fields are required and must be valid!";
    } else {
        // Check if date is not in the past
        if (strtotime($date) < strtotime(date('Y-m-d'))) {
            $msg = "❌ Event date cannot be in the past!";
        } else {
            // Escape strings to prevent SQL injection
            $title = mysqli_real_escape_string($conn, $title);
            $description = mysqli_real_escape_string($conn, $description);
            $venue = mysqli_real_escape_string($conn, $venue);

            // Insert into database
            $sql = "INSERT INTO events (title, description, date, venue, max_participants) 
                    VALUES ('$title', '$description', '$date', '$venue', $max_participants)";

            if (mysqli_query($conn, $sql)) {
                $msg = "✅ Event created successfully!";
                // Clear form data after successful submission
                $_POST = array();
            } else {
                $msg = "❌ Failed to create event: " . mysqli_error($conn);
            }
        }
    }
}

// Get form values for repopulation (in case of errors)
$form_title = isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '';
$form_description = isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '';
$form_date = isset($_POST['date']) ? $_POST['date'] : '';
$form_venue = isset($_POST['venue']) ? htmlspecialchars($_POST['venue']) : '';
$form_max_participants = isset($_POST['max_participants']) ? $_POST['max_participants'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Event – MDC Admin</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      position: relative;
      overflow-x: hidden;
    }

    /* Animated background elements */
    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: 
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
      animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(180deg); }
    }

    .container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 650px;
    }

    .card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      padding: 40px;
      border-radius: 20px;
      box-shadow: 
        0 20px 40px rgba(0, 0, 0, 0.15),
        0 8px 16px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
      transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
      position: relative;
      overflow: hidden;
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
      transition: left 0.5s;
    }

    .card:hover::before {
      left: 100%;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 
        0 25px 50px rgba(0, 0, 0, 0.2),
        0 10px 20px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.9);
    }

    .header {
      text-align: center;
      margin-bottom: 35px;
      position: relative;
    }

    .header::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 50px;
      height: 3px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      border-radius: 2px;
    }

    h2 {
      color: #2d3748;
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 5px;
      background: linear-gradient(135deg, #667eea, #764ba2);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .subtitle {
      color: #718096;
      font-size: 0.95rem;
      margin-top: 8px;
    }

    .form-group {
      margin-bottom: 25px;
      position: relative;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #4a5568;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    input, textarea {
      width: 100%;
      padding: 15px 20px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 16px;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      background: rgba(255, 255, 255, 0.8);
      position: relative;
    }

    input:focus, textarea:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 
        0 0 0 3px rgba(102, 126, 234, 0.1),
        0 4px 12px rgba(102, 126, 234, 0.15);
      transform: translateY(-2px);
      background: rgba(255, 255, 255, 0.95);
    }

    textarea {
      resize: vertical;
      min-height: 120px;
      font-family: inherit;
    }

    .btn {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      font-size: 16px;
      font-weight: 600;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      position: relative;
      overflow: hidden;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
      transition: left 0.5s;
    }

    .btn:hover::before {
      left: 100%;
    }

    .btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .btn:active {
      transform: translateY(-1px);
    }

    .msg {
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 25px;
      font-weight: 500;
      text-align: center;
      border: 1px solid;
      animation: slideIn 0.5s ease-out;
    }

    .msg.success {
      background: linear-gradient(135deg, #48bb78, #38a169);
      color: white;
      border-color: #38a169;
    }

    .msg.error {
      background: linear-gradient(135deg, #f56565, #e53e3e);
      color: white;
      border-color: #e53e3e;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .char-count {
      position: absolute;
      bottom: 10px;
      right: 15px;
      font-size: 0.8rem;
      color: #a0aec0;
      pointer-events: none;
    }

    /* Responsive design */
    @media (max-width: 768px) {
      .card {
        padding: 30px 25px;
      }
      
      .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      h2 {
        font-size: 1.75rem;
      }
    }

    /* Form validation styles */
    .form-group.error input,
    .form-group.error textarea {
      border-color: #f56565;
      box-shadow: 0 0 0 3px rgba(245, 101, 101, 0.1);
    }

    .error-message {
      color: #f56565;
      font-size: 0.8rem;
      margin-top: 5px;
      display: none;
    }

    .form-group.error .error-message {
      display: block;
    }

    .back-link {
      position: absolute;
      top: 20px;
      left: 20px;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .back-link:hover {
      color: white;
    }
  </style>
</head>
<body>
  <a href="dashboard_admin.php" class="back-link">← Back to Dashboard</a>
  
  <div class="container">
    <div class="card">
      <div class="header">
        <h2>Create New Club Event</h2>
        <div class="subtitle">Fill in the details to create an amazing event</div>
      </div>

      <?php if (!empty($msg)): ?>
        <div class="msg <?= str_contains($msg, '❌') ? 'error' : 'success' ?>">
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="eventForm">
        <div class="form-group">
          <label for="title">Event Title</label>
          <input type="text" name="title" id="title" required maxlength="100" value="<?= $form_title ?>">
          <div class="error-message">Please enter a valid event title</div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="date">Event Date</label>
            <input type="date" name="date" id="date" required value="<?= $form_date ?>">
            <div class="error-message">Please select a valid date</div>
          </div>

          <div class="form-group">
            <label for="max_participants">Max Participants</label>
            <input type="number" name="max_participants" id="max_participants" required min="1" max="1000" value="<?= $form_max_participants ?>">
            <div class="error-message">Please enter a valid number (1-1000)</div>
          </div>
        </div>

        <div class="form-group">
          <label for="venue">Venue</label>
          <input type="text" name="venue" id="venue" required maxlength="200" value="<?= $form_venue ?>">
          <div class="error-message">Please enter a valid venue</div>
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea name="description" id="description" rows="4" required maxlength="1000"><?= $form_description ?></textarea>
          <div class="char-count" id="charCount">0/1000</div>
          <div class="error-message">Please enter a valid description</div>
        </div>

        <button type="submit" class="btn" id="submitBtn">
          Create Event
        </button>
      </form>
    </div>
  </div>

  <script>
    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('eventForm');
      const submitBtn = document.getElementById('submitBtn');
      const description = document.getElementById('description');
      const charCount = document.getElementById('charCount');
      const eventDate = document.getElementById('date');
      const maxParticipants = document.getElementById('max_participants');

      // Set minimum date to today
      const today = new Date().toISOString().split('T')[0];
      eventDate.min = today;

      // Character counter for description
      function updateCharCount() {
        const count = description.value.length;
        charCount.textContent = `${count}/1000`;
        charCount.style.color = count > 950 ? '#f56565' : '#a0aec0';
      }

      description.addEventListener('input', updateCharCount);
      
      // Initialize character count
      updateCharCount();

      // Real-time validation
      const inputs = form.querySelectorAll('input, textarea');
      inputs.forEach(input => {
        input.addEventListener('blur', validateField);
        input.addEventListener('input', clearError);
      });

      function validateField(e) {
        const field = e.target;
        const formGroup = field.closest('.form-group');
        
        if (!field.value.trim()) {
          formGroup.classList.add('error');
          return false;
        }
        
        // Additional validation
        if (field.type === 'date' && new Date(field.value) < new Date(today)) {
          formGroup.classList.add('error');
          return false;
        }
        
        if (field.type === 'number' && (field.value < 1 || field.value > 1000)) {
          formGroup.classList.add('error');
          return false;
        }
        
        formGroup.classList.remove('error');
        return true;
      }

      function clearError(e) {
        const formGroup = e.target.closest('.form-group');
        formGroup.classList.remove('error');
      }

      // Enhanced form submission
      form.addEventListener('submit', function(e) {
        // Client-side validation
        let isValid = true;
        
        inputs.forEach(input => {
          if (!validateField({target: input})) {
            isValid = false;
          }
        });

        if (!isValid) {
          e.preventDefault();
          return false;
        }

        // Show loading state
        submitBtn.style.opacity = '0.8';
        submitBtn.style.pointerEvents = 'none';
        submitBtn.textContent = 'Creating Event...';
        
        // Form will submit normally to PHP
        return true;
      });

      // Enhanced input animations
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.closest('.form-group').classList.add('focused');
        });

        input.addEventListener('blur', function() {
          this.closest('.form-group').classList.remove('focused');
        });
      });

      // Auto-hide success message after 5 seconds
      const successMsg = document.querySelector('.msg.success');
      if (successMsg) {
        setTimeout(() => {
          successMsg.style.opacity = '0';
          successMsg.style.transform = 'translateY(-20px)';
          setTimeout(() => {
            successMsg.style.display = 'none';
          }, 300);
        }, 5000);
      }
    });
  </script>
</body>
</html>