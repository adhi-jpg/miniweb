<?php
session_start();
include "config.php";

// ✅ Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// ✅ Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['submit_delivery'])) {
    $order_id = intval($_POST['order_id']);
    $delivery_notes = trim($_POST['delivery_notes']);
    
    if ($order_id <= 0) {
        $message = "Please select a valid order.";
        $message_type = "error";
    } else {
        // Check if order belongs to this student, is completed, and not yet confirmed
        $check_query = "SELECT o.order_id, o.status, m.name as item_name 
                       FROM orders o 
                       JOIN merchandise m ON o.item_id = m.item_id 
                       LEFT JOIN delivery_confirmations dc ON o.order_id = dc.order_id
                       WHERE o.order_id = ? AND o.user_id = ? AND o.status = 'completed' AND dc.order_id IS NULL";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $order_id, $user_id);
        $check_stmt->execute();
        $order_result = $check_stmt->get_result();
        
        if ($order_result->num_rows === 0) {
            $message = "Order not found, not eligible for delivery confirmation, or already confirmed.";
            $message_type = "error";
        } else {
            $order_data = $order_result->fetch_assoc();
            
            // Upload proof file(s)
            $uploaded_files = [];
            $upload_success = true;
            
            if (isset($_FILES['delivery_proof']) && !empty($_FILES['delivery_proof']['name'][0])) {
                $upload_dir = "uploads/delivery_proof/";
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

                $allowed_types = ['jpg','jpeg','png','pdf'];
                foreach ($_FILES['delivery_proof']['name'] as $i => $name) {
                    $tmp = $_FILES['delivery_proof']['tmp_name'][$i];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $new_file = $upload_dir . "delivery_" . $order_id . "_" . time() . "_$i.$ext";

                    if (in_array($ext, $allowed_types) && move_uploaded_file($tmp, $new_file)) {
                        $uploaded_files[] = $new_file;
                    } else {
                        $upload_success = false;
                        $message = "File upload failed for $name";
                        $message_type = "error";
                        break;
                    }
                }
            }

            if ($upload_success) {
                $files_json = !empty($uploaded_files) ? json_encode($uploaded_files) : null;
                $insert = $conn->prepare("INSERT INTO delivery_confirmations (order_id, user_id, delivery_notes, proof_files, submitted_at) VALUES (?, ?, ?, ?, NOW())");
                $insert->bind_param("iiss", $order_id, $user_id, $delivery_notes, $files_json);
                if ($insert->execute()) {
                    // After confirmation, update order status → delivered
                    $update = $conn->prepare("UPDATE orders SET status = 'delivered' WHERE order_id = ?");
                    $update->bind_param("i", $order_id);
                    $update->execute();

                    $message = "✅ Delivery confirmation submitted successfully.";
                    $message_type = "success";
                }
            }
        }
    }
}

// ✅ Fetch orders of this student that are COMPLETED but not yet confirmed
$orders_query = "SELECT o.order_id, o.quantity, o.total_price, m.name as item_name
                 FROM orders o 
                 JOIN merchandise m ON o.item_id = m.item_id
                 LEFT JOIN delivery_confirmations dc ON o.order_id = dc.order_id
                 WHERE o.user_id = ? AND o.status = 'completed' AND dc.order_id IS NULL";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $user_id);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Delivery Confirmation</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --red-primary: #e53935;
      --red-deep: #b71c1c;
      --red-light: #ffcdd2;
      --red-grad-1: #ff1744;
      --red-grad-2: #c2185b;
      --accent: #ffc107;
      --accent-dark: #ff8f00;
      --white: #fff;
      --light-bg: #f9f9f9;
      --dark: #1a1a1a;
      --gray: #666;
      --light-gray: #e0e0e0;
      --glass: rgba(255, 255, 255, 0.92);
      --shadow: 0 6px 30px rgba(229, 57, 53, 0.15);
      --shadow-hover: 0 10px 40px rgba(229, 57, 53, 0.25);
      --radius: 12px;
      --radius-lg: 16px;
      --spacing: 24px;
      --border: #ff8a80;
      --input-bg: #fff5f5;
      --input-focus: #ffcdd2;
      --text: #8b0000;
      --label: #880808;
      --success: #4caf50;
      --error: #f44336;
      --bg-grad: linear-gradient(135deg, var(--red-grad-2) 0%, var(--red-primary) 70%);
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    html, body {
      min-height: 100vh;
      background: var(--bg-grad);
      font-family: 'Inter', Arial, sans-serif;
      color: var(--text);
      line-height: 1.6;
    }
    
    body {
      padding: 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    
    h2 {
      color: var(--white);
      background: linear-gradient(135deg, var(--red-grad-1), var(--accent));
      padding: 18px 30px;
      border-radius: var(--radius-lg);
      text-align: center;
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      box-shadow: var(--shadow);
      width: 100%;
      max-width: 600px;
      margin: 0 auto 30px;
      position: relative;
      overflow: hidden;
    }
    
    h2::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--accent), var(--red-primary));
    }
    
    .container {
      width: 100%;
      max-width: 600px;
      background: var(--glass);
      padding: 35px;
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      border: 1px solid rgba(229, 57, 53, 0.2);
      backdrop-filter: blur(8px);
      position: relative;
    }
    
    .container::before {
      content: '';
      position: absolute;
      top: -2px;
      left: -2px;
      right: -2px;
      bottom: -2px;
      border-radius: var(--radius-lg);
      background: linear-gradient(45deg, var(--red-primary), var(--accent), var(--red-primary));
      z-index: -1;
      opacity: 0.4;
    }
    
    .message {
      padding: 16px 20px;
      margin-bottom: var(--spacing);
      border-radius: var(--radius);
      font-weight: 600;
      font-size: 1rem;
      text-align: center;
      border: 1px solid transparent;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      letter-spacing: 0.03em;
      position: relative;
      overflow: hidden;
    }
    
    .message::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 5px;
      height: 100%;
    }
    
    .message.success {
      color: var(--success);
      background-color: rgba(76, 175, 80, 0.1);
      border-color: rgba(76, 175, 80, 0.3);
    }
    
    .message.success::before {
      background-color: var(--success);
    }
    
    .message.error {
      color: var(--error);
      background-color: rgba(244, 67, 54, 0.1);
      border-color: rgba(244, 67, 54, 0.3);
    }
    
    .message.error::before {
      background-color: var(--error);
    }
    
    @keyframes fadeIn {
      0% { opacity: 0; transform: translateY(-10px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes shake {
      0% { transform: translateX(0); }
      20% { transform: translateX(-8px); }
      40% { transform: translateX(8px); }
      60% { transform: translateX(-5px); }
      80% { transform: translateX(5px); }
      100% { transform: translateX(0); }
    }
    
    form {
      display: flex;
      flex-direction: column;
    }
    
    .form-group {
      margin-bottom: var(--spacing);
    }
    
    label {
      font-weight: 600;
      color: var(--label);
      margin-bottom: 10px;
      display: block;
      font-size: 1rem;
    }
    
    select,
    input[type="file"],
    textarea {
      width: 100%;
      padding: 14px 18px;
      border: 2px solid var(--light-gray);
      border-radius: var(--radius);
      background: var(--input-bg);
      font-size: 1rem;
      font-family: inherit;
      color: var(--text);
      transition: all 0.3s ease;
    }
    
    select:focus,
    textarea:focus,
    input[type="file"]:focus {
      border-color: var(--red-primary);
      box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.2);
      outline: none;
      background: var(--white);
    }
    
    textarea {
      min-height: 100px;
      resize: vertical;
    }
    
    input[type="file"] {
      background: var(--white);
      padding: 12px;
      border: 2px dashed var(--border);
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    input[type="file"]:hover {
      border-color: var(--red-primary);
      background: var(--input-bg);
    }
    
    .file-preview {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
      margin-bottom: 15px;
    }
    
    .file-preview span {
      background: var(--accent);
      color: var(--red-primary);
      border-radius: 20px;
      padding: 6px 14px;
      font-size: 0.9rem;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      border: 1px solid var(--red-primary);
    }
    
    .file-preview span::before {
      content: '📄';
      margin-right: 6px;
    }
    
    button[type="submit"] {
      background: linear-gradient(135deg, var(--red-primary) 0%, var(--red-deep) 100%);
      color: var(--white);
      font-weight: 600;
      font-size: 1.1rem;
      padding: 16px 40px;
      border-radius: var(--radius);
      border: none;
      margin: 10px auto 0;
      box-shadow: var(--shadow);
      cursor: pointer;
      transition: all 0.3s ease;
      letter-spacing: 0.05em;
      outline: none;
      position: relative;
      overflow: hidden;
    }
    
    button[type="submit"]::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: all 0.5s ease;
    }
    
    button[type="submit"]:hover,
    button[type="submit"]:focus {
      transform: translateY(-3px);
      box-shadow: var(--shadow-hover);
    }
    
    button[type="submit"]:hover::before {
      left: 100%;
    }
    
    button[type="submit"]:active {
      transform: translateY(0);
    }
    
    .custom-file-label {
      display: block;
      margin-bottom: 8px;
      color: var(--red-deep);
      font-weight: 600;
    }
    
    select {
      background: var(--input-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23e53935' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E") no-repeat right 16px center;
      appearance: none;
      padding-right: 50px;
      cursor: pointer;
    }
    
    /* Success/Error icons */
    .message.success::after {
      content: '✓';
      margin-left: 8px;
    }
    
    .message.error::after {
      content: '✕';
      margin-left: 8px;
    }
    
    /* Responsive */
    @media (max-width: 660px) {
      body {
        padding: 15px;
      }
      
      h2 {
        font-size: 1.4rem;
        padding: 15px 20px;
        margin-bottom: 20px;
      }
      
      .container {
        padding: 25px 20px;
      }
    }
    
    /* Animation for form elements */
    .form-group {
      animation: fadeIn 0.5s ease forwards;
    }
    
    .form-group:nth-child(1) { animation-delay: 0.1s; }
    .form-group:nth-child(2) { animation-delay: 0.2s; }
    .form-group:nth-child(3) { animation-delay: 0.3s; }
    button[type="submit"] { animation: fadeIn 0.5s ease 0.4s forwards; }
    
    /* Focus states for accessibility */
    select:focus,
    textarea:focus,
    input[type="file"]:focus,
    button[type="submit"]:focus {
      outline: 2px solid var(--accent);
      outline-offset: 2px;
    }
    
    /* Placeholder styling */
    ::placeholder {
      color: #aaa;
      opacity: 1;
    }
    
    :-ms-input-placeholder {
      color: #aaa;
    }
    
    ::-ms-input-placeholder {
      color: #aaa;
    }
  </style>
</head>
<body>
  <h2>Confirm Delivery</h2>
  <div class="container">
    <?php if($message): ?>
      <div class="message <?= $message_type ?>"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
      <div class="form-group">
        <label for="order_id">Select Purchase Order:</label>
        <select name="order_id" id="order_id" required class="order-select">
          <option value="">-- Select Order --</option>
          <?php while($row = $orders_result->fetch_assoc()): ?>
            <option value="<?= $row['order_id'] ?>">
              Order #<?= $row['order_id'] ?> - <?= htmlspecialchars($row['item_name']) ?> (Qty: <?= $row['quantity'] ?>, ₹<?= $row['total_price'] ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="custom-file-label" for="delivery_proof">Upload Proof:</label>
        <input type="file" name="delivery_proof[]" id="delivery_proof" multiple required accept=".jpg,.jpeg,.png,.pdf">
        <div class="file-preview"></div>
      </div>

      <div class="form-group">
        <label for="delivery_notes">Notes:</label>
        <textarea name="delivery_notes" id="delivery_notes" placeholder="Add delivery notes if needed..."></textarea>
      </div>

      <button type="submit" name="submit_delivery">Submit Confirmation</button>
    </form>
  </div>

  <script>
    // File input preview functionality
    document.getElementById('delivery_proof').addEventListener('change', function(e) {
      const preview = document.querySelector('.file-preview');
      preview.innerHTML = '';
      
      if (this.files) {
        const files = Array.from(this.files);
        
        files.forEach(file => {
          const fileName = document.createElement('span');
          fileName.textContent = file.name;
          preview.appendChild(fileName);
        });
      }
    });
  </script>
</body>
</html>