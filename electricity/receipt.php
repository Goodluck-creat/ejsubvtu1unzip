<?php
include("../connection.php");
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['ref'])) {
    die("Access Denied");
}

$user_id = $_SESSION['user_id'];
$request_id = $_GET['ref'];

$query = $conn->prepare("SELECT * FROM vtu_electricity_payments WHERE user_id = ? AND request_id = ?");
$query->bind_param("is", $user_id, $request_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("Transaction not found");
}

$data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Electricity Receipt - <?= $data['request_id'] ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root {
      --primary: #00A86B;
      --secondary: #1E1E2F;
      --accent: #FFD700;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 20px;
      background-color: var(--secondary);
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .receipt {
      background: #fff;
      color: #000;
      padding: 25px;
      border-radius: 10px;
      width: 100%;
      max-width: 600px;
      box-shadow: 0 0 10px rgba(0,0,0,0.15);
    }

    .receipt-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .receipt-header img {
      height: 50px;
    }

    .receipt h2 {
      text-align: center;
      color: var(--primary);
      margin: 20px 0;
    }

    table {
      width: 100%;
      font-size: 15px;
    }

    td {
      padding: 8px;
    }

    .label {
      font-weight: bold;
      color: var(--primary);
    }

    .token-box {
      margin-top: 20px;
      text-align: center;
    }

    .token-text {
      font-size: 18px;
      font-weight: bold;
      background: #f0f0f0;
      padding: 10px;
      border-radius: 5px;
      color: #000;
    }

    .btn {
      padding: 10px 15px;
      background: var(--primary);
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      margin: 10px auto;
      display: inline-block;
    }

    .btn:hover {
      background: #00724f;
    }

    .back-btn {
      display: inline-block;
      margin-bottom: 15px;
      text-decoration: none;
      color: var(--primary);
      font-weight: bold;
    }

    .back-btn i {
      margin-right: 6px;
    }

    .footer {
      text-align: center;
      margin-top: 30px;
      font-size: 13px;
      color: #555;
    }

    @media (max-width: 600px) {
      .receipt {
        padding: 20px;
      }

      .receipt-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }
    }
  </style>
</head>
<body>

  <div class="receipt">
    
    <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Go Back</a>

    <div class="receipt-header">
      <img src="../images/ejsub.png" alt="Site Logo">
      <div>
        <strong style="color: var(--primary);">Receipt Ref:</strong> <?= $data['request_id'] ?>
      </div>
    </div>

    <h2>Electricity Payment Receipt</h2>

    <table>
      <tr><td class="label">Disco:</td><td><?= $data['disco'] ?></td></tr>
      <tr><td class="label">Meter Type:</td><td><?= ucfirst($data['meter_type']) ?></td></tr>
      <tr><td class="label">Meter Number:</td><td><?= $data['meter_number'] ?></td></tr>
      <tr><td class="label">Verified Name:</td><td><?= $data['verified_name'] ?></td></tr>
      <tr><td class="label">Amount:</td><td>₦<?= number_format($data['amount'], 2) ?></td></tr>
      <tr><td class="label">Charges:</td><td>₦<?= number_format($data['charges'], 2) ?></td></tr>
      <!--<tr><td class="label">Old Balance:</td><td>₦<?= number_format($data['old_balance'], 2) ?></td></tr>-->
      <!--<tr><td class="label">New Balance:</td><td>₦<?= number_format($data['new_balance'], 2) ?></td></tr>-->
      <tr><td class="label">Status:</td><td><?= ucfirst($data['status']) ?></td></tr>
      <tr><td class="label">Message:</td><td><?= $data['message'] ?></td></tr>
    </table>

    <?php if (!empty($data['token'])): ?>
    <div class="token-box">
      <p><strong>Token Generated:</strong></p>
      <div id="token-text" class="token-text"><?= $data['token'] ?></div>
      <button onclick="copyToken()" class="btn">Copy Token</button>
    </div>
    <?php endif; ?>

    <div style="text-align:center;">
      <button onclick="printReceipt()" class="btn">🖨️ Print / Save as PDF</button>
    </div>

    <div class="footer">Thank you for using our VTU service.</div>
  </div>

  <script>
    function copyToken() {
      const token = document.getElementById("token-text").innerText;
      navigator.clipboard.writeText(token);
      alert("Token copied to clipboard!");
    }

    function printReceipt() {
      window.print();
    }
  </script>

</body>
</html>
