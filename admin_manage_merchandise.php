<?php
session_start();
include "config.php";

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['add_item'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $image_path = null;

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/merchandise/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                }
            }
        }

        $insert = "INSERT INTO merchandise (name, price, stock, image_path) VALUES ('$name', $price, $stock, " . ($image_path ? "'$image_path'" : "NULL") . ")";
        $message = $conn->query($insert) ? "✅ New merchandise added successfully." : "❌ Error adding merchandise: " . $conn->error;
    }
    
    if (isset($_POST['update_item'])) {
        $item_id = intval($_POST['item_id']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $image_path = null;

        // Handle image upload for updates
        if (isset($_FILES['update_image']) && $_FILES['update_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/merchandise/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['update_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_name = uniqid() . '.' . $file_extension;
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['update_image']['tmp_name'], $target_file)) {
                    $image_path = $target_file;
                    
                    // Delete old image if exists
                    $old_image_query = $conn->query("SELECT image_path FROM merchandise WHERE item_id = $item_id");
                    if ($old_image_query && $old_image_query->num_rows > 0) {
                        $old_image = $old_image_query->fetch_assoc()['image_path'];
                        if ($old_image && file_exists($old_image)) {
                            unlink($old_image);
                        }
                    }
                }
            }
        }

        $update_fields = "price = $price, stock = $stock";
        if ($image_path) {
            $update_fields .= ", image_path = '$image_path'";
        }
        
        $update = "UPDATE merchandise SET $update_fields WHERE item_id = $item_id";
        $message = $conn->query($update) ? "✅ Merchandise updated successfully." : "❌ Error updating merchandise: " . $conn->error;
    }
}

$items = $conn->query("SELECT * FROM merchandise ORDER BY item_id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchandise Management – MDC Club</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; padding: 20px; color: #333;
        }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            text-align: center; margin-bottom: 40px; color: white;
            animation: slideDown 0.8s ease-out;
        }
        .header h1 { font-size: 2.5rem; margin-bottom: 10px; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        .header p { font-size: 1.1rem; opacity: 0.9; animation: fadeIn 1s ease-out 0.3s both; }
        
        .message {
            background: rgba(255, 255, 255, 0.95); color: #28a745; padding: 15px 20px;
            border-radius: 12px; margin-bottom: 30px; text-align: center; font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-left: 4px solid #28a745;
            animation: slideIn 0.5s ease-out;
        }
        .message.error { color: #dc3545; border-left-color: #dc3545; }
        
        .form-section, .table-container {
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);
            padding: 30px; border-radius: 20px; margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.2);
            animation: slideUp 0.6s ease-out;
        }
        
        .form-section h2, .table-header h2 {
            color: #4a00e0; margin-bottom: 25px; font-size: 1.5rem;
            display: flex; align-items: center; gap: 10px;
        }
        
        .form-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 25px;
        }
        
        .form-group label {
            display: block; margin-bottom: 8px; font-weight: 600;
            color: #555; font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9;
            border-radius: 10px; font-size: 1rem; transition: all 0.3s ease;
            background: rgba(255,255,255,0.8);
        }
        
        .form-group input:focus {
            outline: none; border-color: #4a00e0;
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
            transform: translateY(-1px);
        }

        .image-upload-area {
            border: 2px dashed #e1e5e9; border-radius: 10px; padding: 20px;
            text-align: center; transition: all 0.3s ease; cursor: pointer;
            background: rgba(255,255,255,0.5);
        }
        
        .image-upload-area:hover, .image-upload-area.dragover {
            border-color: #4a00e0; background: rgba(74, 0, 224, 0.05);
        }
        
        .image-upload-area i {
            font-size: 2rem; color: #4a00e0; margin-bottom: 10px; display: block;
        }
        
        .image-upload-area input[type="file"] {
            display: none;
        }
        
        .image-preview {
            max-width: 100%; max-height: 200px; border-radius: 8px; margin-top: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn {
            padding: 12px 30px; background: linear-gradient(135deg, #4a00e0, #8e2de2);
            color: white; border: none; border-radius: 10px; cursor: pointer;
            font-size: 1rem; font-weight: 600; transition: all 0.3s ease;
            display: inline-flex; align-items: center; gap: 8px;
            text-transform: uppercase; letter-spacing: 0.5px;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(74, 0, 224, 0.4); }
        .btn:active { transform: translateY(0); }
        .btn-small { padding: 8px 16px; font-size: 0.85rem; }
        
        .table-header {
            padding: 25px 30px; background: linear-gradient(135deg, #4a00e0, #8e2de2);
            color: white; margin: -30px -30px 30px -30px; border-radius: 20px 20px 0 0;
        }
        
        .table-wrapper { overflow-x: auto; }
        
        table { width: 100%; border-collapse: collapse; background: transparent; }
        
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid rgba(0,0,0,0.05); }
        
        th {
            background: rgba(74, 0, 224, 0.1); color: #4a00e0; font-weight: 600;
            text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px;
        }
        
        tbody tr { transition: all 0.3s ease; }
        tbody tr:hover { background: rgba(74, 0, 224, 0.05); transform: scale(1.01); }
        
        .table-input {
            padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px;
            font-size: 0.9rem; width: 80px; transition: all 0.3s ease;
        }
        .table-input:focus {
            outline: none; border-color: #4a00e0;
            box-shadow: 0 0 0 2px rgba(74, 0, 224, 0.1);
        }
        
        .item-id { font-weight: bold; color: #4a00e0; font-family: 'Courier New', monospace; }
        
        .stock-badge {
            display: inline-block; padding: 4px 8px; border-radius: 20px;
            font-size: 0.8rem; font-weight: 600;
        }
        .stock-high { background: #d4edda; color: #155724; }
        .stock-medium { background: #fff3cd; color: #856404; }
        .stock-low { background: #f8d7da; color: #721c24; }
        
        .price-display { font-weight: 600; color: #28a745; font-size: 1.1rem; }
        
        .item-image {
            width: 60px; height: 60px; object-fit: cover; border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1); cursor: pointer;
        }
        
        .no-image {
            width: 60px; height: 60px; background: #f8f9fa; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; color: #6c757d;
            border: 1px solid #dee2e6;
        }
        
        .table-image-upload {
            width: 60px; padding: 4px; border: 1px solid #ddd; border-radius: 6px;
            font-size: 0.8rem; cursor: pointer;
        }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #666; }
        .empty-state i { font-size: 4rem; color: #ddd; margin-bottom: 20px; }
        
        .loading {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 9999;
            justify-content: center; align-items: center;
        }
        
        .spinner {
            width: 50px; height: 50px; border: 4px solid #f3f3f3;
            border-top: 4px solid #4a00e0; border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95); padding: 20px; border-radius: 15px;
            text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            animation: slideUp 0.6s ease-out;
        }
        
        .stat-number {
            font-size: 2rem; font-weight: bold; color: #4a00e0; margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666; font-size: 0.9rem;
            text-transform: uppercase; letter-spacing: 0.5px;
        }

        .image-modal {
            display: none; position: fixed; z-index: 10000; left: 0; top: 0;
            width: 100%; height: 100%; background-color: rgba(0,0,0,0.9);
        }
        
        .modal-content {
            margin: auto; display: block; width: 80%; max-width: 700px;
            max-height: 80%; margin-top: 5%;
        }
        
        .close {
            position: absolute; top: 15px; right: 35px; color: #f1f1f1;
            font-size: 40px; font-weight: bold; cursor: pointer;
        }
        
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes slideIn { from { transform: translateX(-30px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .header h1 { font-size: 2rem; }
            .form-section, .table-container { margin: 0 10px 30px; }
            .form-grid { grid-template-columns: 1fr; }
            th, td { padding: 10px; font-size: 0.9rem; }
            .table-input { width: 60px; }
            .item-image, .no-image { width: 40px; height: 40px; }
        }
    </style>
</head>
<body>
    <div class="loading" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="image-modal">
        <span class="close">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> MDC Club Merchandise Portal</h1>
            <p>Manage your club merchandise inventory with ease</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?= strpos($message, '❌') !== false ? 'error' : '' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid" id="statsGrid"></div>

        <div class="form-section">
            <h2><i class="fas fa-plus-circle"></i> Add New Merchandise</h2>
            <form method="POST" enctype="multipart/form-data" id="addForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Item Name</label>
                        <input type="text" name="name" id="itemName" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-rupee-sign"></i> Price (₹)</label>
                        <input type="number" name="price" id="itemPrice" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-boxes"></i> Stock Quantity</label>
                        <input type="number" name="stock" id="itemStock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Product Image</label>
                        <div class="image-upload-area" onclick="document.getElementById('imageUpload').click()">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <div>Click to upload or drag image here</div>
                            <small>JPG, PNG, GIF, WEBP (Max 5MB)</small>
                            <input type="file" name="image" id="imageUpload" accept="image/*">
                            <img id="imagePreview" class="image-preview" style="display: none;">
                        </div>
                    </div>
                </div>
                <button type="submit" name="add_item" class="btn">
                    <i class="fas fa-plus"></i> Add Item
                </button>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-edit"></i> Update Existing Merchandise</h2>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-image"></i> Image</th>
                            <th><i class="fas fa-tag"></i> Name</th>
                            <th><i class="fas fa-rupee-sign"></i> Price</th>
                            <th><i class="fas fa-boxes"></i> Stock</th>
                            <th><i class="fas fa-cog"></i> Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($items->num_rows > 0): ?>
                            <?php while ($row = $items->fetch_assoc()): ?>
                                <tr>
                                    <form method="POST" enctype="multipart/form-data" class="update-form">
                                        <td>
                                            <span class="item-id">#<?= $row['item_id'] ?></span>
                                            <input type="hidden" name="item_id" value="<?= $row['item_id'] ?>">
                                        </td>
                                        <td>
                                            <?php if ($row['image_path'] && file_exists($row['image_path'])): ?>
                                                <img src="<?= $row['image_path'] ?>" class="item-image" onclick="showImage('<?= $row['image_path'] ?>')" alt="Product">
                                            <?php else: ?>
                                                <div class="no-image">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="update_image" class="table-image-upload" accept="image/*" title="Update Image">
                                        </td>
                                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                        <td>
                                            <span class="price-display">₹</span>
                                            <input type="number" name="price" class="table-input" step="0.01" value="<?= $row['price'] ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" name="stock" class="table-input" value="<?= $row['stock'] ?>" required>
                                            <span class="stock-badge <?= $row['stock'] > 20 ? 'stock-high' : ($row['stock'] > 5 ? 'stock-medium' : 'stock-low') ?>">
                                                <?= $row['stock'] > 20 ? 'High' : ($row['stock'] > 5 ? 'Medium' : 'Low') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="submit" name="update_item" class="btn btn-small">
                                                <i class="fas fa-save"></i> Update
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <i class="fas fa-box-open"></i>
                                    <h3>No merchandise items found</h3>
                                    <p>Start by adding your first item above!</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            updateStats();
            enhanceForm();
            addLoadingStates();
            autoHideMessages();
            setupImageUpload();
            setupImageModal();
        });

        function updateStats() {
            const rows = document.querySelectorAll('tbody tr');
            if (rows.length === 1 && rows[0].querySelector('.empty-state')) return;

            let totalItems = 0, totalValue = 0, lowStockItems = 0;
            rows.forEach(row => {
                const form = row.querySelector('form');
                if (form) {
                    const stock = parseInt(form.querySelector('input[name="stock"]').value) || 0;
                    const price = parseFloat(form.querySelector('input[name="price"]').value) || 0;
                    totalItems += stock;
                    totalValue += stock * price;
                    if (stock <= 5) lowStockItems++;
                }
            });

            document.getElementById('statsGrid').innerHTML = `
                <div class="stat-card"><div class="stat-number">${totalItems}</div><div class="stat-label">Total Items</div></div>
                <div class="stat-card"><div class="stat-number">₹${totalValue.toLocaleString('en-IN')}</div><div class="stat-label">Inventory Value</div></div>
                <div class="stat-card"><div class="stat-number">${lowStockItems}</div><div class="stat-label">Low Stock Items</div></div>
                <div class="stat-card"><div class="stat-number">${rows.length}</div><div class="stat-label">Product Types</div></div>
            `;
        }

        function enhanceForm() {
            const inputs = document.getElementById('addForm').querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('blur', () => validateInput(input));
                input.addEventListener('input', () => input.style.borderColor = '#e1e5e9');
            });
            if (inputs.length > 0) inputs[0].focus();
        }

        function validateInput(input) {
            const value = input.value.trim();
            if (input.hasAttribute('required') && !value) {
                input.style.borderColor = '#dc3545';
                return false;
            }
            if (input.type === 'number' && value) {
                const num = parseFloat(value);
                if (isNaN(num) || num < 0) {
                    input.style.borderColor = '#dc3545';
                    return false;
                }
            }
            input.style.borderColor = '#28a745';
            return true;
        }

        function setupImageUpload() {
            const uploadArea = document.querySelector('.image-upload-area');
            const fileInput = document.getElementById('imageUpload');
            const preview = document.getElementById('imagePreview');

            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                }
            });
        }

        function setupImageModal() {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            const span = document.getElementsByClassName('close')[0];

            window.showImage = function(src) {
                modal.style.display = 'block';
                modalImg.src = src;
            };

            span.onclick = function() {
                modal.style.display = 'none';
            };

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            };
        }

        function addLoadingStates() {
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    let isValid = true;
                    this.querySelectorAll('input[required]').forEach(input => {
                        if (!validateInput(input)) isValid = false;
                    });
                    if (isValid) document.getElementById('loadingOverlay').style.display = 'flex';
                });
            });
        }

        function autoHideMessages() {
            const message = document.querySelector('.message');
            if (message) {
                setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transform = 'translateX(-100%)';
                    setTimeout(() => message.remove(), 300);
                }, 5000);
            }
        }

        // Real-time updates
        document.querySelectorAll('input[name="stock"]').forEach(input => {
            input.addEventListener('input', function() {
                const badge = this.parentNode.querySelector('.stock-badge');
                const value = parseInt(this.value) || 0;
                badge.className = 'stock-badge ' + (value > 20 ? 'stock-high' : (value > 5 ? 'stock-medium' : 'stock-low'));
                badge.textContent = value > 20 ? 'High' : (value > 5 ? 'Medium' : 'Low');
                setTimeout(updateStats, 100);
            });
        });

        document.querySelectorAll('input[name="price"]').forEach(input => {
            input.addEventListener('input', () => setTimeout(updateStats, 100));
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = document.activeElement.closest('form');
                if (form) form.submit();
            }
            if (e.key === 'Escape') {
                document.getElementById('imageModal').style.display = 'none';
            }
        });

        // Button animations
        document.querySelectorAll('button[type="submit"]').forEach(button => {
            button.addEventListener('click', function() {
                this.style.transform = 'scale(0.95)';
                setTimeout(() => this.style.transform = '', 150);
            });
        });
    </script>
</body>
</html>