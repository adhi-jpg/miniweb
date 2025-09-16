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
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}
        .payment-container{background:#fff;border-radius:15px;box-shadow:0 20px 40px rgba(0,0,0,0.1);overflow:hidden;max-width:600px;width:100%}
        .payment-header{background:linear-gradient(135deg,#4a00e0,#8e2de2);color:white;padding:30px;text-align:center}
        .payment-header h2{font-size:28px;margin-bottom:10px}
        .security-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.2);padding:8px 16px;border-radius:20px;font-size:14px}
        .payment-body{padding:30px}
        .order-summary{background:#f8f9fa;border-radius:10px;padding:20px;margin-bottom:25px;border-left:4px solid #4a00e0}
        .order-summary h3{color:#333;margin-bottom:15px;display:flex;align-items:center;gap:10px}
        .summary-row{display:flex;justify-content:space-between;margin-bottom:8px;padding:5px 0}
        .summary-row.total{border-top:2px solid #ddd;padding-top:10px;margin-top:10px;font-weight:bold;font-size:18px;color:#4a00e0}
        .form-group{margin-bottom:20px;position:relative}
        .form-group label{display:block;margin-bottom:8px;font-weight:600;color:#333}
        .form-group input,.form-group select{width:100%;padding:12px 15px;border:2px solid #e0e0e0;border-radius:8px;font-size:16px;transition:border-color 0.3s ease}
        .form-group input:focus,.form-group select:focus{outline:none;border-color:#4a00e0;box-shadow:0 0 0 3px rgba(74,0,224,0.1)}
        .form-group.error input{border-color:#dc3545;box-shadow:0 0 0 3px rgba(220,53,69,0.1)}
        .form-group.success input{border-color:#28a745;box-shadow:0 0 0 3px rgba(40,167,69,0.1)}
        .error-message{color:#dc3545;font-size:14px;margin-top:5px;display:none;animation:fadeIn 0.3s ease}
        .form-group.error .error-message{display:block}
        .success-icon{position:absolute;right:15px;top:43px;color:#28a745;display:none}
        .form-group.success .success-icon{display:block}
        .payment-method-selector{display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:20px}
        .payment-option{border:2px solid #e0e0e0;border-radius:10px;padding:15px;text-align:center;cursor:pointer;transition:all 0.3s ease}
        .payment-option.active{border-color:#4a00e0;background:#f8f6ff}
        .payment-option:hover{border-color:#4a00e0}
        .payment-option i{font-size:24px;margin-bottom:8px;color:#4a00e0}
        .logos{display:flex;justify-content:center;gap:15px;margin:15px 0;flex-wrap:wrap}
        .logos img{height:35px;opacity:0.8;transition:opacity 0.3s ease}
        .logos img:hover{opacity:1}
        .card-row{display:grid;grid-template-columns:1fr 1fr;gap:15px}
        .card-type-icon{position:absolute;right:15px;top:43px;font-size:20px;opacity:0.6}
        .qr-section{text-align:center;padding:20px;background:#f8f9fa;border-radius:10px;margin-top:15px}
        .qr-section img{max-width:180px;border:3px solid #fff;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.1)}
        .pay-button{width:100%;background:linear-gradient(135deg,#4a00e0,#8e2de2);color:white;padding:15px;font-size:18px;font-weight:600;border:none;border-radius:10px;cursor:pointer;transition:all 0.3s ease;margin-top:20px;display:flex;align-items:center;justify-content:center;gap:10px}
        .pay-button:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 10px 25px rgba(74,0,224,0.3)}
        .pay-button:disabled{opacity:0.6;cursor:not-allowed;transform:none}
        .hidden{display:none}
        .security-features{background:#e8f5e8;border-radius:8px;padding:15px;margin-top:20px;border-left:4px solid #28a745}
        .security-features h4{color:#28a745;margin-bottom:10px;display:flex;align-items:center;gap:8px}
        .security-features ul{list-style:none;padding-left:0}
        .security-features li{display:flex;align-items:center;gap:8px;margin-bottom:5px;font-size:14px;color:#666}
        .loading-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.7);display:none;justify-content:center;align-items:center;z-index:1000}
        .loading-content{background:white;padding:30px;border-radius:15px;text-align:center}
        .spinner{width:40px;height:40px;border:4px solid #f3f3f3;border-top:4px solid #4a00e0;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 15px}
        .alert{padding:15px;margin:20px 0;border-radius:8px;display:none}
        .alert.error{background:#f8d7da;color:#721c24;border-left:4px solid #dc3545}
        .alert.success{background:#d4edda;color:#155724;border-left:4px solid #28a745}
        @keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @media (max-width:768px){.payment-method-selector{grid-template-columns:1fr}.card-row{grid-template-columns:1fr}.payment-container{margin:10px}}
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h2><i class="fas fa-shield-alt"></i> Secure Payment</h2>
            <div class="security-badge"><i class="fas fa-lock"></i>SSL Encrypted</div>
        </div>
        <div class="payment-body">
            <div class="order-summary">
                <h3><i class="fas fa-shopping-cart"></i> Order Summary</h3>
                <div class="summary-row"><span><strong>Item:</strong></span><span><?= htmlspecialchars($item['name']) ?></span></div>
                <div class="summary-row"><span><strong>Quantity:</strong></span><span><?= $quantity ?></span></div>
                <div class="summary-row total"><span><strong>Total Amount:</strong></span><span>₹<?= number_format($total_price, 2) ?></span></div>
            </div>
            <div class="alert error" id="alertBox"></div>
            <form id="paymentForm" novalidate>
                <input type="hidden" name="item_id" value="<?= $item_id ?>">
                <input type="hidden" name="quantity" value="<?= $quantity ?>">
                <input type="hidden" name="total_price" value="<?= $total_price ?>">
                <div class="form-group">
                    <label>Choose Payment Method</label>
                    <div class="payment-method-selector">
                        <div class="payment-option active" onclick="selectPaymentMethod('card')">
                            <i class="fas fa-credit-card"></i><div>Debit/Credit Card</div>
                        </div>
                        <div class="payment-option" onclick="selectPaymentMethod('upi')">
                            <i class="fab fa-google-pay"></i><div>UPI Payment</div>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="payment_method" value="card">
                </div>
                <div id="card-section">
                    <div class="logos">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="MasterCard">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg" alt="American Express">
                    </div>
                    <div class="form-group" id="cardNameGroup">
                        <label><i class="fas fa-user"></i> Cardholder Name *</label>
                        <input type="text" name="card_name" id="card_name" placeholder="Enter full name as on card" maxlength="50">
                        <div class="error-message">Please enter a valid cardholder name (2-50 characters)</div>
                        <i class="fas fa-check success-icon"></i>
                    </div>
                    <div class="form-group" id="cardNumberGroup">
                        <label><i class="fas fa-credit-card"></i> Card Number *</label>
                        <input type="text" name="card_number" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                        <div class="error-message">Please enter a valid card number</div>
                        <i class="fas fa-check success-icon"></i>
                        <div class="card-type-icon" id="cardTypeIcon"></div>
                    </div>
                    <div class="card-row">
                        <div class="form-group" id="expiryGroup">
                            <label><i class="fas fa-calendar"></i> Expiry Date *</label>
                            <input type="text" name="expiry" id="expiry" placeholder="MM/YY" maxlength="5">
                            <div class="error-message">Please enter a valid expiry date (MM/YY)</div>
                            <i class="fas fa-check success-icon"></i>
                        </div>
                        <div class="form-group" id="cvvGroup">
                            <label><i class="fas fa-lock"></i> CVV *</label>
                            <input type="password" name="cvv" id="cvv" placeholder="123" maxlength="4">
                            <div class="error-message">Please enter a valid CVV (3-4 digits)</div>
                            <i class="fas fa-check success-icon"></i>
                        </div>
                    </div>
                </div>
                <div id="upi-section" class="hidden">
                    <div class="logos">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/4/42/Paytm_logo.png" alt="Paytm">
                        <img src="gpay.jpeg" alt="Google Pay" style="background:#4285f4;border-radius:4px;">
                        <img src="pay.jpeg" alt="PhonePe" style="background:#5f259f;border-radius:4px;">
                        <img src="upi.png" alt="BHIM UPI" style="background:#ff8c00;border-radius:4px;">
                    </div>
                    <div class="form-group" id="upiIdGroup">
                        <label><i class="fas fa-mobile-alt"></i> UPI ID *</label>
                        <input type="text" name="upi_id" id="upi_id" placeholder="yourname@paytm / 9876543210@ybl">
                        <div class="error-message">Please enter a valid UPI ID (e.g., user@paytm or mobile@ybl)</div>
                        <i class="fas fa-check success-icon"></i>
                    </div>
                    <div class="qr-section">
                        <h4><i class="fas fa-qrcode"></i> Scan QR Code to Pay</h4>
                        <p style="margin:10px 0;color:#666;">Amount: ₹<?= number_format($total_price, 2) ?></p>
                        <img src="upi_1757997525118.png" alt="QR Code for Payment">
                    </div>
                </div>
                <button type="submit" class="pay-button" id="payButton">
                    <i class="fas fa-credit-card"></i>Pay ₹<?= number_format($total_price, 2) ?>
                </button>
            </form>
            <div class="security-features">
                <h4><i class="fas fa-shield-alt"></i> Your Payment is Secure</h4>
                <ul>
                    <li><i class="fas fa-check" style="color:#28a745;"></i> 256-bit SSL encryption</li>
                    <li><i class="fas fa-check" style="color:#28a745;"></i> PCI DSS compliant</li>
                    <li><i class="fas fa-check" style="color:#28a745;"></i> No card details stored</li>
                    <li><i class="fas fa-check" style="color:#28a745;"></i> Secure transaction processing</li>
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
        let vs={cardName:false,cardNumber:false,expiry:false,cvv:false,upiId:false};
        const ct={visa:/^4/,mastercard:/^5[1-5]/,amex:/^3[47]/,discover:/^6(?:011|5)/};
        function selectPaymentMethod(m){document.querySelectorAll('.payment-option').forEach(o=>o.classList.remove('active'));event.currentTarget.classList.add('active');document.getElementById('payment_method').value=m;document.getElementById('card-section').style.display=m==='card'?'block':'none';document.getElementById('upi-section').style.display=m==='upi'?'block':'none';const b=document.getElementById('payButton');const i=m==='card'?'fas fa-credit-card':'fab fa-google-pay';b.innerHTML=`<i class="${i}"></i> Pay ₹<?= number_format($total_price, 2) ?>`;clearValidations();updatePayButtonState()}
        function clearValidations(){document.querySelectorAll('.form-group').forEach(g=>g.classList.remove('error','success'));vs={cardName:false,cardNumber:false,expiry:false,cvv:false,upiId:false}}
        function showError(g,m){const grp=document.getElementById(g);const err=grp.querySelector('.error-message');grp.classList.remove('success');grp.classList.add('error');if(m)err.textContent=m;return false}
        function showSuccess(g){const grp=document.getElementById(g);grp.classList.remove('error');grp.classList.add('success');return true}
        function validateCardName(v){const t=v.trim();if(t.length<2)vs.cardName=showError('cardNameGroup','Name must be at least 2 characters long');else if(t.length>50)vs.cardName=showError('cardNameGroup','Name must not exceed 50 characters');else if(!/^[a-zA-Z\s.'-]+$/.test(t))vs.cardName=showError('cardNameGroup','Name can only contain letters, spaces, and common punctuation');else vs.cardName=showSuccess('cardNameGroup');return vs.cardName}
        function validateCardNumber(v){const c=v.replace(/\s/g,'');if(c.length===0){vs.cardNumber=showError('cardNumberGroup','Card number is required');return false}if(!/^\d+$/.test(c)){vs.cardNumber=showError('cardNumberGroup','Card number can only contain digits');return false}if(c.length<13||c.length>19){vs.cardNumber=showError('cardNumberGroup','Card number must be 13-19 digits long');return false}if(!luhnCheck(c)){vs.cardNumber=showError('cardNumberGroup','Invalid card number');return false}vs.cardNumber=showSuccess('cardNumberGroup');updateCardTypeIcon(c);return true}
        function luhnCheck(v){let s=0,e=false;for(let i=v.length-1;i>=0;i--){let d=parseInt(v.charAt(i));if(e){d*=2;if(d>9)d-=9}s+=d;e=!e}return s%10===0}
        function updateCardTypeIcon(n){const ic=document.getElementById('cardTypeIcon');if(ct.visa.test(n))ic.innerHTML='<i class="fab fa-cc-visa" style="color:#1a1f71;"></i>';else if(ct.mastercard.test(n))ic.innerHTML='<i class="fab fa-cc-mastercard" style="color:#eb001b;"></i>';else if(ct.amex.test(n))ic.innerHTML='<i class="fab fa-cc-amex" style="color:#006fcf;"></i>';else if(ct.discover.test(n))ic.innerHTML='<i class="fab fa-cc-discover" style="color:#ff6000;"></i>';else ic.innerHTML='<i class="fas fa-credit-card" style="color:#666;"></i>'}
        function validateExpiry(v){if(!/^\d{2}\/\d{2}$/.test(v)){vs.expiry=showError('expiryGroup','Enter expiry date in MM/YY format');return false}const [m,y]=v.split('/').map(n=>parseInt(n));const cy=new Date().getFullYear()%100;const cm=new Date().getMonth()+1;if(m<1||m>12){vs.expiry=showError('expiryGroup','Invalid month (01-12)');return false}if(y<cy||(y===cy&&m<cm)){vs.expiry=showError('expiryGroup','Card has expired');return false}if(y>cy+10){vs.expiry=showError('expiryGroup','Expiry date too far in future');return false}vs.expiry=showSuccess('expiryGroup');return true}
        function validateCVV(v){if(!/^\d{3,4}$/.test(v)){vs.cvv=showError('cvvGroup','CVV must be 3 or 4 digits');return false}vs.cvv=showSuccess('cvvGroup');return true}
        function validateUPI(v){const t=v.trim().toLowerCase();if(t.length===0){vs.upiId=showError('upiIdGroup','UPI ID is required');return false}const ur=/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/;if(!ur.test(t)){vs.upiId=showError('upiIdGroup','Invalid UPI ID format. Use format: user@provider');return false}const [u,p]=t.split('@');if(u.length<3){vs.upiId=showError('upiIdGroup','User part must be at least 3 characters');return false}if(u.length>50){vs.upiId=showError('upiIdGroup','User part too long (max 50 characters)');return false}if(/^\d+$/.test(u)&&u.length!==10){vs.upiId=showError('upiIdGroup','Mobile number must be 10 digits');return false}vs.upiId=showSuccess('upiIdGroup');return true}
        function formatCardNumber(){const inp=document.getElementById('card_number');let v=inp.value.replace(/\s/g,'').replace(/[^0-9]/gi,'');const fv=v.match(/.{1,4}/g)?.join(' ')||v;if(fv.length<=19){inp.value=fv;validateCardNumber(fv)}updatePayButtonState()}
        function formatExpiry(){const inp=document.getElementById('expiry');let v=inp.value.replace(/\D/g,'');if(v.length>=2)v=v.substring(0,2)+'/'+v.substring(2,4);inp.value=v;if(v.length===5){validateExpiry(v);updatePayButtonState()}}
        function updatePayButtonState(){const pm=document.getElementById('payment_method').value;const pb=document.getElementById('payButton');let iv=false;if(pm==='card')iv=vs.cardName&&vs.cardNumber&&vs.expiry&&vs.cvv;else if(pm==='upi')iv=vs.upiId;pb.disabled=!iv}
        function showAlert(m,t='error'){const ab=document.getElementById('alertBox');ab.textContent=m;ab.className=`alert ${t}`;ab.style.display='block';setTimeout(()=>ab.style.display='none',5000)}
        function simulatePaymentProcessing(fd){return new Promise(r=>{setTimeout(()=>{const pm=fd.get('payment_method');let s=true;if(pm==='card'){const cn=fd.get('card_number').replace(/\s/g,'');const nam=fd.get('card_name').toLowerCase();const cv=fd.get('cvv');if(cn.startsWith('4000000000000002')||nam.includes('fail')||cv==='000')s=false;else if(cn.length>=13&&cn.length<=19&&nam.length>=2&&cv.length>=3)s=true;else s=Math.random()>0.1}else if(pm==='upi'){const ui=fd.get('upi_id').toLowerCase();if(ui.includes('fail@')||ui.includes('error@')||ui.includes('invalid@'))s=false;else if(ui.includes('@')&&ui.length>5)s=true;else s=Math.random()>0.05}console.log('Payment simulation result:',s?'SUCCESS':'FAILED');r(s)},2000)})}
        function redirectToResultPage(s,fd){const f=document.createElement('form');f.method='POST';f.action=s?'payment_success.php':'payment_failed.php';f.style.display='none';for(let [k,v] of fd.entries()){const inp=document.createElement('input');inp.type='hidden';inp.name=k;inp.value=v;f.appendChild(inp)}const tid=document.createElement('input');tid.type='hidden';tid.name='transaction_id';tid.value='TXN'+Date.now()+Math.random().toString(36).substr(2,9).toUpperCase();f.appendChild(tid);const ts=document.createElement('input');ts.type='hidden';ts.name='timestamp';ts.value=new Date().toISOString();f.appendChild(ts);const st=document.createElement('input');st.type='hidden';st.name='payment_status';st.value=s?'success':'failed';f.appendChild(st);if(!s){const r=document.createElement('input');r.type='hidden';r.name='failure_reason';r.value=getRandomFailureReason();f.appendChild(r)}document.body.appendChild(f);f.submit()}
        function getRandomFailureReason(){const rs=['Insufficient funds in account','Card declined by bank','Network connection timeout','Invalid card details','Transaction limit exceeded','Card blocked or expired','Bank server temporarily unavailable','UPI transaction failed','Payment gateway error'];return rs[Math.floor(Math.random()*rs.length)]}
        document.getElementById('card_name').addEventListener('input',function(){validateCardName(this.value);updatePayButtonState()});
        document.getElementById('card_number').addEventListener('input',formatCardNumber);
        document.getElementById('expiry').addEventListener('input',formatExpiry);
        document.getElementById('cvv').addEventListener('input',function(){this.value=this.value.replace(/[^0-9]/g,'');validateCVV(this.value);updatePayButtonState()});
        document.getElementById('upi_id').addEventListener('input',function(){validateUPI(this.value);updatePayButtonState()});
        document.getElementById('paymentForm').addEventListener('submit',async function(e){e.preventDefault();const pm=document.getElementById('payment_method').value;let iv=true;if(pm==='card'){const cn=document.getElementById('card_name').value;const car=document.getElementById('card_number').value;const ex=document.getElementById('expiry').value;const cv=document.getElementById('cvv').value;iv=validateCardName(cn)&&validateCardNumber(car)&&validateExpiry(ex)&&validateCVV(cv);if(!iv){showAlert('Please correct the errors in the card details before proceeding.');return}const ccn=car.replace(/\s/g,'');if(ccn==='0000000000000000'||ccn==='1111111111111111'||ccn.length<13){showAlert('Invalid card number. Please enter a valid card number.');return}}else if(pm==='upi'){const ui=document.getElementById('upi_id').value;iv=validateUPI(ui);if(!iv){showAlert('Please enter a valid UPI ID.');return}}if(!iv){showAlert('Please fill all required fields correctly.');return}document.getElementById('loadingOverlay').style.display='flex';document.getElementById('payButton').disabled=true;const fd=new FormData(this);try{const ps=await simulatePaymentProcessing(fd);document.getElementById('loadingOverlay').style.display='none';if(ps)showAlert('Payment processed successfully! Redirecting...','success');else showAlert('Payment failed. Redirecting to failure page...','error');setTimeout(()=>redirectToResultPage(ps,fd),2000)}catch(er){document.getElementById('loadingOverlay').style.display='none';showAlert('An unexpected error occurred. Please try again.','error');document.getElementById('payButton').disabled=false}});
        document.addEventListener('DOMContentLoaded',function(){const fi=document.querySelector('input[name="card_name"]');if(fi)fi.focus();updatePayButtonState();if(window.history.replaceState)window.history.replaceState(null,null,window.location.href);document.getElementById('card_number').addEventListener('paste',function(e){setTimeout(()=>formatCardNumber(),10)});document.addEventListener('contextmenu',function(e){if(e.target.type==='password')e.preventDefault()});const sf=['card_number','cvv','upi_id'];sf.forEach(fid=>{const f=document.getElementById(fid);if(f){f.addEventListener('dragstart',e=>e.preventDefault());f.addEventListener('drop',e=>e.preventDefault())}})});
        document.addEventListener('keydown',function(e){if(e.keyCode===123||(e.ctrlKey&&e.shiftKey&&e.keyCode===73)||(e.ctrlKey&&e.keyCode===85)||(e.ctrlKey&&e.keyCode===83)){e.preventDefault();return false}});
        let it;function resetInactivityTimer(){clearTimeout(it);it=setTimeout(()=>{showAlert('Session expired due to inactivity. Please refresh the page.','error');document.getElementById('payButton').disabled=true},15*60*1000)}
        ['mousedown','mousemove','keypress','scroll','touchstart'].forEach(ev=>document.addEventListener(ev,resetInactivityTimer,true));
        resetInactivityTimer();
    </script>
</body>
</html>