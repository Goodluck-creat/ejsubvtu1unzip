<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Electricity Bill Payment</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
      --primary: #00A86B;
      --secondary: #1E1E2F;
      --accent: #FFD700;
    }

    body {
      margin: 0;
      padding: 0;
      background-color: var(--secondary);
      color: white;
      font-family: 'Segoe UI', sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }

    form {
      background: #fff;
      color: #000;
      padding: 25px;
      border-radius: 10px;
      max-width: 500px;
      width: 100%;
      box-shadow: 0 0 10px rgba(0,0,0,0.2);
      animation: fadeIn 0.6s ease-in-out;
    }

    h2 {
      color: var(--primary);
      text-align: center;
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    input, select {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      border: 1px solid #ccc;
      transition: border 0.3s ease;
    }

    input:focus, select:focus {
      border-color: var(--primary);
      outline: none;
    }

    #meter_name {
      font-weight: bold;
      color: var(--primary);
      margin-bottom: 10px;
    }

    button {
      background-color: var(--primary);
      color: white;
      padding: 12px;
      width: 100%;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    button:hover {
      background-color: #00724f;
    }

    .info {
      background: #f0f0f0;
      color: #000;
      padding: 10px;
      border-left: 4px solid var(--primary);
      margin-bottom: 15px;
      font-size: 14px;
      border-radius: 5px;
    }

    .amount-summary {
      margin: 10px 0;
      font-weight: bold;
      color: #333;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 600px) {
      form {
        padding: 20px;
      }
    }

    #preloader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #ffffff;
      z-index: 9999;
      display: flex;
      justify-content: center;
      align-items: center;
      opacity: 1;
    }

    .loader-img {
      width: 80px;
      height: auto;
      animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
      0% { transform: scale(1); opacity: 0.8; }
      50% { transform: scale(1.1); opacity: 1; }
      100% { transform: scale(1); opacity: 0.8; }
    }
  </style>
</head>
<body>
   

<!-- Preloader -->
<div id="preloader">
  <div style="text-align:center;">
    <img src="../images/ejsub.png" alt="Loading..." class="loader-img" style="background-color:gold;" />
    <p style="color:#000; font-weight:bold; margin-top:10px;">Processing...</p>
  </div>
</div>


<form id="electricityForm" method="POST" action="electricity_pay.php">
  <h2> <a href="javascript:history.back()" style="text-decoration:none; color:#00A86B; font-weight:bold;">
  <i class="fas fa-arrow-left"></i> 
</a> Pay Electricity Bill</h2>

  <div class="info">Please ensure your meter number is correct. Enter the amount you wish to pay (excluding ₦100 service fee).</div>

  <label for="disco">Select Disco:</label>
  <select name="disco" id="disco" required>
    <option value="">-- Choose Provider --</option>
    <?php
    include("../connection.php");
    session_start();
    $providers = $conn->query("SELECT * FROM vtu_electricity_providers ORDER BY provider_name");
    while ($row = $providers->fetch_assoc()) {
        echo "<option value='{$row['provider_code']}'>{$row['provider_name']}</option>";
    }
    ?>
  </select>

  <label for="meter_type">Meter Type:</label>
  <select name="meter_type" id="meter_type" required>
    <option value="prepaid">Prepaid</option>
    <option value="postpaid">Postpaid</option>
  </select>

  <label for="meter_number">Meter Number:</label>
  <input type="text" name="meter_number" id="meter_number" maxlength="11" required placeholder="E.g. 45701855087" />
  <button type="button" onclick="validateMeter()"> Validate Meter meter</button>
  <p>please validate meter before payment</p>
 
  <div id="meter_name"></div>

  <label for="amount">Amount (₦):</label>
  <input type="number" name="amount" id="amount" required oninput="updateTotal()" placeholder="Minimum ₦100" />

  <div class="amount-summary">Service Charge: ₦100</div>
  <div class="amount-summary">Total: ₦<span id="total_amount">0.00</span></div>

  <input type="hidden" name="verified_name" id="verified_name" />
  <button type="submit" id="payBtn" disabled>Pay Now</button>
</form>

<script>
function validateMeter() {
  const meter = document.getElementById("meter_number").value;
  const disco = document.getElementById("disco").value;
  const type = document.getElementById("meter_type").value;
  const preloader = document.getElementById("preloader");

  if (!meter || !disco || !type) {
    Swal.fire('⚠️ Incomplete Fields', 'Please select disco, meter type, and meter number.', 'warning');
    return;
  }

  preloader.style.display = 'flex';
  preloader.style.opacity = '1';

  fetch(`validate_meter.php?meter_number=${meter}&disco=${disco}&meter_type=${type}`)
    .then(res => res.json())
    .then(data => {
      preloader.style.display = 'none';

      if (data.status === 'success') {
        document.getElementById("meter_name").innerText = "✅ Meter belongs to: " + data.name;
        document.getElementById("verified_name").value = data.name;
        document.getElementById("payBtn").disabled = false;
        Swal.fire('✅ Verified', `Meter belongs to ${data.name}`, 'success');
      } else {
        document.getElementById("meter_name").innerText = "❌ Invalid meter or disco.";
        document.getElementById("verified_name").value = "";
        document.getElementById("payBtn").disabled = true;
        Swal.fire('❌ Error', data.message || 'Invalid meter number', 'error');
      }
    })
    .catch(err => {
      preloader.style.display = 'none';
      Swal.fire('❌ Error', 'An error occurred while validating.', 'error');
    });
}

function updateTotal() {
  const amt = parseFloat(document.getElementById("amount").value) || 0;
  const total = amt + 100;
  document.getElementById("total_amount").innerText = total.toFixed(2);
}
</script>

<script>
  // Hide preloader after full page load
  window.addEventListener('load', function () {
    const preloader = document.getElementById('preloader');
    preloader.style.transition = 'opacity 0.5s ease';
    preloader.style.opacity = '0';
    setTimeout(() => preloader.style.display = 'none', 500);
  });

  // Show preloader only on final form submit
  const payForm = document.getElementById("electricityForm");
  payForm.addEventListener("submit", function () {
    const preloader = document.getElementById("preloader");
    preloader.style.display = "flex";
    preloader.style.opacity = "1";
  });
</script>

</body>
</html>
