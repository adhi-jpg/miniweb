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
<html>
<head>
    <title>Dummy Payment Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .payment-box { background: #fff; padding: 30px; border-radius: 10px; width: 500px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border-radius: 5px; border: 1px solid #ccc; }
        .btn { width: 100%; background: #4a00e0; color: white; padding: 12px; margin-top: 20px; font-size: 16px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #3700b3; }
        .summary { margin-top: 15px; padding: 10px; background: #f9f9f9; border-radius: 5px; }
        .hidden { display: none; }
        .logos { margin: 10px 0; display: flex; gap: 10px; }
        .logos img { height: 30px; }
        .qr-box { text-align: center; margin-top: 15px; }
        .qr-box img { height: 150px; }
    </style>
    <script>
        function togglePaymentFields() {
            const method = document.querySelector('select[name="payment_method"]').value;
            document.getElementById('card-section').style.display = method === 'card' ? 'block' : 'none';
            document.getElementById('upi-section').style.display = method === 'upi' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="payment-box">
        <h2>Secure Payment</h2>
        <div class="summary">
            <p><b>Item:</b> <?= htmlspecialchars($item['name']) ?></p>
            <p><b>Quantity:</b> <?= $quantity ?></p>
            <p><b>Total:</b> ₹<?= number_format($total_price, 2) ?></p>
        </div>
        <form action="dummy_payment_process.php" method="POST">
            <input type="hidden" name="item_id" value="<?= $item_id ?>">
            <input type="hidden" name="quantity" value="<?= $quantity ?>">
            <input type="hidden" name="total_price" value="<?= $total_price ?>">
            
            <label>Payment Method</label>
            <select name="payment_method" onchange="togglePaymentFields()" required>
                <option value="card">Debit/Credit Card</option>
                <option value="upi">UPI</option>
            </select>

            <!-- Card Fields -->
            <div id="card-section">
                <div class="logos">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="MasterCard">
                </div>
                <label>Cardholder Name</label>
                <input type="text" name="card_name">
                <label>Card Number</label>
                <input type="text" name="card_number" pattern="\d{16}" maxlength="16" placeholder="16-digit card number">
                <label>Expiry Date (MM/YY)</label>
                <input type="text" name="expiry" pattern="\d{2}/\d{2}" placeholder="MM/YY">
                <label>CVV</label>
                <input type="password" name="cvv" pattern="\d{3}" maxlength="3">
            </div>

            <!-- UPI Fields -->
            <div id="upi-section" class="hidden">
                <div class="logos">
                    <img src="gpay.jpeg" alt="GPay">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/f1/PhonePe_Logo.png" alt="PhonePe">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/42/Paytm_logo.png" alt="Paytm">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/4e/BHIM_UPI_Logo.svg" alt="BHIM">
                </div>
                <label>UPI ID</label>
                <input type="text" name="upi_id" placeholder="example@upi">
                <div class="qr-box">
                    <p>Scan QR (Dummy)</p>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/8d/QR_Code_example.png" alt="QR Code">
                </div>
            </div>

            <button type="submit" class="btn">Pay Now</button>
        </form>
    </div>
    <script>
        togglePaymentFields();
    </script>
</body>
</html>
