<?php
session_start();
include "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['item_id']) || !isset($_POST['quantity'])) {
    header("Location: student_buy_merchandise.php");
    exit();
}

$item_id = intval($_POST['item_id']);
$quantity = intval($_POST['quantity']);

// Fetch item details
$item = $conn->query("SELECT * FROM merchandise WHERE item_id = $item_id")->fetch_assoc();
if (!$item || $item['stock'] < $quantity) {
    die("Invalid item or insufficient stock!");
}

$total_price = $item['price'] * $quantity;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment Gateway</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            display: flex; 
            justify-content: center; 
            align-items: center;
            padding: 20px;
        }
        
        .payment-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #4a00e0, #8e2de2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .payment-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .payment-header .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        .payment-body {
            padding: 30px;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid #4a00e0;
        }
        
        .order-summary h3 {
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .summary-row.total {
            border-top: 2px solid #ddd;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 18px;
            color: #4a00e0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4a00e0;
            box-shadow: 0 0 0 3px rgba(74, 0, 224, 0.1);
        }
        
        .payment-method-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-option.active {
            border-color: #4a00e0;
            background: #f8f6ff;
        }
        
        .payment-option:hover {
            border-color: #4a00e0;
        }
        
        .payment-option i {
            font-size: 24px;
            margin-bottom: 8px;
            color: #4a00e0;
        }
        
        .logos {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .logos img {
            height: 35px;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }
        
        .logos img:hover {
            opacity: 1;
        }
        
        .card-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .qr-section img {
            max-width: 180px;
            border: 3px solid #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .pay-button {
            width: 100%;
            background: linear-gradient(135deg, #4a00e0, #8e2de2);
            color: white;
            padding: 15px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .pay-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(74, 0, 224, 0.3);
        }
        
        .pay-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .hidden {
            display: none;
        }
        
        .security-features {
            background: #e8f5e8;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid #28a745;
        }
        
        .security-features h4 {
            color: #28a745;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .security-features ul {
            list-style: none;
            padding-left: 0;
        }
        
        .security-features li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4a00e0;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .payment-method-selector {
                grid-template-columns: 1fr;
            }
            
            .card-row {
                grid-template-columns: 1fr;
            }
            
            .payment-container {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h2><i class="fas fa-shield-alt"></i> Secure Payment</h2>
            <div class="security-badge">
                <i class="fas fa-lock"></i>
                SSL Encrypted
            </div>
        </div>
        
        <div class="payment-body">
            <div class="order-summary">
                <h3><i class="fas fa-shopping-cart"></i> Order Summary</h3>
                <div class="summary-row">
                    <span><strong>Item:</strong></span>
                    <span><?= htmlspecialchars($item['name']) ?></span>
                </div>
                <div class="summary-row">
                    <span><strong>Quantity:</strong></span>
                    <span><?= $quantity ?></span>
                </div>
                <div class="summary-row total">
                    <span><strong>Total Amount:</strong></span>
                    <span>₹<?= number_format($total_price, 2) ?></span>
                </div>
            </div>
            
            <form id="paymentForm" action="dummy_payment_process.php" method="POST">
                <input type="hidden" name="item_id" value="<?= $item_id ?>">
                <input type="hidden" name="quantity" value="<?= $quantity ?>">
                <input type="hidden" name="total_price" value="<?= $total_price ?>">
                
                <div class="form-group">
                    <label>Choose Payment Method</label>
                    <div class="payment-method-selector">
                        <div class="payment-option active" onclick="selectPaymentMethod('card')">
                            <i class="fas fa-credit-card"></i>
                            <div>Debit/Credit Card</div>
                        </div>
                        <div class="payment-option" onclick="selectPaymentMethod('upi')">
                            <i class="fab fa-google-pay"></i>
                            <div>UPI Payment</div>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="payment_method" value="card">
                </div>
                
                <!-- Card Payment Section -->
                <div id="card-section">
                    <div class="logos">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="MasterCard">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg" alt="American Express">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Cardholder Name</label>
                        <input type="text" name="card_name" placeholder="Enter full name as on card" maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-credit-card"></i> Card Number</label>
                        <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19" oninput="formatCardNumber()">
                    </div>
                    
                    <div class="card-row">
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Expiry Date</label>
                            <input type="text" name="expiry" id="expiry" placeholder="MM/YY" maxlength="5" oninput="formatExpiry()">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> CVV</label>
                            <input type="password" name="cvv" placeholder="123" maxlength="4" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                        </div>
                    </div>
                </div>
                
                <!-- UPI Payment Section -->
                <div id="upi-section" class="hidden">
                    <div class="logos">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/42/Paytm_logo.png" alt="Paytm">
                        <img src="gpay.jpeg" alt="Google Pay" onerror="this.style.display='none'">
                        <img src="pay.jpeg" alt="PhonePe" onerror="this.style.display='none'">
                        <img src="upi.png" alt="BHIM UPI" onerror="this.style.display='none'">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-mobile-alt"></i> UPI ID</label>
                        <input type="text" name="upi_id" placeholder="yourname@upi" pattern="[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+">
                    </div>
                    
                    <div class="qr-section">
                        <h4><i class="fas fa-qrcode"></i> Scan QR Code to Pay</h4>
                        <p style="margin: 10px 0; color: #666;">Amount: ₹<?= number_format($total_price, 2) ?></p>
                        <img src="code.png" alt="QR Code for Payment" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNmOGY5ZmEiLz48dGV4dCB4PSI1MCIgeT0iNTUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZmlsbD0iIzY2NiI+UVIgQ29kZTwvdGV4dD48L3N2Zz4='">
                    </div>
                </div>
                
                <button type="submit" class="pay-button" id="payButton">
                    <a href ="dummy_payment_process.php"style="text-decoration: none; color: inherit;">
                    <i class="fas fa-credit-card"></i>
                    Pay ₹<?= number_format($total_price, 2) ?></a>
                </button>
            </form>
            
            <div class="security-features">
                <h4><i class="fas fa-shield-alt"></i> Your Payment is Secure</h4>
                <ul>
                    <li><i class="fas fa-check text-success"></i> 256-bit SSL encryption</li>
                    <li><i class="fas fa-check text-success"></i> PCI DSS compliant</li>
                    <li><i class="fas fa-check text-success"></i> No card details stored</li>
                    <li><i class="fas fa-check text-success"></i> Secure transaction processing</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <h3>Processing Payment...</h3>
            <p>Please do not refresh or close this page</p>
        </div>
    </div>
    
    <script>
        function selectPaymentMethod(method) {
            // Update visual selection
            document.querySelectorAll('.payment-option').forEach(option => {
                option.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Update hidden input
            document.getElementById('payment_method').value = method;
            
            // Show/hide sections
            document.getElementById('card-section').style.display = method === 'card' ? 'block' : 'none';
            document.getElementById('upi-section').style.display = method === 'upi' ? 'block' : 'none';
            
            // Update button icon
            const button = document.getElementById('payButton');
            const icon = method === 'card' ? 'fas fa-credit-card' : 'fab fa-google-pay';
            button.innerHTML = `<i class="${icon}"></i> Pay ₹<?= number_format($total_price, 2) ?>`;
        }
        
        function formatCardNumber() {
            const input = document.getElementById('card_number');
            let value = input.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            const formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            input.value = formattedValue;
        }
        
        function formatExpiry() {
            const input = document.getElementById('expiry');
            let value = input.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            input.value = value;
        }
        
        function validateForm() {
            const method = document.getElementById('payment_method').value;
            
            if (method === 'card') {
                const cardName = document.querySelector('input[name="card_name"]').value.trim();
                const cardNumber = document.querySelector('input[name="card_number"]').value.replace(/\s/g, '');
                const expiry = document.querySelector('input[name="expiry"]').value;
                const cvv = document.querySelector('input[name="cvv"]').value;
                
                if (!cardName || cardName.length < 2) {
                    alert('Please enter a valid cardholder name');
                    return false;
                }
                
                if (!cardNumber || cardNumber.length < 13 || cardNumber.length > 19) {
                    alert('Please enter a valid card number');
                    return false;
                }
                
                if (!expiry || !/^\d{2}\/\d{2}$/.test(expiry)) {
                    alert('Please enter expiry date in MM/YY format');
                    return false;
                }
                
                if (!cvv || cvv.length < 3) {
                    alert('Please enter a valid CVV');
                    return false;
                }
            } else if (method === 'upi') {
                const upiId = document.querySelector('input[name="upi_id"]').value.trim();
                
                if (!upiId || !upiId.includes('@')) {
                    alert('Please enter a valid UPI ID');
                    return false;
                }
            }
            
            return true;
        }
        
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return;
            }
            
            // Show loading overlay
            document.getElementById('loadingOverlay').style.display = 'flex';
            document.getElementById('payButton').disabled = true;
        });
        
        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            const firstInput = document.querySelector('input[name="card_name"]');
            if (firstInput) {
                firstInput.focus();
            }
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>